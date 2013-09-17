<?php

class MediaHolder extends Page {

	private static $description = '<strong>Holds:</strong> Blogs, Events, Media Releases, News, Publications, Speeches <strong>or Custom Media</strong>';

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $allowed_children = array(
		'MediaHolder',
		'MediaPage'
	);

	private static $default_child = 'MediaPage';

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// make the type read only if a child exists

		($this->allChildren()->exists() && $this->MediaType()) ?
			$fields->addFieldToTab('Root.Main', ReadonlyField::create('Media', 'Media Type', $this->MediaType()->Title), 'Title') :
			$fields->addFieldToTab('Root.Main', DropdownField::create('MediaTypeID', 'Media Type', MediaType::get()->map()), 'Title');

		// allow addition of custom media types

		$fields->addFieldToTab('Root.MediaTypes', GridField::create('MediaTypes', 'Media Types', MediaType::get(), GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')));
		return $fields;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// make sure the media holder has a type if media pages are to be created under it.

		if(is_null($this->MediaTypeID) || ($this->MediaTypeID === 0)) {
			$this->MediaTypeID = MediaType::get_one('MediaType')->ID;
		}
	}

}

class MediaHolder_Controller extends Page_Controller {

}
