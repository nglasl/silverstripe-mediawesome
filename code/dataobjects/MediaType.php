<?php

/**
 *	Mediawesome CMS type/category of media.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $has_many = array(
		'MediaAttributes' => 'MediaAttribute'
	);

	private static $default_sort = 'Title';

	/**
	 *	Allow access for CMS users viewing media types.
	 */

	public function canView($member = null) {

		return true;
	}

	/**
	 *	Allow access for CMS users editing media types.
	 */

	public function canEdit($member = null) {

		return true;
	}

	/**
	 *	Determine access for the current CMS user creating media types.
	 */

	public function canCreate($member = null) {

		return $this->checkPermissions($member);
	}

	/**
	 *	Restrict access for CMS users deleting media types.
	 */

	public function canDelete($member = null) {

		return false;
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
		if($this->Title) {

			// Display the title as read only.

			$fields->replaceField('Title', ReadonlyField::create(
				'Title'
			));
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'MediaAttributesTitle',
				"<div class='field'><label class='left'>Custom Attributes</label></div>"
			));

			// Allow customisation of media type attributes, depending on the current CMS user permissions.

			$fields->removeByName('MediaAttributes');
			$configuration = ($this->checkPermissions() === false) ? GridFieldConfig_RecordViewer::create() : GridFieldConfig_RecordEditor::create();
			$fields->addFieldToTab('Root.Main', GridField::create(
				'MediaAttributes',
				'Custom Attributes',
				$this->MediaAttributes(),
				$configuration
			)->setModelClass('MediaAttribute'));
		}

		// Allow extension customisation.

		$this->extend('updateMediaTypeCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Confirm that the current type is valid.
	 */

	public function validate() {

		$result = parent::validate();

		// Confirm that the current type has been given a title and doesn't already exist.

		if($result->valid() && !$this->Title) {
			$result->error('"Title" required!');
		}
		else if($result->valid() && MediaType::get_one('MediaType', array(
			'ID != ?' => $this->ID,
			'Title = ?' => $this->Title
		))) {
			$result->error('Type already exists!');
		}

		// Allow extension customisation.

		$this->extend('validateMediaType', $result);
		return $result;
	}

}
