<?php

class GalleryPicture extends \SilverStripe\ORM\DataObject {

	private static $db = [
		"Sort"                => "Int",
		"Title"               => "Varchar(255)",
		"Content"             => "Text",
		"URLSegment"          => "Varchar(255)",
		"ParmanentURLSegment" => "Varchar(255)",
	];

	private static $belongs_to = [
		"Page" => "SiteTree",
	];

	private static $has_one = [
		"Image" => \SilverStripe\Assets\Image::class,
		"Page"  => "GalleryPage",
	];

	private static $indexes = [
		"URLSegment"          => true,
		"ParmanentURLSegment" => true,
	];

	// caching
	private $_allPicturesCount = null;
	private $_moscaicPicture = null;

	function getCMSFields() {
		$fields = parent::getCMSFields();
		foreach ($this->config()->get('removeCMSFields') as $key) {
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
			$sort = ['Sort' => 'ASC'];
		}
		if ($direction === '-') {
			$filter = [
				"Sort:LessThan" => $this->Sort,
			];
			$sort = ['Sort' => 'DESC'];
		}
		return $this->Page()->Pictures()->filter($filter)->sort($sort)->first();
	}

	function fortemplate() {
		if ($pic = $this->Image()) {
			return '<section class="galleryPicture"><h1>' . $this->Title . '</h1><div class="content">' . $this->Content . '</div>' . $this->Image()->fortemplate() . '</section>';
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
		return $this->Page()->Link() . $this->URLSegment;
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
		$values = explode(',', $pixel);
		if (sizeof($values) > 0) {
			return (((int)$values[0] + (int)$values[1] + (int)$values[2]) / 3) >= $threshold;
		}
		return null;
	}

	function IsTopLeftPixelAbove($threshold = 180) {
		return $this->IsPixelAbove($this->TopLeftPixelValue(), $threshold);
	}

	function IsTopRightPixelAbove($threshold = 180) {
		return $this->IsPixelAbove($this->TopRightPixelValue(), $threshold);
	}

	function NumberOfPicture() {
		return $this->Sort;
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if ($this->ParmanentURLSegment) {
			$this->URLSegment = $this->ParmanentURLSegment;
			return;
		}
		if ((!$this->URLSegment) || (preg_match("/^\d+$/", $this->URLSegment)) || ($this->isChanged('Title')) || ($this->forceUpdateURLSegment)) {
			$filter = \SilverStripe\View\Parsers\URLSegmentFilter::create();
			$t = $filter->filter($this->Title());
			if (strlen(trim($t)) > 0) {
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
		while (GalleryPicture::get()->filter(['URLSegment' => $this->URLSegment, 'PageID' => $this->PageID])->exclude(['ID' => $this->ID])->Count() > 0) {
			$this->URLSegment = preg_replace("/(^.*?)(\-\d+)+$/", "$1", $this->URLSegment);
			$number = (preg_match("/\-*0*(\d)+$/", $this->URLSegment, $matches)) ? (intval($matches[1]) + 1) : $i;
			$this->URLSegment = $this->URLSegment . "-" . sprintf('%02d', $number);
			$i++;
		}
		if (!$this->Sort) {
			$this->Sort = self::get()->filter(['ParentID:GreaterThan' => 0, 'ParentID' => $this->ParentID])->max('Sort') + 1;
		}
	}


	function onBeforeDelete() {
		parent::onBeforeDelete();
		if (($image = $this->Image()) && ($this->config()->get('deleteImageFileOnDelete'))) {
			$image->delete();
		}
	}

}
