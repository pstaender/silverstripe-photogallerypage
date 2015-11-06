# Photogallery Page module

Adds a page type to manage multiple images including description and other various options

The following SiteTree page type will be added:

  * `GalleryPage`

`GalleryPage` can hold many `GalleryPictures`. `GalleryPictures` holds the DataType `Image` and contains additional fields (URLSegment, Sort, Title …) for the image.

## Requirements

The following modules are required and have to be installed:

  * colymba/gridfield-bulk-editing-tools
  * undefinedoffset/sortablegridfield

Easiest way to install is using composer:

```sh
  $ cd to_your_silverstripe_root_dir
  $ composer require colymba/gridfield-bulk-editing-tools ">=2.1"
  $ composer require undefinedoffset/sortablegridfield ">=0.4"
```

## Installation

Best practice via composer:

```sh
  $ cd to_your_silverstripe_root_dir
  $ composer require pstaender/silverstripe-photogallerypage
```

Don't forget to run a `dev/build` after installing the module(s): `http://localhost/dev/build?flush=all`.

## Configuration

The following attributes can be configured in your project `config.yml` (default values are shown here):

```yml
---
Name: yourprojectconfig
---
GalleryPage:
  picturesPerPage: 100
  imageFolder: "images/"
  usePageURLSegmentAsSubfolder: true
  galleryImageListFieldMapping:
    Title: 'Title'
    Preview: 'Image'
    ContentFirstSentence: 'Content'
    URLSegment: 'URL'
    PermanentURLSegment: 'Permanent URL'
```
