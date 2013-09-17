<?php

class MediaPage extends SiteTree {

	private static $description = 'Blog, Event, Media Release, News, Publication, Speech <strong>or Custom Media</strong>';

	private static $db = array(
		'External' => 'VARCHAR(255)',
		'Abstract' => 'TEXT',
		'Date' => 'Datetime'
	);

	private static $defaults = array(
		'ShowInMenus' => 0
	);

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $has_many = array(
		'MediaAttributes' => 'MediaAttribute'
	);

	private static $many_many = array(
		'Images' => 'Image',
		'Attachments' => 'File'
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'MediaHolder';

	private static $page_defaults = array(
		'Blog' => array(
			'Author'
		),
		'Event' => array(
			'Start Time',
			'End Time',
			'Location'
		),
		'Media Release' => array(
			'Contact Name',
			'Contact Number'
		),
		'News' => array(
			'Author'
		),
		'Publication' => array(
			'Author'
		),
		'Speech' => array(
			'Speaker',
			'Location'
		),
	);

	private static $custom_defaults = array(
	);

	public static function customise_defaults($objects) {

		// merge nested array

		if(is_array($objects)) {

			// make sure we don't have an invalid entry

			foreach($objects as $temporary) {
				if(!is_array($temporary)) {
					return;
				}
			}

			// a manual array unique since that doesn't work with nested arrays

			$output = array();
			foreach($objects as $type => $attribute) {
				if(!array_key_exists($type, self::$custom_defaults) && !array_key_exists($type, $output) && ($type !== 'MediaHolder')) {
					$output[$type] = $attribute;

					// add these new media types too

					MediaType::add_default($type);
				}
			}
			self::$custom_defaults = array_merge(self::$custom_defaults, $output);
		}
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// give this page a default date if not set

		if(is_null($this->Date)) {
			$this->Date = SS_Datetime::now()->Format('Y-m-d H:i:s');
		}

		// save each custom attribute field

		foreach($this->record as $name => $value) {
			if(strrpos($name, 'MediaAttribute')) {
				$ID = substr($name, 0, strpos($name, '_'));
				$attribute = MediaAttribute::get_by_id('MediaAttribute', $ID);
				$attribute->Content = $value;
				$attribute->write();
			}
		}

		// link this page to the parent media holder

		$type = $this->getParent()->MediaType();
		$this->MediaTypeID = $type->ID;
		$type = $type->Title;

		$defaults = array();
		foreach(self::$custom_defaults as $default => $attributes) {
			if(!array_key_exists($default, self::$page_defaults)) {
				$defaults[$default] = $attributes;
			}
		}
		$defaults = array_merge(self::$page_defaults, $defaults);

		// add existing attributes to a new media page

		if(!$this->MediaAttributes()->exists()) {

			// grab updated titles if they exist

			$attributes = MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($type) . "'");
			if($attributes->exists()) {

				// prevent duplicates

				$cache = array();
				foreach($attributes as $attribute) {
					if(!in_array($attribute->Title, $cache)) {
						$cache[] = $attribute->Title;

						// grab another of the same attribute with a valid link id

						foreach($attributes as $temporary) {
							if(($temporary->Title === $attribute->Title) && ($temporary->LinkID !== -1)) {
								$existing = $temporary->LinkID;
								break;
							}
						}
						$new = MediaAttribute::create();
						$new->Title = $attribute->Title;
						$new->LinkID = $existing ? $existing : -1;
						$new->MediaPageID = $this->ID;
						$this->MediaAttributes()->add($new);
						$new->write();
					}
				}
			}
			else if(isset($defaults[$type])) {
				foreach($defaults[$type] as $attribute) {
					$new = MediaAttribute::create();

					// initial write to generate a valid ID

					$new->write();

					// now we set the appropriate fields and write

					$new->Title = $attribute;
					$new->LinkID = $new->ID;
					$new->MediaPageID = $this->ID;
					$this->MediaAttributes()->add($new);
					$new->write();
				}
			}
		}
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// make sure the media page type matches the parent media holder

		$fields->addFieldToTab('Root.Main', ReadonlyField::create('Type', 'Type', $this->MediaType()->Title), 'Title');
		$fields->addFieldToTab('Root.Main', TextField::create('External', 'External Link')->setRightTitle('An optional redirect URL to the media source.'), 'Content');

		// add and configure the date/time field

		$fields->addFieldToTab('Root.Main', $date = DatetimeField::create('Date'), 'Content');
		$date->getDateField()->setConfig('showcalendar', true);

		// add all the custom attribute fields

		if($this->MediaAttributes()->exists()) {
			foreach($this->MediaAttributes() as $attribute) {
				if(strripos($attribute->Title, 'Date') || strripos($attribute->Title, 'Time')) {
					$fields->addFieldToTab('Root.Main', $date = DatetimeField::create("{$attribute->ID}_MediaAttribute", $attribute->Title, $attribute->Content), 'Content');
					$date->getDateField()->setConfig('showcalendar', true);
				}
				else {
					$fields->addFieldToTab('Root.Main', TextField::create("{$attribute->ID}_MediaAttribute", $attribute->Title, $attribute->Content), 'Content');
				}
			}
		}

		// add and configure the abstract field just before the main media content.

		$fields->addfieldToTab('Root.Main', $abstract = TextareaField::create('Abstract'), 'Content');
		$abstract->setRightTitle('A concise summary of the media.');
		$abstract->setRows(6);

		// add tabs for attachments and images

		$type = strtolower($this->MediaType()->Title);
		$fields->addFieldToTab('Root.Images', $images = UploadField::create('Images'));
		$images->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif', 'bmp'));
		$images->setFolderName("media-{$type}-{$this->ID}/images");
		$fields->addFieldToTab('Root.Attachments', $attachments = UploadField::create('Attachments'));
		$attachments->setFolderName("media-{$type}-{$this->ID}/attachments");

		return $fields;
	}

}

class MediaPage_Controller extends Page_Controller {

}
