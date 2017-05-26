<?php

class GalleryPageHolder extends Page {

	private static $db = [];

	private static $has_one = [];

	private static $allowed_children = [
		'GalleryPage',
	];

	private static $icon = "silverstripe-photogallerypage/images/images.svg";

}
