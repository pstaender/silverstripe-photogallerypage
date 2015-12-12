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

  function getCMSFields() {
    $fields = parent::getCMSFields();
    foreach($this->config()->get('removeCMSFields') as $key) {
      $fields->removeFieldFromTab('Root.Main', $key);
    }
    return $fields;
  }

  function Next($direction = '+') {
    $pic = null;
    if ($direction === '+') {
      $filter = [
        "Sort:GreaterThan" => $this->Sort,
      ];
      $sort = ['Sort' => 'ASC' ];
      // $pic = ($found = $this->Page()->Pictures()->filter($filter)->sort($sort)->first()) ? $found : $this->Page()->Pictures()->first();
    }
    if ($direction === '-') {
      $filter = [
        "Sort:LessThan" => $this->Sort,
      ];
      $sort = [ 'Sort' => 'DESC' ];
      // $pic = ($found = $this->Page()->Pictures()->filter($filter)->sort($sort)->first()) ? $found : $this->Page()->Pictures()->last();
    }
    return $this->Page()->Pictures()->filter($filter)->sort($sort)->first();
  }

  function fortemplate() {
    if ($pic = $this->Image()) {
      return '<section class="galleryPicture"><h1>'.$this->Title.'</h1><div class="content">'.$this->Content.'</div>'.$this->Image()->fortemplate().'</section>';
    } else {
      return null;
    }
  }

  function Previous() {
    return $this->Next('-');
  }

  function ImagePreview($width = null, $height = null) {
    if (!($width > 0)) {
      $width = $this->config()->get('previewWidth');
    }
    if (!($height > 0)) {
      $height = $this->config()->get('previewHeight');
    }
    return ($image = $this->Image()) ? $image->Fit($width, $height) : null;
  }

  function Content() {
    return $this->Content;
  }

  function ContentPreview() {
    if ($c = $this->dbObject('Content')) {
      return $c->FirstSentence();
    }
    return $this->Content;
  }

  function Title() {
    return $this->Title;
  }

  function getMosaicPicture($width = null, $height = null) {
    if (!$this->_moscaicPicture) {
      if ($width > 0) {
        $this->_moscaicPicture = imagecreatefromjpeg($this->Image()->SetWidth($width)->getFullPath());
      }
      if ($height > 0) {
        $this->_moscaicPicture = imagecreatefromjpeg($this->Image()->SetHeight($height)->getFullPath());
      }
    }
    return $this->_moscaicPicture;
  }

  private function pixelToColorString($x, $y, $image = null, $width = null, $height = null) {
    if (!$image) {
      $image = $this->getMosaicPicture($width, $height);
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
    return $this->Page()->Link().$this->URLSegment;
  }

  function Position() {
    return $this->Sort;
  }

  function LinkingMode() {
    $lm = 'link';
    if ($this->URLSegment === Controller::curr()->CurrentPicture()->URLSegment) {
      $lm = 'current';
    }
    return $lm;
  }

  function IsCurrent() {
    return ($this->LinkingMode() === 'current');
  }

  function TopLeftPixelValue() {
    return $this->pixelToColorString(0, 0, null, 6);
  }

  function TopRightPixelValue() {
    return $this->pixelToColorString(5, 0, null, 6);
  }

  function BottomLeftPixelValue() {
    return $this->pixelToColorString(0, 0, null, 6, 6);
  }

  function BottomRightPixelValue() {
    return $this->pixelToColorString(5, 0, null, 6, 6);
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

  function generateOptimizedJPEG(array $options = []) {
    if (($image = $this->Image()) && ($parentFolder = $image->Parent())) {
      $maxHeight = $options['maxHeight'];
      $maxWidth = $options['maxWidth'];
      $quality = $options['quality'];
      $subfolder = $options['subfolder'];
      $tmpDir = sys_get_temp_dir();
      $qualityBefore = Config::inst()->get('GDBackend', 'default_quality');
      Config::inst()->update('GDBackend', 'default_quality', 100);
      $folderPath = substr($parentFolder->getRelativePath().$subfolder, strlen(ASSETS_DIR));
      $folder = Folder::find_or_make($folderPath);
      $resizedImage = $image->FitMax($maxWidth, $maxHeight);
      $destDir = $folder->getFullPath();
      $destFile = $destDir.$image->Name;
      $command = $this->config()->get('jpegOptimCommand')." --strip-all -m".$quality." -o --stdout '".$resizedImage->getFullPath()."' > '$destFile'";
      $tmpFile = $tmpDir.$image->Name;
      shell_exec($command);
      Filesystem::sync($folder->ID);
      Config::inst()->update('GDBackend', 'default_quality', $qualityBefore);
    } else {
      // no image attached
      return null;
    }
  }

  function generateOptimizedJPEGAllSizes() {
    $config = [];
    $config['normal'] = $this->config()->get('normal');
    $config['medium'] = $this->config()->get('medium');
    $config['small'] = $this->config()->get('small');
    foreach($config as $size => $configData) {
      if (isset($configData['subfolder'])) {
        $this->generateOptimizedJPEG($configData);
      }
    }
  }

  function OptimizedJPEG($size = 'normal') {
    if ($image = $this->Image()) {
      $folder = ($config = $this->config()->get($size)) ? $config['subfolder'] : null;
      if ($folder) {
        $filepath = $image->Parent()->getRelativePath().$folder.'/'.$image->Name;
        // return $filepath;
        return Image::find($filepath);
      } else {
        return null;
      }
    }
    return null;
  }

  function onAfterWrite() {
    parent::onAfterWrite();
    $this->generateOptimizedJPEGAllSizes();
  }

  function onBeforeWrite() {
    parent::onBeforeWrite();

    if ($this->ParmanentURLSegment) {
      $this->URLSegment = $this->ParmanentURLSegment;
      return;
    }
    if ((!$this->URLSegment) || (preg_match("/^\d+$/", $this->URLSegment)) || ($this->isChanged('Title')) || ($this->forceUpdateURLSegment)) {
      $filter = URLSegmentFilter::create();
      $t = $filter->filter($this->Title());
      if (strlen(trim($t))>0) {
        $this->URLSegment = $t;
      } else {
        $this->URLSegment = $this->Sort;
      }
      if (!$this->URLSegment) {
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
