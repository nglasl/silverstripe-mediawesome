<?php

/**
 *	Extension to validate media page and holder placement.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class PageChildrenExtension extends DataExtension {

	private static $allowed_children = array(
		'Page'
	);

}
