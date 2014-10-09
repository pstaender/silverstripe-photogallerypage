<?php

class GalleryPicture extends DataObject {

  private static $db = array(
    "Sort"                => "Int",
    "Title"               => "Varchar(255)",
    "Content"             => "Text",
    "URLSegment"          => "Varchar(255)",
    "ParmanentURLSegment" => "Varchar(255)",
  );

  private static $belongs_to = array(
    "Page" => "SiteTree",
  );

  private static $has_one = array(
    "Image"   => "Image",
    "Page"    => "SiteTree",
  );

  private static $indexes = array(
    "URLSegment" => true,
    "ParmanentURLSegment" => true,
  );

  // caching
  private $_allPicturesCount = null;
  private $_moscaicPicture = null;

  function Preview() {
    return ($image = $this->Image()) ? $image->SetWidth(300) : null;
  }

  function getMosaicPicture() {
    if (!$this->_moscaicPicture) {
      $this->_moscaicPicture = imagecreatefromjpeg($this->Image()->SetWidth(6)->getFullPath());
    }
    return $this->_moscaicPicture;
  }

  private function pixelToColorString($x, $y, $image = null) {
    if (!$image) {
      $image = $this->getMosaicPicture();
    }
    $rgb = imagecolorat($image, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    return "$r,$g,$b";
  }

  function AllPicturesCount() {
    $this->_allPicturesCount = ($this->_allPicturesCount) ? $this->_allPicturesCount : $this->Page()->Pictures()->Count();
    return $this->_allPicturesCount;
  }

  function canView($member = NULL) {
    return $this->Page()->canView($member);
  }

  function canEdit($member = NULL) {
    return $this->Page()->canEdit($member);
  }


  function Link() {
    $page = $this->Page();
    if ($page->ClassName === 'FrontPage') {
      return $page->Link();
    } else {
      return $page->Link().$this->URLSegment;
    }
  }

  function Position() {
    return $this->Sort;
  }

  // function Content($max = 250) {
  //   return ($this->Title) ? substr($this->Title,0,$max) : substr($this->Page()->Title,0,$max)." (".substr($this->Image()->Title,0,$max).")";
  // }

  function TopLeftPixelValue() {
    return $this->pixelToColorString(0, 0);
  }

  function TopRightPixelValue() {
    return $this->pixelToColorString(5, 0);
  }

  function IsPixelAbove($pixel, $threshold = 180) {
    $values = explode(',',$pixel);
    if (sizeof($values)>0) {
      return (((int)$values[0]+(int)$values[1]+(int)$values[2]) / 3) >= $threshold;
    }
    return null;
  }

  function IsTopLeftPixelAbove($threshold = 180) {
    return $this->IsPixelAbove($this->TopLeftPixelValue(), $threshold);
  }

  function IsTopRightPixelAbove($threshold = 180) {
    return $this->IsPixelAbove($this->TopRightPixelValue(), $threshold);
  }

  function onBeforeWrite() {
    parent::onBeforeWrite();

    if ($this->ParmanentURLSegment) {
      $this->URLSegment = $this->ParmanentURLSegment;
      return;
    }
    if ((!$this->URLSegment) || (preg_match("/^\d+$/", $this->URLSegment)) || ($this->isChanged('Title')) || ($this->forceUpdateURLSegment)) {
      $filter = URLSegmentFilter::create();
      $t = $filter->filter($this->Content());
      if (strlen(trim($t))>0) {
        $this->URLSegment = $t;
      } else {
        $this->URLSegment = $this->ID;
      }
    }
    // check for duplicate URLSegment
    $i = 1;
    // $this->URLSegment = preg_replace("/(^.*?)(\-\d+)+$/","$1", $this->URLSegment);
    while(GalleryPicture::get()->filter(['URLSegment' => $this->URLSegment, 'PageID' => $this->PageID])->exclude(['ID' => $this->ID])->Count() > 0) {
      $this->URLSegment = preg_replace("/(^.*?)(\-\d+)+$/","$1", $this->URLSegment);
      $number = (preg_match("/\-*0*(\d)+$/", $this->URLSegment, $matches)) ? (intval($matches[1])+1) : $i;
      $this->URLSegment = $this->URLSegment."-".sprintf('%02d', $number);
      $i++;
    }
  }

}
