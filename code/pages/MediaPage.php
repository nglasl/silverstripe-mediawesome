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

		$parent = $this->getParent();
		if($parent) {
			$type = $this->getParent()->MediaType();

			if($type->exists()) {
				$this->MediaTypeID = $type->ID;
			}

			$type = $type->exists() ? $type->Title : null;

			$combinedDefaults = array();
			foreach(self::$custom_defaults as $key => $default) {
				if(!array_key_exists($key, self::$page_defaults)) {
					$combinedDefaults[$key] = $default;
				}
			}
			$combinedDefaults = array_merge(self::$page_defaults, $combinedDefaults);

			if(!$this->MediaAttributes()->exists()) {

				// grab updated titles if they exist
				$atts = MediaAttribute::get()->innerJoin('MediaPage', 'MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($type) . "'");
				if($atts->first()) {
					//avoid duplicates
					$titles = array();
					foreach($atts as $att) {
						if(!in_array($att->Title, $titles)) {
							$existing = MediaAttribute::get()->innerJoin('MediaPage', 'MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($type) . "' AND LinkID <> -1 AND MediaAttribute.Title = '" . $att->Title . "'")->first();
							$new = MediaAttribute::create();
							$new->LinkID = $existing ? $existing->LinkID : -1;
							$new->Title = $att->Title;
							$new->MediaPageID = $this->ID;
							$this->MediaAttributes()->add($new);
							$new->write();
							$titles[] = $att->Title;
						}
					}
				}
				else if(isset($combinedDefaults[$type])) {
					foreach($combinedDefaults[$type] as $attribute) {
						$new = MediaAttribute::create();
						$new->write();
						$new->Title = $attribute;
						$new->MediaPageID = $this->ID;
						$this->MediaAttributes()->add($new);
						$new->LinkID = $new->ID;
						$new->write();
					}
				}
			}
		}
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', ReadonlyField::create('Type', 'Type', $this->MediaType()->Title), 'Title');
		$fields->addFieldToTab('Root.Main', TextField::create('External', 'External Link'), 'Title');

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

		// add tabs for attachments and images

		$fields->addFieldToTab('Root.Attachments', UploadField::create('Attachments'));
		$fields->addFieldToTab('Root.Images', UploadField::create('Images'));

		return $fields;
	}

}

class MediaPage_Controller extends Page_Controller {

}
