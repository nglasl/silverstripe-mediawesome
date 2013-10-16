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
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canView($member = null) {

		return true;
	}

	/**
	 *	Allow access for CMS users editing tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canEdit($member = null) {

		return true;
	}

	/**
	 *	Allow access for CMS users creating tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canCreate($member = null) {

		return true;
	}

	/**
	 *	Restrict access for CMS users deleting tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
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
		!$this->Title ?
			$result->error('Title required!') :
			MediaTag::get_one('MediaTag', "ID != " . Convert::raw2sql($this->ID) . " AND Title = '" . Convert::raw2sql($this->Title) . "'") ?
				$result->error('Tag already exists!') :
				$result->valid();

		// Allow extension customisation.

		$this->extend('validate', $result);
		return $result;
	}

}
