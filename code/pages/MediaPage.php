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
		'Categories' => 'MediaTag',
		'Tags' => 'MediaTag'
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
		$fields->addFieldToTab('Root.Main', $date = DatetimeField::create(
			'Date'
		), 'Content');
		$date->getDateField()->setConfig('showcalendar', true);

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

		// Allow customisation of media type attribute content respective to the current page.

		$this->updateCMSAttributeFields($fields, 'Content');

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
	 * Update fieldlist with available attributes
	 */
	public function updateCMSAttributeFields(FieldList $fields, $insertBefore = null) {
		$mediaTypeField = $fields->dataFieldByName('MediaTypeID');
		$canModifyMediaType = ($mediaTypeField && !$mediaTypeField->isReadonly() && $mediaTypeField->hasMethod('getSource'));
		$useDisplayLogicModule = ($canModifyMediaType && class_exists('DisplayLogicFormField'));

		$attributeFields = array();
		if($this->MediaAttributes()->exists()) 
		{
			foreach($this->MediaAttributes() as $attribute) 
			{
				$attributeUID = ($attribute->LinkID == -1) ? $attribute->ID : $attribute->LinkID;
				$attributeField = $attribute->scaffoldFormField();
				if ($useDisplayLogicModule) 
				{
					$attributeField->displayIf('MediaTypeID')->isEqualTo((int)$attribute->MediaTypeID);
				}
				if (!$canModifyMediaType && $attribute->MediaTypeID != $this->MediaTypeID)
				{
					$attributeField = $attributeField->performReadonlyTransformation();
				}
				$attributeField->AttributeRecord = $attribute; // NOTE: For determining MediaTypeID on ObjectCreatorPage/review action.
				$attributeFields[$attributeUID] = $attributeField;
			}
		}
		
		// Add missing attribute fields by getting the placeholder attributes
		$mediaTypes = array($this->MediaTypeID => '');
		if ($canModifyMediaType)
		{
			$mediaTypes = $mediaTypeField->getSource();
		}
		if ($mediaTypes)
		{
			foreach ($mediaTypes as $mediaTypeID => $unused) 
			{
				$attributes = MediaAttribute::get()
					->where(array(
						'MediaTypeID = ?' => $mediaTypeID,
						'MediaAttribute.LinkID = -1' // NOTE: Not parameterized as it's a constant
				));
				foreach ($attributes as $attribute)
				{
					$attributeUID = ($attribute->LinkID == -1) ? $attribute->ID : $attribute->LinkID;
					if (!isset($attributeFields[$attributeUID]))
					{
						$attributeField = $attribute->scaffoldFormField();
						$attributeField->setValue('');
						if ($useDisplayLogicModule) {
							$attributeField->displayIf('MediaTypeID')->isEqualTo((int)$mediaTypeID);
						}
						$attributeField->AttributeRecord = $attribute; // NOTE: For determining MediaTypeID on ObjectCreatorPage/review action.
						$attributeFields[$attributeUID] = $attributeField;
					}
				}
			}
		}

		// Add fields to main field list
		if ($insertBefore)
		{
			foreach($attributeFields as $attributeField) 
			{
				$fields->insertBefore($attributeField, $insertBefore);
			}
		}
		else
		{
			foreach($attributeFields as $attributeField) 
			{
				$fields->push($attributeField);
			}
		}
	}

	/**
	 *	Confirm that the current page is valid.
	 */

	public function validate() {

		// The URL segment will conflict with a year/month/day/media format when numeric.

		if(is_numeric($this->URLSegment)) {

			// Customise a validation error message.

			$message = '"URL Segment" must not be numeric!';
			$error = new SS_HTTPResponse_Exception($message, 403);
			$error->getResponse()->addHeader('X-Status', rawurlencode($message));

			// Allow extension customisation.

			$this->extend('validateMediaPage', $error);
			throw $error;
		}
		return parent::validate();
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

		// Detect change in media type, if changed, put page underneath first MediaHolder found with matching Media Type 
		// -UNLESS- the ParentID has been explicitly changed.
		//
		// NOTE: This is for frontend fields where the MediaType can actually be changed in a dropdown.
		//
		$changedFields = $this->getChangedFields();
		$hasChangedParent = (isset($changedFields['ParentID']) && $changedFields['ParentID']['before'] != $changedFields['ParentID']['after']);
		$hasChangedMediaType = (isset($changedFields['MediaTypeID']) && $changedFields['MediaTypeID']['before'] != $changedFields['MediaTypeID']['after']);
		if ((!$this->ID || !$hasChangedParent) && $hasChangedMediaType && $this->MediaTypeID)
		{
			// NOTE(Jake): HasChangedMediaType = false, when pages are created in the CMS. Tested in SS 3.2.
			$holderPage = MediaHolder::get()->find('MediaTypeID', $this->MediaTypeID);
			if ($holderPage)
			{
				$this->ParentID = $holderPage->ID;
			}
		}

		// Apply the parent holder media type.
		$parent = $this->getParent();
		if($parent) 
		{
			// Set media type
			$type = '';
			$parentMediaType = $parent->MediaType();
			if($parentMediaType->exists()) 
			{
				// Force set to parent media type
				$this->MediaTypeID = $parentMediaType->ID;
				$type = $parentMediaType->Title;
			}
			else 
			{
				// If parent has no media type, force the parents media type 
				$existingMediaType = MediaType::get_one('MediaType');
				$parent->MediaTypeID = $existingMediaType->ID;
				$parent->write();

				// Force set to parent media type
				$this->MediaTypeID = $existingMediaType->ID;
				$type = $existingMediaType->Title;
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

				$attributes = MediaAttribute::get()->innerJoin('MediaPage', 'MediaAttribute.MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where(array(
					'MediaType.Title = ?' => $type,
					'MediaAttribute.LinkID = ?' => -1
				));
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

		//
		// Apply the changes from each media type attribute.
		//
		foreach($this->record as $name => $value) 
		{
			if (strrpos($name, 'MediaAttribute')) 
			{
				$ID = (int)substr($name, 0, strpos($name, '_'));
				if ($ID)
				{
					$attribute = DataObject::get_by_id('MediaAttribute', $ID);
					if ($attribute->MediaTypeID == $this->MediaTypeID)
					{
						if ($attribute->MediaPageID == $this->ID)
						{
							// Handle existing attributes attached to the page
							// (only if their media type matches)
							$attribute->Content = $value;
							$attribute->write();
						}
						else if ($value && $attribute->LinkID == -1)
						{
							// Handle adding new attributes, must have some sort of content to be added ($value)
							// to create them.
							//
							// If the attribute is the original/placeholder (and not attached to this page)
							// then create a copy of it and attach it to this page.
							//
							if ($this->MediaAttributes() instanceof UnsavedRelationList) 
							{
								// Attributes are created above and immediately attached based on the current
								// MediaTypeID. Use the ones created above.
								//
								// The code above was left as-is to ensure backwards compatibility,
								// ie. creating attributes for pages if none exist yet, rather than creating
								// 	   them if a value comes through.
								$newAttr = $this->MediaAttributes()->find('LinkID', $ID);
								if (!$newAttr) 
								{
									$newAttr = MediaAttribute::create();
								}
							}
							else
							{
								$newAttr = MediaAttribute::create();
							}
							$newAttr->OriginalTitle = $attribute->OriginalTitle;
							$newAttr->Title = $attribute->Title;
							$newAttr->Content = $value;
							$newAttr->LinkID = $attribute->ID;
							$newAttr->MediaPageID = $this->ID;
							$newAttr->write();
							$this->MediaAttributes()->add($newAttr);
						}
					}
				}
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
	 * Retrieve attributes attached to the page that match the current media type
	 *
	 * @return DataList
	 */
	public function getAttributes() {
		return $this->MediaAttributes()->filter(array('MediaTypeID' => $this->MediaTypeID));
	}

	/**
	 * Retrieve attributes attached to the page that match the current media type
	 *
	 * @return DataList
	 */
	public function Attributes() {
		return $this->getAttributes();
	}

	/**
	 *	Retrieve a specific attribute for use in templates.
	 *
	 *	@parameter <{ATTRIBUTE}> string
	 *	@return media attribute
	 */

	public function getAttribute($title) {

		foreach($this->MediaAttributes()->filter(array('MediaTypeID' => $this->MediaTypeID)) as $attribute) {

			// Retrieve the original title for comparison.

			if($attribute->OriginalTitle === $title) {
				return $attribute;
			}
		}
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