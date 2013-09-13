<?php

class MediaType extends DataObject {

	private static $db = array(
		'Title' => 'VARCHAR(255)'
	);

	//move defaults array to config
	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// News Page, Event, Publication, Media Release, Speech, Blog, Media Holder.

		$defaults = array(
			'NewsPage',
			'Event',
			'Publication',
			'MediaRelease',
			'Speech',
			'Blog',
			'MediaHolder'
		);

		foreach($defaults as $default) {

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
			$titles = array();
			foreach($objects as $object) {
				if(!in_array($object->Title, $titles)) {
					$output->push($object);
					$titles[] = $object->Title;
				}
			}
			$fields->addFieldToTab('Root.AdditionalAttributes', $gridfield = GridField::create('AdditionalAttributes', 'Additional Attributes', $output, GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction'))->setModelClass('MediaAttribute'));
		}

		return $fields;
	}

	public function validate() {
		$result = parent::validate();
		$this->Title ? $result->valid() : $result->error('Pls give title');
		return $result;
	}

}
