# Photogallery Module

Adds a page type to manage multiple images including description and other various options

The following SiteTree page type will be added:

  * `GalleryPage` / `GalleryPageController`
  * `GalleryPageHolder`
  * `GalleryPicture`

## Requirements

  * SilverStripe 4+ (use v0.6.1 for SilverStripe 3.x)

## Installation

```sh
  $ cd to_your_silverstripe_root_dir
  $ composer require pstaender/silverstripe-photogallerypage
```

Don't forget to run a `dev/build?flush=all` after installing.

## Methods and Behaviour

All Pictures will be available through `SortedPictures`, with `+` for ascending (default) and `-` for descending. All Pictures are also available as URL. Let's assume the URL of your GalleryPage is `http://localhost/photos` and (an arbitary) image has the URLSegment `example-picture`, it will be available as `http://localhost/photos/example-picture`. This behaviour can be be switched on/off through `picturesAccessibleViaURL` in your config (see below for further details).

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
        <a href="$Link">« $Image.ScaleHeight(100)</a>
      <% end_with %>
    </span>
    <span class="nextPicture">
      <% with Next %>
        <a href="$Link">$Image.ScaleHeight(100) » </a>
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

Have a look at `_config/photogallerypage.yml` to see what configuration attributes are available and can be overwritten in your projects' config.

## Copyright and License

This project is under GNU General Public License v2

Icons by Freepik (http://www.flaticon.com/packs/web-design-2)
