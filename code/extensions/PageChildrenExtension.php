<?php

/**
 *	Mediawesome extension which validates media holder/page tree placement.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class PageChildrenExtension extends DataExtension {

	/**
	 *	Restrict page children to page, so media can't be incorrectly assigned.
	 */

	private static $allowed_children = array(
		'Page'
	);

}
