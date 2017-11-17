<?php

namespace nglasl\mediawesome;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;

/**
 *	This is a CMS type/category of media.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaType extends DataObject {

	private static $table_name = 'MediaType';

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $has_many = array(
		'MediaAttributes' => MediaAttribute::class
	);

	private static $default_sort = 'Title';

	public function canView($member = null) {

		return true;
	}

	public function canEdit($member = null) {

		return true;
	}

	public function canCreate($member = null, $context = array()) {

		return $this->checkPermissions($member);
	}

	public function canDelete($member = null) {

		// Determine whether this is being used, and whether this is user created.

		$config = MediaPage::config();
		return
			!MediaHolder::get()->filter('MediaTypeID', $this->ID)->exists()
			&& !isset($config->type_defaults[$this->Title]);
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

			// Allow customisation of media type attributes, depending on the current CMS user permissions.

			$fields->removeByName('MediaAttributes');
			$configuration = ($this->checkPermissions() === false) ? GridFieldConfig_RecordViewer::create() : GridFieldConfig_RecordEditor::create();
			$fields->addFieldToTab('Root.Main', GridField::create(
				'MediaAttributes',
				'Custom Attributes',
				$this->MediaAttributes(),
				$configuration
			)->setModelClass(MediaAttribute::class));
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

		if($result->isValid() && !$this->Title) {
			$result->addError('"Title" required!');
		}
		else if($result->isValid() && MediaType::get_one(MediaType::class, array(
			'ID != ?' => $this->ID,
			'Title = ?' => $this->Title
		))) {
			$result->addError('Type already exists!');
		}

		// Allow extension customisation.

		$this->extend('validateMediaType', $result);
		return $result;
	}

}
