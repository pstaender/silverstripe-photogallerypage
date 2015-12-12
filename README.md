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
  $ composer require pstaender/silverstripe-photogallerypage *
```

Don't forget to run a `dev/build` after installing the module(s): `http://localhost/dev/build?flush=all`.

## Methods and Behaviour

All Pictures will be available through `SortedPictures`, with `+` for ascending (default) and `-` for descending. All Pictures are also available as URL. Let's assume the URL of your GalleryPage is `http://localhost/photos` and (an arbitary) image has the URLSegment `example-picture`, it will be available as `http://localhost/photos/example-picture`. This behaviour can be be switched on/off through `picturesAccessibleViaURL` in your config (see below for further details).

Helpful methods on `GalleryPage_Controller`:

  * CurrentPicture
  * SortedPictures

Helpful methods on `GalleryPicture`:

  * ImagePreview
  * AllPicturesCount
  * IsCurrent
  * LinkingMode
  * Position
  * Link
  * OptimizedJPEG(normal|medium|small) (requires unix tool `jpegoptim` -> `apt-get install jpegoptim`)

## Usage in Template

Your `Layout/GalleryPage.ss` could be for instance:

```html
<article class="gallery">
  <h1>$Title</h1>
  <% with CurrentPicture %>
    <h2>Selected Picture</h2>
    $Image.ScaleHeight(200)
    <br />
    <span class="previousPicture">
      <% with Previous %>
        <a href="$Link">« $Image.SetHeight(100)</a>
      <% end_with %>
    </span>
    <span class="nextPicture">
      <% with Next %>
        <a href="$Link">$Image.SetHeight(100) » </a>
      <% end_with %>
    </span>
  <% end_with %>

  <br />

  <h3>All Pictures</h3>
  <% loop SortedPictures %>
    <a href="$Link" class="$LinkingMode">
      $ImagePreview
      <% if LinkingMode == 'current' %>selected<% end_if %>
    </a>
  <% end_loop %>
</article>
```

## Configuration

The following attributes can be configured optional in your project `config.yml` (default values are used here):

```yml
---
Name: yourprojectconfig
---
GalleryPicture:
  previewWidth: 300
  removeCMSFields:
    - Sort
    - URLSegment
GalleryPage_Controller:
  picturesAccessibleViaURL: true
GalleryPage:
  picturesPerPage: 100
  imageFolder: "images/"
  usePageURLSegmentAsSubfolder: true
  deletePicturesOnDeleteGallery: true
  galleryImageListFieldMapping:
    Title: 'Title'
    ImagePreview: 'ImagePreview'
    ContentPreview: 'ContentPreview'
    URLSegment: 'URL'
    PermanentURLSegment: 'PermanentURL'
```
