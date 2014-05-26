<?php

/**
 *	Displays customised media content relating to the respective media type.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaPage extends SiteTree {

	private static $db = array(
		'ExternalLink' => 'Varchar(255)',
		'Abstract' => 'Text',
		'Date' => 'Datetime'
	);

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $has_many = array(
		'MediaAttributes' => 'MediaAttribute'
	);

	private static $many_many = array(
		'Images' => 'Image',
		'Attachments' => 'File',
		'Tags' => 'MediaTag'
	);

	private static $defaults = array(
		'ShowInMenus' => 0
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'MediaHolder';

	private static $description = 'Blog, Event, News, Publication <strong>or Custom Media</strong>';

	/**
	 *	The default media types and their respective attributes.
	 */

	private static $page_defaults = array(
		'Blog' => array(
			'Author'
		),
		'Event' => array(
			'Start Time',
			'End Time',
			'Location'
		),
		'News' => array(
			'Author'
		),
		'Publication' => array(
			'Author'
		)
	);

	/**
	 *	The custom default media types and their respective attributes.
	 */

	private static $custom_defaults = array(
	);

	/**
	 *	Apply custom default media types with respective attributes, or additional attributes to existing default media types.
	 *
	 *	@parameter <{MEDIA_TYPES_AND_ATTRIBUTES}> array(array(string))
	 */

	public static function customise_defaults($objects) {

		// Confirm that the parameter is valid.

		if(is_array($objects)) {
			foreach($objects as $temporary) {
				if(!is_array($temporary)) {
					return;
				}
			}

			// Apply an array unique for the nested array.

			$output = array();
			foreach($objects as $type => $attribute) {
				if(!isset(self::$custom_defaults[$type]) && !isset($output[$type]) && ($type !== 'MediaHolder')) {
					$output[$type] = $attribute;

					// Apply the custom default media types.

					MediaType::add_default($type);
				}
			}

			// Apply the custom default media types with respective attributes.

			self::$custom_defaults = array_merge(self::$custom_defaults, $output);
		}
	}

	/**
	 *	Display the appropriate CMS media page fields and respective media type attributes.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Display the media type as read only.

		$fields->addFieldToTab('Root.Main', ReadonlyField::create(
			'Type',
			'Type',
			$this->MediaType()->Title
		), 'Title');

		// Display a notification that the parent holder contains mixed children.

		$parent = $this->getParent();
		if($parent && $parent->getCheckMediaHolder()->exists()) {
			Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'MediaNotification',
				"<p class='mediawesome notification'><strong>Mixed {$this->MediaType()->Title} Holder</strong></p>"
			), 'Type');
		}

		// Display the remaining media page fields and tags.

		$fields->addFieldToTab('Root.Main', TextField::create(
			'ExternalLink'
		)->setRightTitle('An <strong>optional</strong> redirect URL to the media source'), 'URLSegment');
		$fields->addFieldToTab('Root.Main', $date = DatetimeField::create(
			'Date'
		), 'Content');
		$date->getDateField()->setConfig('showcalendar', true);
		$tags = MediaTag::get()->map()->toArray();
		$fields->addFieldToTab('Root.Main', $tagsList = ListboxField::create(
			'Tags',
			'Tags',
			$tags
		), 'Content');
		$tagsList->setMultiple(true);
		if(!$tags) {
			$tagsList->setAttribute('disabled', 'true');
		}

		// Allow customisation of media type attribute content respective to the current page.

		if($this->MediaAttributes()->exists()) {
			foreach($this->MediaAttributes() as $attribute) {
				if(strripos($attribute->Title, 'Time') || strripos($attribute->Title, 'Date') || stripos($attribute->Title, 'When')) {

					// Display an attribute as a date time field where appropriate.

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
				$custom->setRightTitle('Custom <strong>' . strtolower($this->MediaType()->Title) . '</strong> attribute');
			}
		}

		// Display an abstract field for content summarisation.

		$fields->addfieldToTab('Root.Main', $abstract = TextareaField::create(
			'Abstract'
		), 'Content');
		$abstract->setRightTitle('A concise summary of the content');
		$abstract->setRows(6);

		// Allow customisation of images and attachments.

		$type = strtolower($this->MediaType()->Title);
		$fields->addFieldToTab('Root.Images', $images = UploadField::create(
			'Images'
		));
		$images->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif', 'bmp'));
		$images->setFolderName("media-{$type}/{$this->ID}/images");
		$fields->addFieldToTab('Root.Attachments', $attachments = UploadField::create(
			'Attachments'
		));
		$attachments->setFolderName("media-{$type}/{$this->ID}/attachments");

		// Allow extension customisation.

		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Apply the parent holder media type and update any respective media type attributes.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Set the default media page date to the current time.

		if(is_null($this->Date)) {
			$this->Date = SS_Datetime::now()->Format('Y-m-d H:i:s');
		}

		// Confirm that the external link exists.

		if($this->ExternalLink) {
			if(stripos($this->ExternalLink, 'http') === false) {
				$this->ExternalLink = 'http://' . $this->ExternalLink;
			}
			$file_headers = @get_headers($this->ExternalLink);
			if(!$file_headers || strripos($file_headers[0], '404 Not Found')) {
				$this->ExternalLink = null;
			}
		}

		// Apply the changes from each media type attribute.

		foreach($this->record as $name => $value) {
			if(strrpos($name, 'MediaAttribute')) {
				$ID = substr($name, 0, strpos($name, '_'));
				$attribute = MediaAttribute::get_by_id('MediaAttribute', $ID);
				$attribute->Content = $value;
				$attribute->write();
			}
		}

		// Apply the parent holder media type.

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

			// Merge the default and custom default media types and their respective attributes.

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

			// Apply existing attributes to a new media page.

			if(!$this->MediaAttributes()->exists()) {

				// Retrieve existing attributes for the respective media type.

				$attributes = MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($type) . "' AND MediaAttribute.LinkID = -1");
				if($attributes->exists()) {
					foreach($attributes as $attribute) {

						// Create a new attribute for each one found.

						$new = MediaAttribute::create();
						$new->Title = $attribute->Title;
						$new->LinkID = $attribute->ID;
						$new->MediaPageID = $this->ID;
						$this->MediaAttributes()->add($new);
						$new->write();
					}
				}

				// Create a new attribute for each default and custom default media type found.

				else if(isset($defaults[$type])) {
					foreach($defaults[$type] as $attribute) {
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

	/**
	 *	Retrieve a specific attribute for use in templates.
	 *
	 *	@parameter <{ATTRIBUTE}> string
	 *	@return media attribute
	 */

	public function getAttribute($title) {

		foreach($this->MediaAttributes() as $attribute) {

			// Retrieve the original title for comparison.

			if($attribute->OriginalTitle === $title) {
				return $attribute;
			}
		}
	}

}

class MediaPage_Controller extends Page_Controller {

	/**
	 *	Determine the template for this media holder.
	 */

	public function index() {

		// Use a custom media type page template if one exists.

		$type = $this->data()->MediaType();
		$templates = array();
		if($type->exists()) {
			$templates[] = "{$this->data()->ClassName}_" . str_replace(' ', '', $type->Title);
		}
		$templates[] = $this->data()->ClassName;
		$templates[] = 'Page';
		return $this->renderWith($templates);
	}

}
