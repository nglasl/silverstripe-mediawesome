<?php

/**
 *	Mediawesome CMS type/category of media.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $default_sort = 'Title';

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

	private static $custom_defaults = array();

	/**
	 *	Apply a custom default media type with no respective attributes.
	 *	NOTE: Refer to the module configuration example.
	 *
	 *	@parameter <{MEDIA_TYPE}> string
	 */

	public static function add_default($type) {

		self::$custom_defaults[] = $type;
	}

	/**
	 *	The process to automatically create any default media types, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Merge the default and custom default media types.

		$defaults = array_unique(array_merge(self::$page_defaults, self::$custom_defaults));
		foreach($defaults as $default) {

			// Confirm that this media type doesn't already exist before creating it.

			if(!MediaType::get_one('MediaType', array(
				'Title = ?' => $default
			))) {
				$type = MediaType::create();
				$type->Title = $default;
				$type->write();
				DB::alteration_message("{$default} Media Type", 'created');
			}
		}
	}

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

	/**
	 *	Display the respective CMS media type attributes.
	 */

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

			// Allow customisation of media type attributes if a respective media page exists, depending on the current CMS user permissions.

			if(MediaPage::get()->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where(array(
				'MediaType.Title = ?' => $this->Title
			))->exists()) {
				if($this->checkPermissions() === false) {
					$configuration = GridFieldConfig_RecordViewer::create();
				}
				else {
					$configuration = GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction');

					// The media attribute may have no media type context, which `MediaAttributeAddNewButton` will then provide.

					$configuration->removeComponentsByType('GridFieldAddNewButton');
					$configuration->addComponent(new MediaAttributeAddNewButton($this->ID));
				}
				$fields->addFieldToTab('Root.Main', GridField::create(
					'MediaAttributes',
					'Custom Attributes',
					MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where(array(
						'MediaType.Title = ?' => $this->Title,
						'MediaAttribute.LinkID = ?' => -1
					)),
					$configuration
				)->setModelClass('MediaAttribute'));
			}
			else {

				// Display a notice that respective media pages should first be created.

				Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
				$fields->addFieldToTab('Root.Main', LiteralField::create(
					'MediaNotice',
					"<p class='mediawesome notice'><strong>No {$this->Title} Pages Found</strong></p>"
				));
			}
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
