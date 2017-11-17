<?php

namespace nglasl\mediawesome;

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
		'MediaAttribute.Title',
		'Content'
	);

	private static $field_labels = array(
		'MediaAttribute.Title' => 'Title'
	);

	public function canDelete($member = null) {

		return false;
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->removeByName('MediaPageID');
		$fields->removeByName('MediaAttributeID');

		// This is most commonly a simple attribute, so a HTML field only complicates things for the user.

		$fields->replaceField('Content', TextareaField::create(
			'Content'
		));

		// Allow extension customisation.

		$this->extend('updateMediaPageAttributeCMSFields', $fields);
		return $fields;
	}

}
