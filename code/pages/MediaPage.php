<?php

/**
 *	Displays customised media content relating to the respective media type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaPage extends Page {

	private static $db = array(
		'ExternalLink' => 'Varchar(255)',
		'Abstract' => 'Text',
		'Date' => 'Date'
	);

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $many_many = array(
		'MediaAttributes' => 'MediaAttribute',
		'Images' => 'Image',
		'Attachments' => 'File',
		'Categories' => 'MediaTag',
		'Tags' => 'MediaTag'
	);

	/**
	 *	Each page will have different content for a media attribute.
	 */

	private static $many_many_extraFields = array(
		'MediaAttributes' => array(
			'Content' => 'HTMLText'
		)
	);

	private static $defaults = array(
		'ShowInMenus' => 0
	);

	private static $searchable_fields = array(
		'Title',
		'Content',
		'ExternalLink',
		'Abstract',
		'Tagging'
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'MediaHolder';

	private static $description = 'Blog, Event, News, Publication <strong>or Custom Media</strong>';

	/**
	 *	The default media types and their respective attributes.
	 */

	private static $type_defaults = array();

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Instantiate the default media types and their respective attributes.

		foreach($this->config()->type_defaults as $name => $attributes) {

			// Confirm that the media type doesn't already exist before creating it.

			$type = MediaType::get()->filter(array(
				'Title' => $name
			))->first();
			if(!$type) {
				$type = MediaType::create();
				$type->Title = $name;
				$type->write();
				DB::alteration_message("\"{$name}\" Media Type", 'created');
			}
			if(is_array($attributes)) {
				foreach($attributes as $attribute) {

					// Without this, it may cause a duplicate "time" attribute to appear when migrating.

					$titles = array(
						$attribute
					);
					if(($name === 'Event') && ($attribute === 'Time')) {
						$titles[] = 'Start Time';
					}

					// Confirm that the media attributes don't already exist before creating them.

					if(!MediaAttribute::get()->filter(array(
						'MediaTypeID' => $type->ID,
						'OriginalTitle' => $titles
					))->first()) {
						$new = MediaAttribute::create();
						$new->Title = $attribute;
						$new->MediaTypeID = $type->ID;
						$new->write();
						DB::alteration_message("\"{$name}\" > \"{$attribute}\" Media Attribute", 'created');
					}
				}
			}
		}
	}

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
		if($parent && $parent->getMediaHolderChildren()->exists()) {
			Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'MediaNotification',
				"<p class='mediawesome notification'><strong>Mixed {$this->MediaType()->Title} Holder</strong></p>"
			), 'Type');
		}

		// Display the remaining media page fields.

		$fields->addFieldToTab('Root.Main', TextField::create(
			'ExternalLink'
		)->setRightTitle('An <strong>optional</strong> redirect URL to the media source'), 'URLSegment');
		$fields->addFieldToTab('Root.Main', DateField::create(
			'Date'
		)->setConfig('showcalendar', true)->setConfig('dateformat', 'dd/MM/YYYY'), 'Content');

		// Allow customisation of categories and tags respective to the current page.

		$tags = MediaTag::get()->map()->toArray();
		$fields->findOrMakeTab('Root.CategoriesTags', 'Categories and Tags');
		$fields->addFieldToTab('Root.CategoriesTags', $categoriesList = ListboxField::create(
			'Categories',
			'Categories',
			$tags
		)->setMultiple(true));
		$fields->addFieldToTab('Root.CategoriesTags', $tagsList = ListboxField::create(
			'Tags',
			'Tags',
			$tags
		)->setMultiple(true));
		if(!$tags) {
			$categoriesList->setAttribute('disabled', 'true');
			$tagsList->setAttribute('disabled', 'true');
		}

		// Allow customisation of the media type attributes.

		foreach($this->MediaType()->MediaAttributes() as $attribute) {
			$existing = $this->MediaAttributes()->byID($attribute->ID);
			$content = $existing ? $existing->Content : null;
			if(strrpos($attribute->Title, 'Date') || strrpos($attribute->OriginalTitle, 'Date')) {

				// Display an attribute as a date field where appropriate.

				$fields->insertAfter('Date', $custom = DateField::create(
					"{$attribute->ID}_MediaAttribute",
					$attribute->Title,
					$content ? date('d/m/Y', strtotime($content)) : null
				)->setConfig('showcalendar', true)->setConfig('dateformat', 'dd/MM/YYYY'));
			}
			else {
				$fields->addFieldToTab('Root.Main', $custom = TextField::create(
					"{$attribute->ID}_MediaAttribute",
					$attribute->Title,
					$content
				), 'Content');
			}
			$custom->setRightTitle('Custom <strong>' . strtolower($this->MediaType()->Title) . '</strong> attribute');
		}

		// Display an abstract field for content summarisation.

		$fields->addfieldToTab('Root.Main', $abstract = TextareaField::create(
			'Abstract'
		), 'Content');
		$abstract->setRightTitle('A concise summary of the content');
		$abstract->setRows(6);

		// Allow customisation of images and attachments.

		$type = strtolower($this->MediaType()->Title);
		$fields->findOrMakeTab('Root.ImagesAttachments', 'Images and Attachments');
		$fields->addFieldToTab('Root.ImagesAttachments', $images = UploadField::create(
			'Images'
		));
		$images->getValidator()->setAllowedExtensions(array(
			'jpg',
			'jpeg',
			'png',
			'gif',
			'bmp'
		));
		$images->setFolderName("media-{$type}/{$this->ID}/images");
		$fields->addFieldToTab('Root.ImagesAttachments', $attachments = UploadField::create(
			'Attachments'
		));
		$attachments->setFolderName("media-{$type}/{$this->ID}/attachments");

		// Allow extension customisation.

		$this->extend('updateMediaPageCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Confirm that the current page is valid.
	 */

	public function validate() {

		$parent = $this->getParent();

		// The URL segment will conflict with a year/month/day/media format when numeric.

		if(is_numeric($this->URLSegment) || !($parent instanceof MediaHolder) || ($this->MediaTypeID && ($parent->MediaTypeID != $this->MediaTypeID))) {

			// Customise a validation error message.

			$message = is_numeric($this->URLSegment) ? '"URL Segment" must not be numeric!' : 'Invalid media holder!';
			$error = new SS_HTTPResponse_Exception($message, 403);
			$error->getResponse()->addHeader('X-Status', rawurlencode($message));

			// Allow extension customisation.

			$this->extend('validateMediaPage', $error);
			throw $error;
		}
		return parent::validate();
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Set the default media page date.

		if(!$this->Date) {
			$this->Date = date('Y-m-d');
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

		// Apply the parent holder media type.

		$parent = $this->getParent();
		if($parent) {
			$type = $parent->MediaType();
			if($type->exists()) {
				$this->MediaTypeID = $type->ID;
			}
			else {
				$existing = MediaType::get()->first();
				$parent->MediaTypeID = $existing->ID;
				$parent->write();
				if($parent->isPublished()) {

					// The parent needs to be published, otherwise it'll be considered an invalid media holder.

					$parent->publish('Stage', 'Live');
				}
				$this->MediaTypeID = $existing->ID;
			}
		}
	}

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Link any missing media type attributes.

		foreach($this->MediaType()->MediaAttributes() as $attribute) {
			$this->MediaAttributes()->add($attribute);
		}

		// Apply changes from the media type attributes.

		foreach($this->record as $name => $value) {
			if(strrpos($name, 'MediaAttribute')) {
				$ID = substr($name, 0, strpos($name, '_'));
				$attribute = MediaAttribute::get()->byID($ID);
				$this->MediaAttributes()->add($attribute, array(
					'Content' => $value
				));
			}
		}
	}

	/**
	 *	Determine the URL by using the media holder's defined URL format.
	 */

	public function Link($action = null) {

		$parent = $this->getParent();
		if(!$parent) {
			return null;
		}
		$date = ($parent->URLFormatting !== '-') ? $this->dbObject('Date')->Format($parent->URLFormatting) : '';
		$link = $parent->Link() . "{$date}{$this->URLSegment}/";
		if($action) {
			$link .= "{$action}/";
		}
		return $link;
	}

	/**
	 *	Determine the absolute URL by using the media holder's defined URL format.
	 */

	public function AbsoluteLink($action = null) {

		$parent = $this->getParent();
		if(!$parent) {
			return null;
		}
		$date = ($parent->URLFormatting !== '-') ? $this->dbObject('Date')->Format($parent->URLFormatting) : '';
		$link = $parent->AbsoluteLink() . "{$date}{$this->URLSegment}/";
		if($action) {
			$link .= "{$action}/";
		}
		return $link;
	}

	/**
	 *	Retrieve a specific attribute for use in templates.
	 *
	 *	@parameter <{ATTRIBUTE}> string
	 *	@return media attribute
	 */

	public function getAttribute($title) {

		return $this->MediaAttributes()->filter('OriginalTitle', $title)->first();
	}

	/**
	 *	Retrieve a specific attribute for use in templates.
	 *
	 *	@parameter <{ATTRIBUTE}> string
	 *	@return media attribute
	 */

	public function Attribute($title) {

		// This provides consistency when it comes to defining parameters from the template.

		return $this->getAttribute($title);
	}

}

class MediaPage_Controller extends Page_Controller {

	/**
	 *	Determine the template for this media page.
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
		$this->extend('updateTemplates', $templates);
		return $this->renderWith($templates);
	}

}
