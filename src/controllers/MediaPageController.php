<?php

namespace nglasl\mediawesome;

/**
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaPageController extends \PageController {

	/**
	 *	Determine the template for this media page.
	 */

	public function index() {

		// Use a custom media type page template if one exists.

		$type = $this->data()->MediaType();
		$templates = array();
		if($type->exists()) {
			$templates[] = 'MediaPage_' . str_replace(' ', '', $type->Title);
		}
		$templates[] = 'MediaPage';
		$templates[] = 'Page';
		$this->extend('updateTemplates', $templates);
		return $this->renderWith($templates);
	}

}
