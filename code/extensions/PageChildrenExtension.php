<?php

/**
 *	Mediawesome extension which validates media holder/page placement.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class PageChildrenExtension extends DataExtension {

	/**
	 *	Restrict page children so media pages can't be assigned incorrectly.
	 */

	private static $allowed_children = array(
		'Page'
	);

}
