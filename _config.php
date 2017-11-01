<?php

/**
 *	The mediawesome specific configuration settings.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

if(!defined('MEDIAWESOME_PATH')) {
	define('MEDIAWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}

// Update the current media holder/page images.

$configuration = Config::inst();
$configuration->update('MediaHolder', 'icon', MEDIAWESOME_PATH . '/images/holder.png');
$configuration->update('MediaPage', 'icon', MEDIAWESOME_PATH . '/images/page.png');
