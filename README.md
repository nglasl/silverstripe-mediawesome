# ```php mediawesome``` Dynamic Media Pages

	A module for SilverStripe which allows creation of a flexible media holder and media pages
	with customisable type (blogs, events, news, publications).

## Requirement

* SilverStripe 3.1.X

## Getting Started

* Place the module under your root project directory.
* Define any custom media types and associated attributes through project configuration.
* `/dev/build`
* Create a media holder.
* Configure media type.
* Create media pages.
* Select the media holder.
* Configure media type attributes.

## Overview

### Custom Media Types & Associated Attributes

There are a number of default media types included, each with their own attributes.

```php
private static $page_defaults = array(
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

```php
MediaPage::customise_defaults(array(
	'MediaType' => array(
		'AttributeName'
	)
));
```

### Media Types

Minimises the number of manageable CMS items, where media pages will inherit the current holder type. It is also possible to have a media holder full of additional media holders.

### Dynamic Attributes

The attributes for a specific media type may be customised through the CMS by users, including the addition of new attributes (which will automatically be attached to all existing media pages of that type).

### Smart Templating

Custom templates may be defined for your media type (`MediaPage_Blog.ss` for a type Blog). It is also possible to reference a custom attribute directly through a custom template:

```php
$getAttribute(Author)
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
