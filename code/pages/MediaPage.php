<?php

class MediaPage extends Page {

	private static $icon = 'silverstripe-media/images/page.png';

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

	private static $pageDefaults = array(
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

	private static $customDefaults = array();

	public static function addDefaults($objects) {

		// merge nested array

		if(is_array($objects)) {

			// make sure we don't have an invalid entry
			foreach($objects as $test) {
				if(!is_array($test)) {
					return;
				}
			}

			$pages = array();
			$merge = array();
			foreach($objects as $page => $attribute) {
				if(!in_array($page, $pages) && !array_key_exists($page, self::$customDefaults) && ($page !== 'MediaHolder')) {
					$pages[] = $page;
					$merge[$page] = $attribute;
				}
			}
			self::$customDefaults = array_merge(self::$customDefaults, $merge);

			// add these new media types without requiring additional configuration
			MediaType::addDefaults($pages);
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
			foreach(self::$customDefaults as $key => $default) {
				if(!array_key_exists($key, self::$pageDefaults)) {
					$combinedDefaults[$key] = $default;
				}
			}
			$combinedDefaults = array_merge(self::$pageDefaults, $combinedDefaults);

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

		// add tabs for attachments and images

		$fields->addFieldToTab('Root.Attachments', UploadField::create('Attachments'));
		$fields->addFieldToTab('Root.Images', UploadField::create('Images'));

		return $fields;
	}

}

class MediaPage_Controller extends Page_Controller {

}
