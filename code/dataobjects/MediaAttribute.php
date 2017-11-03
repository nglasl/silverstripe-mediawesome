<?php

/**
 *	Mediawesome CMS attribute for a media type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaAttribute extends DataObject {

	private static $db = array(
		'OriginalTitle' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'Content' => 'HTMLText',
		'LinkID' => 'Int'
	);

	private static $has_one = array(
		'MediaType' => 'MediaType',
		'MediaPage' => 'MediaPage'
	);

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Retrieve existing "start time" attributes.

		$attributes = MediaAttribute::get()->filter(array(
			'MediaType.Title' => 'Event',
			'OriginalTitle' => 'Start Time'
		));
		foreach($attributes as $attribute) {

			// These should now be "time" attributes.

			$attribute->OriginalTitle = 'Time';
			$attribute->Title = 'Time';
			$attribute->write();
		}
	}

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
	 *	Determine whether this is user created, and whether it's not being used on a media page.
	 */

	public function canDelete($member = null) {

		$config = MediaPage::config();
		$type = $this->MediaType()->Title;
		return
			(!isset($config->type_defaults[$type]) || !in_array($this->OriginalTitle, $config->type_defaults[$type]))
			&& (MediaAttribute::get()->filter('LinkID', $this->ID)->where('Content IS NOT NULL')->count() === 0);
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

		// Remove the attribute fields relating to an individual media page.

		$fields->removeByName('Content');
		$fields->removeByName('LinkID');
		$fields->removeByName('MediaPageID');

		// The media attribute may have no media type context, which `MediaAttributeAddNewButton` will then provide.

		if(Controller::has_curr()) {
			$fields->push(HiddenField::create(
				'MediaType',
				null,
				Controller::curr()->getRequest()->getVar('type')
			));
		}

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

		// Determine whether this is a new attribute.

		if(!$this->MediaPageID && !$this->ID) {

			// This will be the master attribute, which is used to keep the page attributes in line.

			$this->LinkID = -1;
			if(!$this->MediaTypeID) {

				// This needs the media type context.

				$this->MediaTypeID = $this->MediaType;
			}
		}
	}

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Retrieve the respective media type for updating all attribute references.

		$typeID = $this->MediaTypeID ?: $this->MediaType;
		$pages = MediaPage::get()->filter('MediaTypeID', $typeID);

		// Apply this new master attribute to existing media pages of the respective type.

		if($pages->exists() && !$this->MediaPageID && $this->isChanged('ID')) {
			foreach($pages as $key => $page) {

				// Create a new attribute for remaining media pages.

				$new = MediaAttribute::create();
				$new->OriginalTitle = $this->OriginalTitle;
				$new->Title = $this->Title;
				$new->LinkID = $this->ID;
				$new->MediaTypeID = $typeID;
				$new->MediaPageID = $page->ID;
				$new->write();
			}
		}

		// Apply the changes from this master attribute to existing media pages of the respective type.

		else if($pages->exists() && !$this->MediaPageID) {
			foreach($pages as $page) {
				foreach($page->MediaAttributes() as $attribute) {

					// Confirm that each attribute is linked to the original attribute.

					if(($attribute->LinkID == $this->ID) && ($attribute->Title !== $this->Title)) {

						// Apply the changes from this attribute.

						$attribute->Title = $this->Title;
						$attribute->write();
					}
				}
			}
		}
	}

	public function onAfterDelete() {

		parent::onAfterDelete();
		if($this->LinkID === -1) {

			// Delete the page attributes associated with this master attribute.

			MediaAttribute::get()->filter('LinkID', $this->ID)->removeAll();
		}
	}

	/**
	 *	Retrieve a class name of the current attribute for use in templates.
	 *
	 *	@return string
	 */

	public function getTemplateClass() {

		return strtolower($this->OriginalTitle);
	}

	/**
	 *	Retrieve the title and content of the current attribute for use in templates.
	 *
	 *	@return string
	 */

	public function forTemplate() {

		// Add spaces between words, other characters and numbers.

		return ltrim(preg_replace(array(
			'/([A-Z][a-z]+)/',
			'/([A-Z]{2,})/',
			'/([_.0-9]+)/'
		), ' $0', $this->Title)) . ": {$this->Content}";
	}

}
