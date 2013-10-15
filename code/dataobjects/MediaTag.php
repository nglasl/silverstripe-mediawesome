<?php

/**
 *	Mediawesome CMS media page tag.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

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

	// prevent deletion of media tags

	public function canDelete($member = null) {
		return false;
	}

	public function canView($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return true;
	}

	public function canCreate($member = null) {
		return true;
	}
	
}
