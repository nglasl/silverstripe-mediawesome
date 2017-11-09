<?php

/**
 *	Mediawesome CMS attribute for a media type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaAttribute extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'OriginalTitle' => 'Varchar(255)'
	);

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $belongs_many_many = array(
		'MediaPages' => 'MediaPage'
	);

	/**
	 *	Allow access for CMS users viewing attributes.
	 */

	public function canView($member = null) {

		return true;
	}

	/**
	 *	Determine access for the current CMS user editing attributes.
	 */

	public function canEdit($member = null) {

		return $this->checkPermissions($member);
	}

	/**
	 *	Determine access for the current CMS user creating attributes.
	 */

	public function canCreate($member = null) {

		return $this->checkPermissions($member);
	}

	/**
	 *	Determine whether this is user created, and whether it's not used on a media page.
	 */

	public function canDelete($member = null) {

		$config = MediaPage::config();
		$type = $this->MediaType()->Title;
		return
			(!isset($config->type_defaults[$type]) || !in_array($this->OriginalTitle, $config->type_defaults[$type]))
			&& (!$this->MediaPages()->exists() || !$this->MediaPages()->where('MediaPage_MediaAttributes.Content IS NOT NULL')->exists());
	}

	/**
	 *	Determine access for the current CMS user from the site configuration permissions.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function checkPermissions($member = null) {

		// Retrieve the current site configuration permissions for customisation of media.

		$configuration = SiteConfig::current_site_config();
		return Permission::check($configuration->MediaPermission, 'any', $member);
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->removeByName('OriginalTitle');
		$fields->removeByName('MediaTypeID');
		$fields->removeByName('MediaPages');

		// Allow extension customisation.

		$this->extend('updateMediaAttributeCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Confirm that the current attribute is valid.
	 */

	public function validate() {

		$result = parent::validate();

		// Confirm that the current attribute has been given a title.

		if($result->valid() && !$this->Title) {
			$result->error('"Title" required!');
		}

		// Allow extension customisation.

		$this->extend('validateMediaAttribute', $result);
		return $result;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Set the original title of the current attribute for use in templates.

		if(!$this->OriginalTitle) {
			$this->OriginalTitle = $this->Title;
		}
	}

	public function onAfterDelete() {

		parent::onAfterDelete();

		// Clean up the pages associated with this.

		$this->MediaPages()->removeAll();
	}

	/**
	 *	Retrieve a class name of the current attribute for use in templates.
	 *
	 *	@return string
	 */

	public function getTemplateClass() {

		return strtolower($this->OriginalTitle);
	}

}
