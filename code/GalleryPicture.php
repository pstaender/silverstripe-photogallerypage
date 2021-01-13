<?php

namespace Zeitpulse;

class GalleryPicture extends \SilverStripe\ORM\DataObject
{
    private static $db = [
        'Sort' => 'Int',
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'URLSegment' => 'Varchar(255)',
        'ParmanentURLSegment' => 'Varchar(255)',
    ];

    private static $belongs_to = [
        'Page' => 'SiteTree',
    ];

    private static $has_one = [
        'Image' => \SilverStripe\Assets\Image::class,
        'Page' => \Zeitpulse\GalleryPage::class,
    ];

    private static $indexes = [
        'URLSegment' => true,
        'ParmanentURLSegment' => true,
    ];

    private static $default_classname = 'GalleryPicture';
    private static $table_name = 'GalleryPicture';

    // caching
    private $_allPicturesCount = null;
    private $_moscaicPicture = null;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        foreach ($this->config()->get('removeCMSFields') as $key) {
            $fields->removeFieldFromTab('Root.Main', $key);
        }

        return $fields;
    }

    public function Next($direction = '+')
    {
        $pic = null;
        if ($direction === '+') {
            $filter = [
                'Sort:GreaterThan' => $this->Sort,
            ];
            $sort = ['Sort' => 'ASC'];
        }
        if ($direction === '-') {
            $filter = [
                'Sort:LessThan' => $this->Sort,
            ];
            $sort = ['Sort' => 'DESC'];
        }

        return $this->Page()->Pictures()->filter($filter)->sort($sort)->first();
    }

    public function fortemplate()
    {
        if ($pic = $this->Image()) {
            return '<section class="galleryPicture"><h1>'.$this->Title.'</h1><div class="content">'.$this->Content.'</div>'.$this->Image()->fortemplate().'</section>';
        } else {
            return null;
        }
    }

    public function Previous()
    {
        return $this->Next('-');
    }

    public function ImagePreview($width = null, $height = null)
    {
        if (!($width > 0)) {
            $width = $this->config()->get('previewWidth');
        }
        if (!($height > 0)) {
            $height = $this->config()->get('previewHeight');
        }

        return ($image = $this->Image()) ? $image->Fit($width, $height) : null;
    }

    public function PreviewImageField()
    {
        return ($image = $this->Image()) ? \SilverStripe\Forms\LiteralField::create('Preview', '<img src="'.$image->PreviewLink().'" style="max-height: 150px;" alt="'.$image->Title.'" />') : null;
    }

    public function Content()
    {
        return $this->Content;
    }

    public function ContentPreview()
    {
        if ($c = $this->dbObject('Content')) {
            return $c->FirstSentence();
        }

        return $this->Content;
    }

    public function Title()
    {
        return $this->Title;
    }

    public function getMosaicPicture($width = null, $height = null)
    {
        if (!$this->_moscaicPicture) {
            if (($width > 0) && ($this->Image()->ScaleWidth($width))) {
                $this->_moscaicPicture = imagecreatefromstring($this->Image()->ScaleWidth($width)->getString());
            }
            if (($height > 0) && ($this->Image()->ScaleHeight($height))) {
                $this->_moscaicPicture = imagecreatefromstring($this->Image()->ScaleHeight($height)->getString());
            }
        }

        return $this->_moscaicPicture;
    }

    private function pixelToColorString($x, $y, $image = null, $width = null, $height = null)
    {
        if (!$image) {
            $image = $this->getMosaicPicture($width, $height);
        }
        if ($image) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
        } else {
            $r = $g = $b = '0,0,0';
        }

        return "$r,$g,$b";
    }

    public function AllPicturesCount()
    {
        $this->_allPicturesCount = ($this->_allPicturesCount) ? $this->_allPicturesCount : $this->Page()->Pictures()->Count();

        return $this->_allPicturesCount;
    }

    public function canView($member = null)
    {
        return $this->Page()->canView($member);
    }

    public function canEdit($member = null)
    {
        return $this->Page()->canEdit($member);
    }

    public function Link()
    {
        return $this->Page()->Link().$this->URLSegment;
    }

    public function Position()
    {
        return $this->Sort;
    }

    public function LinkingMode()
    {
        $lm = 'link';
        if ($this->URLSegment === Controller::curr()->CurrentPicture()->URLSegment) {
            $lm = 'current';
        }

        return $lm;
    }

    public function IsCurrent()
    {
        return $this->LinkingMode() === 'current';
    }

    public function TopLeftPixelValue()
    {
        return $this->pixelToColorString(0, 0, null, 6);
    }

    public function TopRightPixelValue()
    {
        return $this->pixelToColorString(5, 0, null, 6);
    }

    public function BottomLeftPixelValue()
    {
        return $this->pixelToColorString(0, 0, null, 6, 6);
    }

    public function BottomRightPixelValue()
    {
        return $this->pixelToColorString(5, 0, null, 6, 6);
    }

    public function IsPixelAbove($pixel, $threshold = null)
    {
        $threshold = $this->defaultThresholdForDarkLight($threshold);
        $values = explode(',', $pixel);
        if (sizeof($values) > 0) {
            return (((int) $values[0] + (int) $values[1] + (int) $values[2]) / 3) >= $threshold;
        }

        return null;
    }

    public function IsTopLeftPixelAbove($threshold = null)
    {
        return $this->IsPixelAbove($this->TopLeftPixelValue(), $this->defaultThresholdForDarkLight($threshold));
    }

    public function IsTopRightPixelAbove($threshold = null)
    {
        return $this->IsPixelAbove($this->TopRightPixelValue(), $this->defaultThresholdForDarkLight($threshold));
    }

    public function IsBottomLeftPixelAbove($threshold = null)
    {
        return $this->IsPixelAbove($this->BottomLeftPixelValue(), $this->defaultThresholdForDarkLight($threshold));
    }

    public function IsBottomRightPixelAbove($threshold = null)
    {
        return $this->IsPixelAbove($this->BottomRightPixelValue(), $this->defaultThresholdForDarkLight($threshold));
    }

    public function defaultThresholdForDarkLight($threshold = null) {
        if ($threshold !== null) {
            return $threshold;
        }
        return $this->config()->get('darkLightThreshold');
    }


    public function NumberOfPicture()
    {
        return $this->Sort;
    }

    public function onBeforeWrite()
    {
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
            $this->URLSegment = preg_replace("/(^.*?)(\-\d+)+$/", '$1', $this->URLSegment);
            $number = (preg_match("/\-*0*(\d)+$/", $this->URLSegment, $matches)) ? (intval($matches[1]) + 1) : $i;
            $this->URLSegment = $this->URLSegment.'-'.sprintf('%02d', $number);
            ++$i;
        }
        if (!$this->Sort && $this->PageID > 0) {
            $this->Sort = GalleryPicture::get()->filter(['PageID' => $this->PageID])->max('Sort') + 1;
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->PageID > 0 && $image = $this->Image()) {
            $image->write(); //maybe that's too much here...
            $image->doPublish();
        }
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        if (($image = $this->Image()) && ($this->config()->get('deleteImageFileOnDelete'))) {
            $image->deleteFile();
            if ($image->ID) {
                $image->delete();
            }
        }
    }
}
