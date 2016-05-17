<?php

class GalleryPage_Controller extends Page_Controller {

  private static $url_handlers = array(
    '$Picture' => 'picture' // catch-all
  );

  private function findPictureByURLSegment($URLSegment, $cached = true) {
    if (($cached) && ($this->CurrentPicture)) {
      return $this->CurrentPicture;
    }
    if (!$URLSegment) {
      $this->CurrentPicture = $this->dataRecord->FirstPicture();
    } else {
      $this->CurrentPicture = GalleryPicture::get()->filter([ "URLSegment" => $URLSegment, "PageID" => $this->dataRecord->ID ])->first();
    }
    return $this->CurrentPicture;
  }

  function picture() {
    if ($this->config()->get('picturesAccessibleViaURL')) {
      $this->CurrentPicture = $this->findPictureByURLSegment($this->request->param('Picture'));
      if ($this->CurrentPicture) {
        return [];
      } else {
        if (($this->request->param('Picture')) || ($this->Pictures()->Count() > 0)) {
          if ($this->config()->get('redirectToParentPageIfPictureNotFound')) {
            return $this->redirect($this->dataRecord->Link());
          } else {
            return $this->httpError(404);
          }
        } else {
          return [];
        }
      }
    } else {
      return [];
    }
  }

  function hasAction($action) {
    if ($this->findPictureByURLSegment($action)) {
      return true;
    } else if (!$this->config()->get('picturesAccessibleViaURL')) {
      return true;
    } else {
      return parent::hasAction($action);
    }
  }

  function checkAccessAction($action) {
    if ($this->hasAction($action)) {
      return $this->dataRecord->canView();
    } else if (!$this->config()->get('picturesAccessibleViaURL')) {
      return true;
    } else {
      return false;
    }
  }

  function CurrentPicture($cache = true) {
    if (!$cache) {
      return $this->dataRecord->FirstPicture();
    }
    return ($this->CurrentPicture) ? $this->CurrentPicture : $this->CurrentPicture = $this->dataRecord->FirstPicture();
  }

  function NextPicture() {
    return $this->dataRecord->NextPicture($this->CurrentPicture());
  }

  function PrevPicture() {
    return $this->dataRecord->PrevPicture($this->CurrentPicture());
  }

}
