<?php

class MediaAttribute extends DataObject {

	private static $db = array(
		'OriginalTitle' => 'VARCHAR(255)',
		'Title' => 'VARCHAR(255)',
		'Content' => 'HTMLTEXT',
		'LinkID' => 'INT'
	);

	private static $has_one = array(
		'MediaPage' => 'MediaPage'
	);

	// used to avoid infinite recursion when writing inside the onbeforewrite

	private static $writeFlag = false;

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// we only want to allow change of the title which will be globally applied to all attributes

		$fields->removeByName('OriginalTitle');
		$fields->removeByName('Content');
		$fields->removeByName('LinkID');
		$fields->removeByName('MediaPageID');

		// allow customisation of the cms fields displayed

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// set the original title for future reference if it is changed

		if(is_null($this->OriginalTitle)) {
			$this->OriginalTitle = $this->Title;
		}

		// grab the media type id which will be used to update all attributes against this type

		$params = Controller::curr()->getRequest()->requestVars();
		$url = $params['url'];
		$matches = array();
		$result = preg_match('#MediaTypes/item/[0-9]*/#', $url, $matches);
		if($result) {
			$ID = preg_replace('#[^0-9]#', '', $matches[0]);
			$pages = MediaPage::get()->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where('MediaType.ID = ' . Convert::raw2sql($ID));

			// for a new attribute

			if($pages && (is_null($this->MediaPageID) || ($this->MediaPageID === 0))) {
				foreach($pages as $key => $page) {
					if($key === 0) {

						// set the current attribute fields since this is currently being written

						self::$writeFlag = true;
						$this->LinkID = -1;
						$this->MediaPageID = $page->ID;
						$page->MediaAttributes()->add($this);
					}
					else {

						// create a new attribute matching the instantiated one, and assign it to each media page of the corresponding type

						$new = MediaAttribute::create();
						$new->Title = $this->Title;
						$new->LinkID = $this->ID;
						$new->MediaPageID = $page->ID;
						$page->MediaAttributes()->add($new);
						$new->write();
					}
				}
			}
			else if($pages) {

				// the write flag is used here to avoid infinite recursion

				if(!self::$writeFlag) {
					foreach($pages as $page) {
						foreach($page->MediaAttributes() as $attribute) {

							// link each attribute against the owner attribute for title edit purposes

							if(($attribute->LinkID == $this->ID) && ($attribute->Title !== $this->Title)) {
								self::$writeFlag = true;
								$attribute->Title = $this->Title;
								$attribute->write();
							}
						}
					}
					self::$writeFlag = false;
				}
			}
		}
	}

	public function validate() {
		$result = parent::validate();

		// make sure a new media attribute has been given a title

		$this->Title ? $result->valid() : $result->error('Title required.');

		// allow validation extension

		$this->extend('validate', $result);

		return $result;
	}

	// in case getattribute is called inside a template without accessing variables directly

	public function forTemplate() {
		return "{$this->Title}: {$this->Content}";
	}

	// prevent deletion of media attributes

	public function canDelete($member = null) {
		return false;
	}

	// allow a content author access to manage these media attributes

	public function canView($member = null) {
		return Permission::check('SITETREE_REORGANISE', 'any', $member);
	}

	public function canEdit($member = null) {
		return Permission::check('SITETREE_REORGANISE', 'any', $member);
	}

	public function canCreate($member = null) {
		return Permission::check('SITETREE_REORGANISE', 'any', $member);
	}

}
