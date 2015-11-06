<?php

class GalleryPage extends Page {

  private static $db = [];

  private static $has_one = [];

  private static $has_many = [
    "Pictures" => "GalleryPicture",
  ];

  private static $icon = "silverstripe-photogallerypage/images/imageicon.png";

  private static $picturesPerPage = 20;
  private static $imageFolder = "images/";
  private static $usePageURLSegmentAsSubfolder = true;

  function getCMSFields() {
    $fields = parent::getCMSFields();

    $pictures_per_page = $this->config()->get('picturesPerPage');
    $conf = GridFieldConfig_RelationEditor::create($pictures_per_page);
    $conf->getComponentByType('GridFieldPaginator')->setItemsPerPage($pictures_per_page);
    $conf->addComponent(new GridFieldBulkUpload());
    $conf->addComponent(new GridFieldSortableRows('Sort'));
    $imageFolder = $this->config()->get('imageFolder');
    if ($this->config()->get('usePageURLSegmentAsSubfolder')) {
      $imageFolder = preg_replace("/^(.+?)\/*$/", '$1/', $imageFolder) . $this->URLSegment;
    }
    $conf->getComponentByType('GridFieldBulkUpload')->setUfSetup('setFolderName', $imageFolder);
    $gridField = new GridField('Pictures', 'Pictures', $this->SortedPictures(), $conf);
    $dataColumns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
    $imageFieldMapping = $this->config()->get('galleryImageListFieldMapping');
    if (!$imageFieldMapping) {
      $imageFieldMapping = [
        // 'Sort'                    => _t('PhotoGalleryPage.Sorting', 'Sorting'),
        'Preview'                 => _t('PhotoGalleryPage.PreviewThumb', 'Preview'),
        'Title'                   => _t('PhotoGalleryPage.Title', 'Title'),
        'ContentFirstSentence'    => _t('PhotoGalleryPage.Content', 'Content'),
        'URLSegment'              => _t('PhotoGalleryPage.URLSegment', 'URLSegment'),
        'PermanentURLSegment'     => _t('PhotoGalleryPage.URLSegment', 'PermanentURLSegment'),
      ];
    }
    $dataColumns->setDisplayFields($imageFieldMapping);
    if ($this->ID>0) {
      $fields->addFieldsToTab('Root.Pictures', array(
        $gridField,
      ));
    }
    return $fields;
  }

  function SortedPictures($direction = '+') {
    return $this->Pictures()->sort("Sort", ($direction==='-') ? "DESC" : "ASC");
  }

  function FirstPicture() {
    return $this->SortedPictures()->First();
  }

  function PicturesCount() {
    return $this->SortedPictures()->Count();
  }

  function Children() {
    return $this->SortedPictures();
  }

  function onBeforeDelete() {
    parent::onBeforeDelete();
    foreach($this->Pictures() as $pic) {
      $pic->delete();
    }
  }

  function asJSON() {
    $json = new JSONDataFormatter();
    $pictures = array();
    foreach($this->SortedPictures() as $pic) {
      $pictures[] = $json->convertDataObjectToJSONObject($pic);
    }
    return array(
      'page'        => $json->convertDataObjectToJSONObject($this),
      'pictures'    => $pictures
    );
  }


}
