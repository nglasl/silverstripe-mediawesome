<?php

class MediaHolder extends Page {

	private static $icon = 'silverstripe-media/images/MediaHolder.png';

	private static $description = 'HOLDER FOR: News Page, Event, Publication, Media Release, Speech, Blog';

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
		if(count($this->AllChildren())) {
			$fields->addFieldToTab('Root.Main', ReadonlyField::create('Media', 'Media Type', $this->MediaType()->Title), 'Title');
		}
		else {
			$fields->addFieldToTab('Root.Main', DropdownField::create('MediaTypeID', 'Media Type', MediaType::get()->map()), 'Title');
		}
		$fields->addFieldToTab('Root.MediaTypes', $gridfield = GridField::create('MediaTypes', 'Media Types', MediaType::get()->exclude(array('Title' => 'MediaHolder')), GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')));
		return $fields;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		if(($this->MediaTypeID === 0) || is_null($this->MediaTypeID)) {
			$this->MediaTypeID = MediaType::get_one('MediaType')->ID;
		}
	}

}

class MediaHolder_Controller extends Page_Controller {

}
