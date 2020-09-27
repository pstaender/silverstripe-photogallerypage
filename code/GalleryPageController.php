<?php

use SilverStripe\ORM\DataObject;

class GalleryPageController extends PageController
{
    private static $url_handlers = [
        '$Picture' => 'picture', // catch-all
    ];

    private static $allowed_actions = [
        'picture' => '->canView()',
    ];

    protected function findPictureByURLSegment($urlSegment)
    {
        if (empty($urlSegment)) {
            $this->CurrentPicture = $this->dataRecord->FirstPicture();
        } else {

            $this->CurrentPicture = GalleryPicture::get()->filter([
                "URLSegment" => $urlSegment,
                "PageID" => $this->dataRecord->ID
            ])->first();
        }
        return $this->CurrentPicture;
    }

    private function findCachedPictureByURLSegment($urlSegment)
    {
        if ($this->CurrentPicture) {
            return $this->CurrentPicture;
        }
        return $this->CurrentPicture = $this->findPictureByURLSegment($urlSegment);
    }

    public function picture()
    {
        if ($this->config()->get('picturesAccessibleViaURL')) {
            $picturerUrlSegment = $this->request->param('Picture');
            $this->CurrentPicture = $this->findCachedPictureByURLSegment($picturerUrlSegment);
            if ($this->config()->get('redirectToFirstPictureOnEmptyURLSegment') && empty($picturerUrlSegment) && $this->CurrentPicture) {
                return $this->redirect($this->CurrentPicture->Link());
            }
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
        }
        return parent::hasAction($action);
    }

    public function checkAccessAction($action)
    {
        if ($this->config()->get('picturesAccessibleViaURL') && $this->findCachedPictureByURLSegment($this->request->param('Picture'))) {
            return $this->canView();
        }
        return parent::checkAccessAction($action);
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
