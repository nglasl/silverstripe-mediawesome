<?php

namespace nglasl\mediawesome;

use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;

/**
 *	This is essentially the versioned join between `MediaPage` and `MediaAttribute`, since each page will have different content for an attribute.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaPageAttribute extends DataObject {

	private static $table_name = 'MediaPageAttribute';

	private static $db = array(
		'Content' => 'HTMLText'
	);

	private static $has_one = array(
		'MediaPage' => MediaPage::class,
		'MediaAttribute' => MediaAttribute::class
	);

	private static $summary_fields = array(
		'Title',
		'Content'
	);

	public function canDelete($member = null) {

		return false;
	}

	public function getTitle() {

		return $this->MediaAttribute()->Title;
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->removeByName('MediaPageID');
		$fields->removeByName('MediaAttributeID');

		// Determine the field type.

		if(strrpos($this->getTitle(), 'Date')) {

			// The user expects this to be a date attribute.

			$fields->replaceField('Content', DateField::create(
				'Content'
			));
		}
		else {

			// This is most commonly a simple attribute, so a HTML field only complicates things for the user.

			$fields->replaceField('Content', TextareaField::create(
				'Content'
			));
		}

		// Allow extension customisation.

		$this->extend('updateMediaPageAttributeCMSFields', $fields);
		return $fields;
	}

}
