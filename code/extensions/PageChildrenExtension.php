<?php

/**
 *	Mediawesome extension which validates media holder/page tree placement.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class PageChildrenExtension extends DataExtension {

	private static $allowed_children = array(
		'Page'
	);

}
