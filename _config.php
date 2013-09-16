<?php

/**
 *	Media specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('MEDIA_PATH')) {
	define('MEDIA_PATH', rtrim(basename(dirname(__FILE__))));
}

/*
 * EXAMPLE: Adding default pages and their attributes.
 *
 * MediaPage::addDefaults(array(
 *	'Type' => array(
 *		'Attribute1',
 *		'Attribute2'
 *	)
 * ));
 *
 */
