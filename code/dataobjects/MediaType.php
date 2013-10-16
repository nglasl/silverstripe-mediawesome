<?php

/**
 *	Mediawesome CMS type/category of media.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	/**
	 *	The default media types.
	 */

	private static $page_defaults = array(
		'Blog',
		'Event',
		'News',
		'Publication'
	);

	/**
	 *	The custom default media types.
	 */

	private static $custom_defaults = array(
	);

	/**
	 *	Apply all Mediawesome required extensions.
	 */

	public static function apply_required_extensions() {

		Object::add_extension('SiteConfig', 'SiteConfigMediaPermissionExtension');
		Object::add_extension('Page', 'PageChildrenExtension');
		Config::inst()->update('MediaHolder', 'icon', MEDIAWESOME_PATH . '/images/holder.png');
		Config::inst()->update('MediaPage', 'icon', MEDIAWESOME_PATH . '/images/page.png');
	}

	/**
	 *	Apply a custom default media type with no respective attributes.
	 *	NOTE: Refer to the module configuration example.
	 *
	 *	@parameter <{MEDIA_TYPE}> string
	 */

	public static function add_default($type) {

		//merge any new media type customisation

		self::$custom_defaults[] = $type;
	}

	/**
	 *	The process to automatically construct any default media types, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// create the default example types provided, along with any custom definitions

		$defaults = array_unique(array_merge(self::$page_defaults, self::$custom_defaults));
		foreach($defaults as $default) {

			// make sure one doesn't already exist

			if(!MediaType::get_one('MediaType', "Title = '" . Convert::raw2sql($default) . "'")) {
				$type = MediaType::create();
				$type->Title = $default;
				$type->write();
				DB::alteration_message("{$default} Media Type", 'created');
			}
		}
	}

	/**
	 *	Allow access for CMS users viewing media types.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canView($member = null) {
		return true;
	}

	/**
	 *	Allow access for CMS users editing media types.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canEdit($member = null) {
		return true;
	}

	/**
	 *	Determine access for the current CMS user creating media types.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canCreate($member = null) {
		return $this->checkPermissions($member);
	}

	/**
	 *	Restrict access for CMS users deleting media types.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
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
		$configuration = SiteConfig::current_site_config();
		return Permission::check($configuration->MediaPermission, 'any', $member);
	}

	/**
	 *	Display the respective CMS media type attributes.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// if no title has been set, allow creation of a new media type

		if($this->Title) {
			$fields->replaceField('Title', ReadonlyField::create(
				'Title'
			));
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'MediaAttributesTitle',
				"<div class='field'><label class='left'>Custom Attributes</label></div>"
			));
			if(MediaPage::get()->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($this->Title) . "'")->exists()) {

				// get the list of type attributes available and place them in a gridfield

				$configuration = ($this->checkPermissions() === false) ? GridFieldConfig_RecordViewer::create() : GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction');
				$fields->addFieldToTab('Root.Main', GridField::create(
					'MediaAttributes',
					'Custom Attributes',
					MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($this->Title) . "' AND MediaAttribute.LinkID = -1"),
					$configuration
				)->setModelClass('MediaAttribute'));
			}
			else {

				// Display a notification that a media page should first be created.

				Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
				$fields->addFieldToTab('Root.Main', LiteralField::create(
					'MediaNotification',
					"<p class='mediawesome notification'><strong>No {$this->Title} Pages Found</strong></p>"
				));
			}
		}

		// allow customisation of the cms fields displayed

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 *	Confirm that the current media type is valid.
	 */

	public function validate() {
		$result = parent::validate();

		// make sure a new media type has been given a title and doesn't already exist

		!$this->Title ?
			$result->error('Title required!') :
			MediaType::get_one('MediaType', "ID != " . Convert::raw2sql($this->ID) . " AND Title = '" . Convert::raw2sql($this->Title) . "'") ?
				$result->error('Type already exists!') :
				$result->valid();

		// allow validation extension

		$this->extend('validate', $result);

		return $result;
	}

}
