<?php

namespace Zeitpulse;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField;

class GalleryPage extends Page
{
    private static $db = [
        'SortPicturesAlphanumerically' => 'Boolean',
    ];

    private static $table_name = 'GalleryPage';

    private static $has_one = [];

    private static $has_many = [
        'Pictures' => GalleryPicture::class,
    ];
    private static $icon = 'vendor/pstaender/silverstripe-photogallerypage/images/image.svg';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $pictures_per_page = $this->config()->get('picturesPerPage');
        $conf = GridField\GridFieldConfig_RelationEditor::create();
        $conf->getComponentByType(GridField\GridFieldPaginator::class)->setItemsPerPage($pictures_per_page);
        $conf->addComponent(new \Colymba\BulkUpload\BulkUploader());
        $conf->addComponent(new \Colymba\BulkManager\BulkManager());
        $conf->getComponentByType('Colymba\BulkUpload\BulkUploader')->setUfSetup('setFolderName', $this->uploadFolderName());
        $pictures = $this->SortedPictures();
        $gridField = new GridField\GridField('Pictures', 'Pictures', $pictures, $conf);

        if (!preg_match('/\\./', $this->pictureSortfield())) {
            $gridField->getConfig()->addComponent(new \Symbiote\GridFieldExtensions\GridFieldOrderableRows($this->pictureSortfield()));
        }

        $dataColumns = $gridField->getConfig()->getComponentByType(GridField\GridFieldDataColumns::class);
        $imageFieldMapping = $this->config()->get('galleryImageListFieldMapping');
        foreach ($imageFieldMapping as $key => $value) {
            $imageFieldMapping[$key] = _t('GalleryPicture.'.$key, $value);
        }
        $dataColumns->setDisplayFields($imageFieldMapping);

        if ($this->ID > 0) {
            $fields->addFieldsToTab('Root.'._t('GalleryPage.Photos', 'Photos'), [
                CheckboxField::create('SortPicturesAlphanumerically', _t('GalleryPage.SortPicturesAlphanumerically', 'Sort pictures alphanumerically')),
                $gridField,
            ]);
        }

        return $fields;
    }

    public function SortedPictures($direction = '+')
    {
        return $this->Pictures()->sort($this->pictureSortfield(), ($direction === '-') ? 'DESC' : 'ASC');
    }

    private function pictureSortfield()
    {
        return ($this->SortPicturesAlphanumerically) ? 'Image.Name' : 'Sort';
    }

    public function FirstPicture($direction = '+')
    {
        return $this->SortedPictures($direction)->First();
    }

    public function NextPicture($currentPicture = null)
    {
        if (!$currentPicture) {
            $currentPicture = $this;
        }
        $sort = $currentPicture->Sort;
        $next = $this->SortedPictures()->filter(['Sort:GreaterThan' => $sort])->sort('Sort ASC')->first();
        if (!$next) {
            // select first from next gallery
            $nextGallery = ($this->Parent()->ClassName == 'GalleryPageHolder') ? $this->Parent()->AllChildren()->filter(['Sort:GreaterThan' => $this->Sort])->sort('Sort ASC')->first() : $this->Parent()->AllChildren()->filter(['Sort:GreaterThan' => $this->Sort])->sort('Sort ASC')->first();
            if (($nextGallery) && (method_exists($next, 'SortedPictures'))) {
                $next = $nextGallery->SortedPictures()->First();
            }
        }

        return $next;
    }

    public function NumberOfPictures()
    {
        return $this->SortedPictures()->Count();
    }

    public function PrevPicture($currentPicture = null)
    {
        if (!$currentPicture) {
            $currentPicture = $this;
        }
        $sort = $currentPicture->Sort;
        $prev = $this->SortedPictures()->filter(['Sort:LessThan' => $sort])->sort('Sort', 'DESC')->first();
        if (!$prev) {
            // select last from prev gallery
            $prevGallery = $this->Parent()->AllChildren()->filter(['Sort:LessThan' => $this->Sort])->sort('Sort', 'DESC')->first();
            if (is_a($prevGallery, 'GalleryPage')) {
                $prev = $prevGallery->SortedPictures()->Last();
            }
        }

        return $prev;
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $publishedStatusFlag = $this->getStatusFlags();
        // only delete if gallery page is already unpublished
        // see https://github.com/silverstripe/silverstripe-framework/issues/4017
        if (isset($publishedStatusFlag['addedtodraft'])) {
            $this->deleteGalleryPictures();
        }
    }

    public function deleteGalleryPictures()
    {
        if ($pictures = $this->Pictures()) {
            foreach ($pictures as $picture) {
                $picture->delete();
            }
        }
    }

    private function uploadFolderName()
    {
        $imageFolder = $this->config()->get('imageFolder');
        // backward compatibility: use 'images/$URLSegment' instead
        if ($this->config()->get('usePageURLSegmentAsSubfolder')) {
            $imageFolder = preg_replace("/^(.+?)\/*$/", '$1/', $imageFolder).$this->URLSegment;
        }
        $imageFolder = str_replace('$ID', $this->ID, $imageFolder);
        $imageFolder = str_replace('$URLSegment', $this->URLSegment, $imageFolder);

        return $imageFolder;
    }
}
