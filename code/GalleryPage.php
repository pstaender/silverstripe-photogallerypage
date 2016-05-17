<?php

class GalleryPage extends Page {

  private static $db = [];

  private static $has_one = [];

  private static $has_many = [
    "Pictures" => "GalleryPicture",
  ];

  private static $icon = "silverstripe-photogallerypage/images/imageicon.png";

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
    foreach($imageFieldMapping as $key => $value) {
      $imageFieldMapping[$key] = _t('GalleryPicture.'.$key, $value);
    }
    $dataColumns->setDisplayFields($imageFieldMapping);
    if ($this->ID>0) {
      $fields->addFieldsToTab('Root.'._t('GalleryPage.Photos', 'Photos'), array(
        $gridField,
      ));
    }
    return $fields;
  }

  function SortedPictures($direction = '+') {
    return $this->Pictures()->sort("Sort", ($direction==='-') ? "DESC" : "ASC");
  }

  function FirstPicture($direction = '+') {
    return $this->SortedPictures($direction)->First();
  }

  function onBeforeDelete() {
    parent::onBeforeDelete();
    if ($this->config()->get('deletePicturesOnDeleteGallery')) {
      foreach($this->Pictures() as $pic) {
        $pic->delete();
      }
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

  function NextPicture($currentPicture = null) {
    if (!$currentPicture) {
      $currentPicture = $this;
    }
    $sort = $currentPicture->Sort;
    $next = $this->SortedPictures()->filter(['Sort:GreaterThan' => $sort])->sort('Sort ASC')->first();
    if (!$next) {
      // select first from next gallery
      $nextGallery = ($this->Parent()->ClassName == 'GalleryPageHolder') ? $this->Parent()->AllChildren()->filter(['Sort:GreaterThan' => $this->Sort])->sort('Sort ASC')->first() : $this->Parent()->AllChildren()->filter(['Sort:GreaterThan' => $this->Sort])->sort('Sort ASC')->first();
      if ($nextGallery) {
        $next = $nextGallery->SortedPictures()->First();
      }

    }
    return $next;
  }

  function NumberOfPictures() {
    return $this->SortedPictures()->Count();
  }

  function PrevPicture($currentPicture = null) {
    if (!$currentPicture) {
      $currentPicture = $this;
    }
    $sort = $currentPicture->Sort;
    $prev = $this->SortedPictures()->filter(['Sort:LessThan' => $sort])->sort("Sort", "DESC")->first();
    if (!$prev) {
      // select last from prev gallery
      $prevGallery = $this->Parent()->AllChildren()->filter(['Sort:LessThan' => $this->Sort])->sort("Sort", "DESC")->first();

      if (is_a($prevGallery,'GalleryPage')) {
        $prev = $prevGallery->SortedPictures()->Last();
      }


    }
    return $prev;
  }

}
