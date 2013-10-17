# mediawesome

	A module for SilverStripe which will allow creation of dynamic media holders/pages with CMS customisable
	types and attributes (blogs, events, news, publications).

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

Applying custom default media types with respective attributes, or additional attributes to existing default media types. These may also be added through the CMS, depending on the current CMS user permissions.

```php
MediaPage::customise_defaults(array(
	'Media Type' => array(
		'Attribute'
	)
));
```

### Dynamic Attributes

The attributes for a specific media type may be customised through the CMS by users, including the addition of new attributes (which will automatically be attached to all existing media pages of that type).

### Smart Templating

Custom templates may be defined for your media type (`MediaPage_Blog.ss` for a type Blog). It is also possible to reference a custom attribute directly through a custom template:

```php
$getAttribute(Author)
```

### Permissions

These may be changed through the site configuration, between administrators and content authors.

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
