<?php

/**
 *	Media specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('MEDIAWESOME_PATH')) {
	define('MEDIAWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}
MediaType::apply_required_extensions();

/*
 * EXAMPLE: Adding default pages and their attributes.
 *
 * MediaPage::customise_defaults(array(
 *	'Type' => array(
 *		'Attribute1',
 *		'Attribute2'
 *	)
 * ));
 *
 */
