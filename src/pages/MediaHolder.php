<?php

namespace nglasl\mediawesome;

use Page;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataList;

/**
 *	Displays media holder/page children, with optional date/tag filters.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaHolder extends Page {

	private static $table_name = 'MediaHolder';

	private static $db = array(
		'URLFormatting' => "Enum('y/MM/dd/, y/MM/, y/, -', 'y/MM/dd/')"
	);

	private static $has_one = array(
		'MediaType' => MediaType::class
	);

	private static $allowed_children = array(
		MediaHolder::class,
		MediaPage::class
	);

	private static $default_child = MediaPage::class;

	private static $description = '<strong>Holds:</strong> Blogs, Events, News, Publications <strong>or Custom Media</strong>';

	private static $icon = 'nglasl/silverstripe-mediawesome: client/images/holder.png';

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Display the media type as read only if media page children exist.

		($this->getMediaPageChildren()->exists() && $this->MediaType()->exists()) ?
			$fields->addFieldToTab('Root.Main', ReadonlyField::create(
				'Media',
				'Media Type',
				$this->MediaType()->Title
			), 'Title') :
			$fields->addFieldToTab('Root.Main', DropdownField::create(
				'MediaTypeID',
				'Media Type',
				MediaType::get()->map()->toArray()
			), 'Title');

		// Allow customisation of the media URL format.

		$formats = array(
			'y/MM/dd/' => 'year/month/day/media',
			'y/MM/' => 'year/month/media',
			'y/' => 'year/media',
			'-' => 'media'
		);
		$fields->insertBefore(DropdownField::create(
			'URLFormatting',
			'URL Formatting',
			$formats
		)->setDescription('The <strong>media</strong> URL format'), 'Content');

		// Allow customisation of media types, depending on the current CMS user permissions.

		$fields->findOrMakeTab('Root.ManageMedia.TypesAttributes', 'Types and Attributes');
		$fields->findOrMakeTab('Root.ManageMedia')->setTitle('Manage ALL Media');
		$fields->addFieldToTab('Root.ManageMedia.TypesAttributes', GridField::create(
			'TypesAttributes',
			'Types and Attributes',
			MediaType::get(),
			GridFieldConfig_RecordEditor::create()
		)->setModelClass(MediaType::class));

		// Allow customisation of media categories and tags.

		$fields->findOrMakeTab('Root.ManageMedia.CategoriesTags', 'Categories and Tags');
		$fields->addFieldToTab('Root.ManageMedia.CategoriesTags', GridField::create(
			'CategoriesTags',
			'Categories and Tags',
			MediaTag::get(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType(GridFieldDeleteAction::class)
		)->setModelClass(MediaTag::class));

		// Allow extension customisation.

		$this->extend('updateMediaHolderCMSFields', $fields);
		return $fields;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Apply the first media type by default.

		if(!$this->MediaTypeID) {
			$existing = MediaType::get()->first();
			$this->MediaTypeID = $existing->ID;
		}
	}

	/**
	 *	Retrieve any `MediaHolder` children of this `MediaHolder`.
	 *
	 *	@return DataList|MediaHolder[]
	 */

	public function getMediaHolderChildren() {

		return $this->AllChildren()->filter('ClassName', MediaHolder::class);
	}

	/**
	 *	Retrieve any `MediaPage` children of this `MediaHolder`.
	 *
	 *	@return DataList|MediaPage[]
	 */
	public function getMediaPageChildren() {

		return $this->AllChildren()->filter('ClassName', MediaPage::class);
	}

}
