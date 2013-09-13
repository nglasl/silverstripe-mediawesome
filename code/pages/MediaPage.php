<?php

class MediaPage extends Page {

	private static $icon = 'media/images/MediaPage.png';

	private static $description = 'News Page, Event, Publication, Media Release, Speech, Blog';

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
		'Attachments' => 'File',
		'Images' => 'Image'
	);

	private static $can_be_root = false;

	private static $allowed_children = "none";

	private static $default_parent = 'MediaHolder';

	//move defaults array to config
	public function onBeforeWrite() {
		parent::onBeforeWrite();

		foreach($this->record as $name => $value) {
			if(strrpos($name, 'MediaAttribute')) {
				$stuff = explode('_', $name);
				$ID = $stuff[0];
				$attribute = MediaAttribute::get_by_id('MediaAttribute', $ID);
				$attribute->Content = $value;
				$attribute->write();
			}
		}

		if(!isset($this->Date) || $this->Date === null) {
			$this->Date = SS_Datetime::now()->Format('Y-m-d 09:00:00');
		}

		$defaults = array(
			'NewsPage' => array(
				'Author'
			),
			'Event' => array(
				'Start Time',
				'End Time',
				'Location'
			),
			'Publication' => array(
				'Author'
			),
			'MediaRelease' => array(
				'Contact Name',
				'Contact Number'
			),
			'Speech' => array(
				'Speaker',
				'Location'
			),
			'Blog' => array(
				'Author'
			)
		);

		$parent = $this->getParent();
		if($parent) {
			$type = $this->getParent()->MediaType();

			if($type->exists()) {
				$this->MediaTypeID = $type->ID;
			}

			$type = $type->exists() ? $type->Title : null;
			if(!$this->MediaAttributes()->exists() && isset($defaults[$type])) {
				foreach($defaults[$type] as $attribute) {
					$new = MediaAttribute::create();
					$new->Title = $attribute;
					$new->MediaPageID = $this->ID;
					$this->MediaAttributes()->add($new);
					$new->write();
				}
			}
		}
	}

	//add tab for other fields such as attachments and images
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', ReadonlyField::create('Type', 'Type', $this->MediaType()->Title), 'Title');
		$fields->addFieldToTab('Root.Main', TextField::create('ExternalLink'), 'Title');

		$fields->addFieldToTab('Root.Main', $dateTimeField = new DatetimeField('Date'), 'Content');
		$dateTimeField->getDateField()->setConfig('showcalendar', true);

		$fields->addfieldToTab('Root.Main', $abstractField = new TextareaField('Abstract'), 'Content');
		$abstractField->setAttribute('maxlength', '160');
		$abstractField->setRightTitle('The abstract is used as a summary on the listing pages. It is limited to 160 characters.');
		$abstractField->setRows(6);

		if($this->MediaAttributes()->exists()) {
			foreach($this->MediaAttributes() as $attribute) {
				$fields->addFieldToTab('Root.Main', TextField::create("{$attribute->ID}_MediaAttribute", $attribute->Title, $attribute->Content), 'Title');
			}
		}

		return $fields;
	}

}

class MediaPage_Controller extends Page_Controller {

}
