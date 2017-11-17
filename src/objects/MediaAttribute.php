<?php

namespace nglasl\mediawesome;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

/**
 *	This is a CMS attribute for a media type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaAttribute extends DataObject {

	private static $table_name = 'MediaAttribute';

	private static $db = array(
		'Title' => 'Varchar(255)',
		'OriginalTitle' => 'Varchar(255)'
	);

	private static $has_one = array(
		'MediaType' => MediaType::class
	);

	private static $belongs_many_many = array(
		'MediaPages' => MediaPage::class . '.MediaAttributes'
	);

	public function canView($member = null) {

		return true;
	}

	public function canEdit($member = null) {

		return $this->checkPermissions($member);
	}

	public function canCreate($member = null, $context = array()) {

		return $this->checkPermissions($member);
	}

	public function canDelete($member = null) {

		// Determine whether this is being used.

		$current = Versioned::get_stage();
		foreach(singleton(Versioned::class)->getVersionedStages() as $stage) {
			Versioned::set_stage($stage);
			if($this->MediaPages()->exists() && $this->MediaPages()->where('MediaPageAttribute.Content IS NOT NULL')->exists()) {
				return false;
			}
		}
		Versioned::set_stage($current);

		// Determine whether this is user created.

		$config = MediaPage::config();
		$type = $this->MediaType()->Title;
		return !isset($config->type_defaults[$type]) || !in_array($this->OriginalTitle, $config->type_defaults[$type]);
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

		if($result->isValid() && !$this->Title) {
			$result->addError('"Title" required!');
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

	public function onAfterWrite() {

		parent::onAfterWrite();

		// This needs to appear on media pages of the respective type.

		foreach(MediaPage::get()->filter('MediaTypeID', $this->MediaTypeID) as $page) {
 			$page->MediaAttributes()->add($this);
 		}
	}

	public function onAfterDelete() {

		parent::onAfterDelete();

		// Clean up the pages associated with this.

		$current = Versioned::get_stage();
		foreach(singleton(Versioned::class)->getVersionedStages() as $stage) {
			Versioned::set_stage($stage);
			MediaPageAttribute::get()->filter('MediaAttributeID', $this->ID)->removeAll();
		}
		Versioned::set_stage($current);
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
