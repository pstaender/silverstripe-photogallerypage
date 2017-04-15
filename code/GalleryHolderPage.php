<?php

class GalleryPageHolder extends Page {

  private static $db = array();

  private static $has_one = array();

  private static $allowed_children = array(
    'GalleryPage',
  );

	private static $icon = "silverstripe-photogallerypage/images/images.svg";

}
