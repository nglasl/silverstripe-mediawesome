<?php

/**
 *	The mediawesome specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('MEDIAWESOME_PATH')) {
	define('MEDIAWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}

// Update the current media holder/page images.

$configuration = Config::inst();
$configuration->update('MediaHolder', 'icon', MEDIAWESOME_PATH . '/images/holder.png');
$configuration->update('MediaPage', 'icon', MEDIAWESOME_PATH . '/images/page.png');

/**
 *
 *	EXAMPLE: Apply custom default media types with respective attributes, or additional attributes to existing default media types.
 *
 *	@parameter <{MEDIA_TYPES_AND_ATTRIBUTES}> array(array(string))
 *
 *	MediaPage::customise_defaults(array(
 *		'<Media Type>' => array(
 *			'<Attribute>',
 *			'<Attribute>',
 *			'<Attribute>'
 *		),
 *		'<Media Type>' => array(
 *			'<Attribute>',
 *			'<Attribute>',
 *			'<Attribute>'
 *		)
 *	));
 *
 */
