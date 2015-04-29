# [mediawesome](https://packagist.org/packages/nglasl/silverstripe-mediawesome)

_The current release is **1.1.0**_

	A module for SilverStripe which will allow creation of dynamic media holders/pages
	with CMS customisable types and attributes (blogs, events, news, publications).

## Requirement

* SilverStripe 3.1.X

## Getting Started

* Place the module under your root project directory.
* `/dev/build`
* Create a media holder.
* Configure the media type.
* Create media pages.

## Overview

### Default Media Types

These are the default media types and their respective attributes.

```php
array(
	'Blog' => array(
		'Author'
	),
	'Event' => array(
		'Start Time',
		'End Time',
		'Location'
	),
	'News' => array(
		'Author'
	),
	'Publication' => array(
		'Author'
	)
);
```

Apply custom default media types with respective attributes, or additional attributes to existing default types.

```php
MediaPage::customise_defaults(array(
	'Media Type' => array(
		'Attribute'
	)
));
```

These may also be added through the CMS, depending on the current user permissions.

* Select a media holder.
* Select `Manage ALL Media`
* Select `Types and Attributes`

### Dynamic Media Attributes

These may be customised through the CMS, depending on the current user permissions.

* Select a media holder.
* Select `Manage ALL Media`
* Select `Types and Attributes`
* Select the respective type.

These will be applied to new and existing media pages of the respective type.

### Media Categories and Tags

* Select a media holder.
* Select `Manage ALL Media`
* Select `Categories and Tags`

### CMS Permissions

These may be changed through the site configuration by an administrator.

* Select `Settings`
* Select `Access`

Customisation of media types and their respective attributes will be restricted.

### Filtering Media Pages

A media holder request may have optional date, category and tag filters, which are extendable by developers. It is also possible to represent the date in a `year/month/day/media` URL format.

Retrieve the date filter form in the media holder template:

```php
$DateFilterForm
```

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

	Nathan Glasl, nathan@silverstripe.com.au
