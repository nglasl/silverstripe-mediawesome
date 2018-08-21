<?php

namespace nglasl\mediawesome;

/**
 *  @author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaPageController extends \PageController {

	/**
	 *  Determine the template for this media page.
	 */

	public function index() {
		// The following code was taken from RedirectorPageController::index()
		// on SilverStripe 4.1.1
		/** @var MediaPage $page */
		$page = $this->data();
		if (!$this->getResponse()->isFinished() && $link = $page->ExternalLink) {
			return $this->redirect($link, 301);
		}

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
