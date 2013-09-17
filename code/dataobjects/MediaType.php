<?php

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'VARCHAR(255)'
	);

	private static $page_defaults = array(
		'NewsPage',
		'Event',
		'Publication',
		'MediaRelease',
		'Speech',
		'Blog'
	);

	private static $custom_defaults = array(
	);

	public static function add_default($type) {

		//merge any new media type customisation

		self::$custom_defaults[] = $type;
	}

	public static function apply_required_extensions() {

		Object::add_extension('Page', 'PageChildrenExtension');
		Config::inst()->update('MediaHolder', 'icon', MEDIAWESOME_PATH . '/images/holder.png');
		Config::inst()->update('MediaPage', 'icon', MEDIAWESOME_PATH . '/images/page.png');
	}

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// News Page, Event, Publication, Media Release, Speech, Blog, Media Holder.

		$combinedDefaults = array_unique(array_merge(self::$page_defaults, self::$custom_defaults));
		foreach($combinedDefaults as $default) {

			// Create the default media page types.

			if(!MediaType::get_one('MediaType', "Title = '$default'")) {
				$type = MediaType::create();
				$type->Title = $default;
				$type->write();
				DB::alteration_message("Added Media Type $default", 'created');
			}
		}
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		if($this->Title) {
			$fields->replaceField('Title', ReadonlyField::create('Title'));
			$objects = MediaAttribute::get()->innerJoin('MediaPage', 'MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($this->Title) . "'");
			$output = ArrayList::create();
			//avoid duplicates
			$titles = array();
			foreach($objects as $object) {
				if(!in_array($object->Title, $titles)) {
					$output->push($object);
					$titles[] = $object->Title;
				}
			}
			if($this->canEdit()) {
				$fields->addFieldToTab('Root.AdditionalAttributes', $gridfield = GridField::create('AdditionalAttributes', 'Additional Attributes', $output, GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction'))->setModelClass('MediaAttribute'));
			}
			else {
				$fields->addFieldToTab('Root.Main', LiteralField::create('Notification',
					'<div>Additional Attributes will be available here once a Media Page has been created</div>'
				));
			}
		}

		return $fields;
	}

	public function validate() {
		$result = parent::validate();
		$this->Title ? $result->valid() : $result->error('Pls give title');
		return $result;
	}

	public function canEdit($member = null) {
		$params = Controller::curr()->getRequest()->requestVars();
		$url = $params['url'];
		$matches = array();
		$result = preg_match('#MediaTypes/item/new#', $url, $matches);
		if($result && Permission::check('ADMIN', 'any', $member)) {
			return true;
		}
		else {
			//only need pages?
			$objects = MediaAttribute::get()->innerJoin('MediaPage', 'MediaPageID = MediaPage.ID')->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($this->Title) . "'");
			$pages = MediaPage::get()->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.Title = '" . Convert::raw2sql($this->Title) . "'");
			$a = ($objects->first() || $pages->first()) ? true : false;
			return $a;
		}
	}

}
