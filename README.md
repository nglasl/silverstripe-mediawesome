# [mediawesome](https://packagist.org/packages/nglasl/silverstripe-mediawesome)

_The current release is **4.0.6**_

> This module allows creation of dynamic media holders/pages with CMS customisable types and attributes (blogs, events, news, publications), including versioning.

## Requirement

* SilverStripe 3.1 → **4.0**

**This repository is no longer supported, however this module is still supported [here](https://github.com/symbiote/silverstripe-mediawesome).**

## Getting Started

* [Place the module under your root project directory.](https://packagist.org/packages/nglasl/silverstripe-mediawesome)
* `/dev/build`
* Create a media holder.
* Configure the media type.
* Create media pages.

## Overview

### Default Media Types

These are the default media types and their respective attributes.

```yaml
nglasl\mediawesome\MediaPage:
  type_defaults:
    Blog:
      - 'Author'
    Event:
      - 'End Date'
      - 'Time'
      - 'End Time'
      - 'Location'
    News:
      - 'Author'
    Publication:
      - 'Author'
```

Apply custom default media types and/or respective attributes.

```yaml
nglasl\mediawesome\MediaPage:
  type_defaults:
    Type:
      - 'Attribute'
```

These may also be added through the CMS, depending on the current user permissions.

![types](https://raw.githubusercontent.com/nglasl/silverstripe-mediawesome/master/client/images/mediawesome-types.png)

* Select a media holder.
* Select `Manage ALL Media`
* Select `Types and Attributes`

### Dynamic Media Attributes

These may be customised through the CMS, depending on the current user permissions.

![attributes](https://raw.githubusercontent.com/nglasl/silverstripe-mediawesome/master/client/images/mediawesome-attributes.png)

* Select a media holder.
* Select `Manage ALL Media`
* Select `Types and Attributes`
* Select the respective type.

These attributes will appear on media pages of the respective type, and are versioned.

![versioning](https://raw.githubusercontent.com/nglasl/silverstripe-mediawesome/master/client/images/mediawesome-attribute-versioning.png)

### Media Categories and Tags

![categories-and-tags](https://raw.githubusercontent.com/nglasl/silverstripe-mediawesome/master/client/images/mediawesome-categories-and-tags.png)

* Select a media holder.
* Select `Manage ALL Media`
* Select `Categories and Tags`

### CMS Permissions

These may be changed through the site configuration by an administrator.

* Select `Settings`
* Select `Access`

Customisation of media types and their respective attributes will be restricted.

### Filtering Media Pages

A media holder request may have optional date, category and tag filters, which are extendable by developers.

The following on the media holder template allows a user to select a date, and then see media pages for and prior to that date:

```php
$DateFilterForm
```

It is also possible to represent the date in a `year/month/day/media` URL format.

![URL-formatting](https://raw.githubusercontent.com/nglasl/silverstripe-mediawesome/master/client/images/mediawesome-URL-formatting.png)

### Smart Templating

Custom media type templates may be defined for your media holder/page:

`MediaHolder_Blog.ss` or `MediaPage_Blog.ss`

Retrieve a specific media page attribute in templates:

```php
$Attribute('Author')
```

To see examples, look at the default templates:

`MediaHolder.ss` and `MediaPage.ss`

## Maintainer Contact

	Nathan Glasl, nathan@symbiote.com.au
