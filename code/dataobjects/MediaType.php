<?php

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $page_defaults = array(
		'Blog',
		'Event',
		'News',
		'Publication'
	);

	private static $custom_defaults = array(
	);

	public static function add_default($type) {

		//merge any new media type customisation

		self::$custom_defaults[] = $type;
	}

	public static function apply_required_extensions() {

		Object::add_extension('SiteConfig', 'SiteConfigMediaAccessExtension');
		Object::add_extension('Page', 'PageChildrenExtension');
		Config::inst()->update('MediaHolder', 'icon', MEDIAWESOME_PATH . '/images/holder.png');
		Config::inst()->update('MediaPage', 'icon', MEDIAWESOME_PATH . '/images/page.png');
	}

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

	// prevent deletion of media types

	public function canDelete($member = null) {
		return false;
	}

	// allow a content author access to manage these media types

	public function canView($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return true;
	}

	public function canCreate($member = null) {
		return $this->checkPermissions($member);
	}

	public function checkPermissions($member = null) {
		$configuration = SiteConfig::current_site_config();
		return Permission::check($configuration->MediaAccess, 'any', $member);
	}

}
