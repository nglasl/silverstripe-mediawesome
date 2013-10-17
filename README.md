# mediawesome

	A module for SilverStripe which will allow creation of dynamic media holders/pages with CMS
	customisable types and attributes (blogs, events, news, publications).

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

Apply custom default media types with respective attributes, or additional attributes to existing default media types.

```php
MediaPage::customise_defaults(array(
	'Media Type' => array(
		'Attribute'
	)
));
```

These may also be added through the CMS, depending on the current user permissions.

* Select a media holder.
* Select `Media Types`

### Dynamic Media Attributes

These may be customised through the CMS, depending on the current user permissions.

* Select a media holder.
* Select `Media Types`
* Select the respective type.

These will be applied to existing media pages of the respective type.

### Tags

* Select a media holder.
* Select `Media Tags`

### Permissions

These may be changed through the site configuration, between administrators and content authors.

### Smart Templating

Custom templates may be defined for your media type (`MediaPage_Blog.ss` for a type Blog). It is also possible to reference a custom attribute directly through a custom template:

```php
$getAttribute(Author)
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
