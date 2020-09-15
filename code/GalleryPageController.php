<?php

use SilverStripe\ORM\DataObject;

class GalleryPageController extends PageController
{

    private static $url_handlers = [
        '$Picture' => 'picture', // catch-all
    ];

    private function findPictureByURLSegment($URLSegment, $cached = true)
    {
        if (($cached) && ($this->CurrentPicture)) {
            return $this->CurrentPicture;
        }
        if (!$URLSegment) {
            $this->CurrentPicture = $this->dataRecord->FirstPicture();
        } else {
            $sanatizedURLSegment = preg_split('/[\\?#]/', $URLSegment)[0];
            $this->CurrentPicture = GalleryPicture::get()->filter(["URLSegment" => $sanatizedURLSegment, "PageID" => $this->dataRecord->ID])->first();
        }
        return $this->CurrentPicture;
    }

    public function picture()
    {
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

    public function hasAction($action)
    {
        if ($this->findPictureByURLSegment($action)) {
            return true;
        } else if (!$this->config()->get('picturesAccessibleViaURL')) {
            return true;
        } else {
            return parent::hasAction($action);
        }
    }

    public function checkAccessAction($action)
    {
        if ($this->hasAction($action)) {
            return $this->dataRecord->canView();
        } else if (!$this->config()->get('picturesAccessibleViaURL')) {
            return true;
        } else {
            return false;
        }
    }

    public function CurrentPicture($cache = true)
    {
        if (!$cache) {
            return $this->dataRecord->FirstPicture();
        }
        return ($this->CurrentPicture) ? $this->CurrentPicture : $this->CurrentPicture = $this->dataRecord->FirstPicture();
    }

    public function NextPicture()
    {
        return $this->dataRecord->NextPicture($this->CurrentPicture());
    }

    public function PrevPicture()
    {
        return $this->dataRecord->PrevPicture($this->CurrentPicture());
    }

    public function PrevPage($className)
    {
        return $this->previousOrNextPage('previous', $className);
    }

    public function NextPage($className)
    {
        return $this->previousOrNextPage('next', $className);
    }

    private function previousOrNextPage($mode = 'next', $className = 'SiteTree')
    {
        if ($mode == 'next') {
            $where = "ParentID = $this->ParentID AND Sort > $this->Sort";
            $sort = "Sort ASC";
        } elseif ($mode == 'previous') {
            $where = "ParentID = $this->ParentID AND Sort < $this->Sort";
            $sort = "Sort DESC";
        }
        return DataObject::get($className, $where, $sort)->first();
    }

}
