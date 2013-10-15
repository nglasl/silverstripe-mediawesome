<?php

/**
 *	Mediawesome specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('MEDIAWESOME_PATH')) {
	define('MEDIAWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}
MediaType::apply_required_extensions();

/**
 *
 *	EXAMPLE: Creating default media types with respective attributes.
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
