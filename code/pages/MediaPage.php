<?php

class MediaPage extends SiteTree {

	private static $description = 'Blog, Event, Media Release, News, Publication, Speech <strong>or Custom Media</strong>';

	private static $db = array(
		'ExternalLink' => 'VARCHAR(255)',
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

	// any custom attributes for existing media types will be stored in here rather than custom defaults

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
				if(!isset(self::$custom_defaults[$type]) && !isset($output[$type]) && ($type !== 'MediaHolder')) {
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

		// clean up an external url, making sure it exists/is available

		if($this->ExternalLink) {
			if(stripos($this->ExternalLink, 'http') === false) {
				$this->ExternalLink = 'http://' . $this->ExternalLink;
			}
			$file_headers = @get_headers($this->ExternalLink);
			if(!$file_headers || strripos($file_headers[0], '404 Not Found')) {
				$this->ExternalLink = null;
			}
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

		$parent = $this->getParent();
		if($parent) {
			$type = $parent->MediaType();
			if($type->exists()) {
				$this->MediaTypeID = $type->ID;
				$type = $type->Title;
			}
			else {
				$existing = MediaType::get_one('MediaType');
				$parent->MediaTypeID = $existing->ID;
				$parent->write();
				$this->MediaTypeID = $existing->ID;
				$type = $existing->Title;
			}

			$temporary = array();
			foreach(self::$custom_defaults as $default => $attributes) {
				if(isset(self::$page_defaults[$default])) {
					self::$page_defaults[$default] = array_unique(array_merge(self::$page_defaults[$default], $attributes));
				}
				else {
					$temporary[$default] = $attributes;
				}
			}
			$defaults = array_merge(self::$page_defaults, $temporary);

			// add existing attributes to a new media page

			if(!$this->MediaAttributes()->exists()) {

				// grab updated titles if they exist

				$attributes = MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($type) . "' AND MediaAttribute.LinkID = -1");
				if($attributes->exists()) {

					// grab another of the same attribute with a link id of -1 (should only be one)

					foreach($attributes as $attribute) {
						$new = MediaAttribute::create();
						$new->Title = $attribute->Title;
						$new->LinkID = $attribute->ID;
						$new->MediaPageID = $this->ID;
						$this->MediaAttributes()->add($new);
						$new->write();
					}
				}
				else if(isset($defaults[$type])) {
					foreach($defaults[$type] as $attribute) {

						// create this brand new attribute

						$new = MediaAttribute::create();
						$new->Title = $attribute;
						$new->LinkID = -1;
						$new->MediaPageID = $this->ID;
						$this->MediaAttributes()->add($new);
						$new->write();
					}
				}
			}
		}
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// make sure the media page type matches the parent media holder

		$fields->addFieldToTab('Root.Main', ReadonlyField::create(
			'Type',
			'Type',
			$this->MediaType()->Title
		), 'Title');
		$fields->addFieldToTab('Root.Main', TextField::create(
			'ExternalLink'
		)->setRightTitle('An optional redirect URL to the media source.'), 'URLSegment');

		// add and configure the date/time field

		$fields->addFieldToTab('Root.Main', $date = DatetimeField::create(
			'Date'
		), 'Content');
		$date->getDateField()->setConfig('showcalendar', true);

		// add all the custom attribute fields

		if($this->MediaAttributes()->exists()) {
			foreach($this->MediaAttributes() as $attribute) {
				if(strripos($attribute->Title, 'Time') || strripos($attribute->Title, 'Date') || stripos($attribute->Title, 'When')) {
					$fields->addFieldToTab('Root.Main', $custom = DatetimeField::create(
						"{$attribute->ID}_MediaAttribute",
						$attribute->Title,
						$attribute->Content
					), 'Content');
					$custom->getDateField()->setConfig('showcalendar', true);
				}
				else {
					$fields->addFieldToTab('Root.Main', $custom = TextField::create(
						"{$attribute->ID}_MediaAttribute",
						$attribute->Title,
						$attribute->Content
					), 'Content');
				}
				$custom->setRightTitle('Custom ' . strtolower($this->MediaType()->Title) . ' attribute.');
			}
		}

		// add and configure the abstract field just before the main media content.

		$fields->addfieldToTab('Root.Main', $abstract = TextareaField::create(
			'Abstract'
		), 'Content');
		$abstract->setRightTitle('A concise summary of the media.');
		$abstract->setRows(6);

		// add tabs for attachments and images

		$type = strtolower($this->MediaType()->Title);
		$fields->addFieldToTab('Root.Images', $images = UploadField::create(
			'Images'
		));
		$images->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif', 'bmp'));
		$images->setFolderName("media-{$type}-{$this->ID}/images");
		$fields->addFieldToTab('Root.Attachments', $attachments = UploadField::create(
			'Attachments'
		));
		$attachments->setFolderName("media-{$type}-{$this->ID}/attachments");

		return $fields;
	}

	// get an attribute for a template using the original title in case it has been changed/updated

	public function getAttribute($title) {
		foreach($this->MediaAttributes() as $attribute) {
			if($attribute->OriginalTitle === $title) {

				// return the attribute object so any variables may be accessed.

				return $attribute;
			}
		}
	}

}

class MediaPage_Controller extends Page_Controller {

	public function index() {

		// if a custom template for the specific page type has been defined, use this

		$type = $this->data()->MediaType();
		return $this->renderWith(array(($type->exists() ? str_replace(' ', '', $type->Title) : null), $this->data()->ClassName, 'Page'));
	}

}
