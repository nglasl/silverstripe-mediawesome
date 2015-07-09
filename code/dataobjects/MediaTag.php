<?php

/**
 *	Mediawesome CMS tag for a media page.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	/**
	 *	Allow access for CMS users viewing tags.
	 */

	public function canView($member = null) {

		return true;
	}

	/**
	 *	Allow access for CMS users editing tags.
	 */

	public function canEdit($member = null) {

		return true;
	}

	/**
	 *	Allow access for CMS users creating tags.
	 */

	public function canCreate($member = null) {

		return true;
	}

	/**
	 *	Restrict access for CMS users deleting tags.
	 */

	public function canDelete($member = null) {

		return false;
	}

	/**
	 *	Confirm that the current tag is valid.
	 */

	public function validate() {

		$result = parent::validate();

		// Confirm that the current tag has been given a title and doesn't already exist.

		$this->Title = strtolower($this->Title);
		if($result->valid() && !$this->Title) {
			$result->error('"Title" required!');
		}
		else if($result->valid() && MediaTag::get_one('MediaTag', "ID != " . (int)$this->ID . " AND Title = '" . Convert::raw2sql($this->Title) . "'")) {
			$result->error('Tag already exists!');
		}

		// Allow extension customisation.

		$this->extend('validateMediaTag', $result);
		return $result;
	}

}
