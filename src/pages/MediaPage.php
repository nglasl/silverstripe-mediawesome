<?php

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;

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
		'MediaAttributes' => array(
			'through' => 'MediaPageAttribute', // This is essentially the versioned join.
			'from' => 'MediaPage',
			'to' => 'MediaAttribute'
		),
		'Images' => Image::class,
		'Attachments' => File::class,
		'Categories' => 'MediaTag',
		'Tags' => 'MediaTag'
	);

	private static $owns = array(
		'MediaPageAttributes',
		'Images',
		'Attachments'
	);

	private static $defaults = array(
		'ShowInMenus' => 0
	);

	private static $searchable_fields = array(
		'Title',
		'ExternalLink',
		'Abstract',
		'Tagging'
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'MediaHolder';

	private static $description = 'Blog, Event, News, Publication <strong>or Custom Media</strong>';

	private static $icon = 'nglasl/silverstripe-mediawesome: client/images/page.png';

	/**
	 *	The default media types and their respective attributes.
	 */

	private static $type_defaults = array();

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Determine whether this requires an SS3 to SS4 migration.

		if(MediaAttribute::get()->filter('MediaTypeID', 0)->exists()) {

			// Retrieve the existing media attributes.

			$attributes = new SQLSelect(
				'*',
				'MediaAttribute',
				'LinkID <> 0 AND MediaPageID <> 0',
				'LinkID ASC'
			);
			$attributes = $attributes->execute();
			if(count($attributes)) {

				// With the results from above, delete these to prevent data integrity issues.

				$delete = new SQLDelete(
					'MediaAttribute',
					'LinkID <> 0 AND MediaPageID <> 0'
				);
				$delete->execute();

				// Migrate the existing media attributes.

				foreach($attributes as $existing) {
					$page = MediaPage::get()->byID($existing['MediaPageID']);
					if(!$page) {

						// This page may no longer exist.

						continue;
					}
					if($existing['LinkID'] == -1) {

						// Instantiate a new attribute for each "master" attribute.

						$attribute = MediaAttribute::create();
						$attribute->ID = $existing['ID'];
						$attribute->Created = $existing['Created'];
						$attribute->Title = $existing['Title'];
						$attribute->OriginalTitle = $existing['OriginalTitle'];
						$attribute->MediaTypeID = $page->MediaTypeID;
						$attribute->write();
					}
					else {
						$attribute = MediaAttribute::get()->byID($existing['LinkID']);
					}

					// Each page will have different content for a media attribute.

					$content = isset($existing['Content']) ? $existing['Content'] : null;
					$page->MediaAttributes()->add($attribute, array(
						'Content' => $content
					));

					// The attributes are versioned, but should only be published when it's considered safe to do so.

					if($page->isPublished() && !$page->isModifiedOnDraft()) {
						$page->publishRecursive();
					}
				}
			}
		}

		// Retrieve existing "start time" attributes.

		$attributes = MediaAttribute::get()->filter(array(
			'MediaType.Title' => 'Event',
			'OriginalTitle' => 'Start Time'
		));
		foreach($attributes as $attribute) {

			// These should now be "time" attributes.

			$attribute->Title = 'Time';
			$attribute->OriginalTitle = 'Time';
			$attribute->write();
		}

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

					// Confirm that the media attributes don't already exist before creating them.

					if(!MediaAttribute::get()->filter(array(
						'MediaTypeID' => $type->ID,
						'OriginalTitle' => $attribute
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
			Requirements::css('nglasl/silverstripe-mediawesome: client/css/mediawesome.css');
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'MediaNotification',
				"<p class='mediawesome notification'><strong>Mixed {$this->MediaType()->Title} Holder</strong></p>"
			), 'Type');
		}

		// Display the remaining media page fields.

		$fields->addFieldToTab('Root.Main', TextField::create(
			'ExternalLink'
		)->setDescription('An <strong>optional</strong> redirect URL to the media source'), 'URLSegment');
		$fields->addFieldToTab('Root.Main', DateField::create(
			'Date'
		), 'Content');

		// Allow customisation of categories and tags respective to the current page.

		$tags = MediaTag::get()->map()->toArray();
		$fields->findOrMakeTab('Root.CategoriesTags', 'Categories and Tags');
		$fields->addFieldToTab('Root.CategoriesTags', $categoriesList = ListboxField::create(
			'Categories',
			'Categories',
			$tags
		));
		$fields->addFieldToTab('Root.CategoriesTags', $tagsList = ListboxField::create(
			'Tags',
			'Tags',
			$tags
		));
		if(!$tags) {
			$categoriesList->setAttribute('disabled', 'true');
			$tagsList->setAttribute('disabled', 'true');
		}

		// Display an abstract field for content summarisation.

		$fields->addfieldToTab('Root.Main', $abstract = TextareaField::create(
			'Abstract'
		), 'Content');
		$abstract->setDescription('A concise summary of the content');

		// Allow customisation of the media type attributes.

		$fields->addFieldToTab('Root.Main', GridField::create(
			'MediaPageAttributes',
			"{$this->MediaType()->Title} Attributes",
			$this->MediaPageAttributes(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType(GridFieldAddNewButton::class)
		)->addExtraClass('pb-2'), 'Content');

		// Allow customisation of images and attachments.

		$type = strtolower($this->MediaType()->Title);
		$fields->findOrMakeTab('Root.ImagesAttachments', 'Images and Attachments');
		$fields->addFieldToTab('Root.ImagesAttachments', $images = Injector::inst()->create(
			FileHandleField::class,
			'Images'
		));
		$images->setAllowedFileCategories('image/supported');
		$images->setFolderName("media-{$type}/{$this->ID}/images");
		$fields->addFieldToTab('Root.ImagesAttachments', $attachments = Injector::inst()->create(
			FileHandleField::class,
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
			$error = new HTTPResponse_Exception($message, 403);
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

					$parent->publishRecursive();
				}
				$this->MediaTypeID = $existing->ID;
			}
		}
	}

	public function onAfterWrite() {

		parent::onAfterWrite();

		// This triggers for both a save and publish, causing duplicate attributes to appear.

		if(Versioned::get_stage() === 'Stage') {

			// The attributes of the respective type need to appear on this page.

			foreach($this->MediaType()->MediaAttributes() as $attribute) {
	 			$this->MediaAttributes()->add($attribute);
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
		$date = ($parent->URLFormatting !== '-') ? $this->dbObject('Date')->Format($parent->URLFormatting ?: 'y/MM/dd/') : '';
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
		$date = ($parent->URLFormatting !== '-') ? $this->dbObject('Date')->Format($parent->URLFormatting ?: 'y/MM/dd/') : '';
		$link = $parent->AbsoluteLink() . "{$date}{$this->URLSegment}/";
		if($action) {
			$link .= "{$action}/";
		}
		return $link;
	}

	/**
	 *	Retrieve the versioned attribute join records, since these are what we're editing.
	 *
	 *	@return media page attribute
	 */

	public function MediaPageAttributes() {

		return MediaPageAttribute::get()->filter('MediaPageID', $this->ID);
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
