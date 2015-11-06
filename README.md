# Photogallery Page module

Adds a page type to manage multiple images including description and other various options

The following SiteTree page type will be added:

  * `GalleryPage`

`GalleryPage` can hold many `GalleryPictures`. `GalleryPictures` holds the DataType `Image` and contains additional fields (URLSegment, Sort, Title …) for the image.

## Installation

Best practice via composer:

```sh
  $ cd to_your_silverstripe_root_dir
  $ composer require pstaender/silverstripe-photogallerypage
```

## Requirements

The following modules will be installed (required):

  * colymba/gridfield-bulk-editing-tools
  * undefinedoffset/sortablegridfield
