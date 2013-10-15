<?php

/**
 *	Mediawesome CMS media page tag.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	/**
	 *	Allow access for CMS users viewing media page tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canView($member = null) {
		return true;
	}

	/**
	 *	Allow access for CMS users editing media page tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canEdit($member = null) {
		return true;
	}

	/**
	 *	Allow access for CMS users creating media page tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canCreate($member = null) {
		return true;
	}

	/**
	 *	Restrict access for CMS users deleting media page tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canDelete($member = null) {
		return false;
	}

	/**
	 *	Confirm that the current media page tag is valid.
	 */

	public function validate() {
		$result = parent::validate();

		// make sure a media tag has been given a title and doesn't already exist

		$this->Title = strtolower($this->Title);
		!$this->Title ?
			$result->error('Title required!') :
			MediaTag::get_one('MediaTag', "ID != " . Convert::raw2sql($this->ID) . " AND Title = '" . Convert::raw2sql($this->Title) . "'") ?
				$result->error('Tag already exists!') :
				$result->valid();

		// allow validation extension

		$this->extend('validate', $result);

		return $result;
	}
	
}
