<?php

class MediaAttribute extends DataObject {

	private static $db = array(
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

		$fields->removeByName('Content');
		$fields->removeByName('LinkID');
		$fields->removeByName('MediaPageID');
		return $fields;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// grab the media type id which will be used to update all attributes against this type

		$params = Controller::curr()->getRequest()->requestVars();
		$url = $params['url'];
		$matches = array();
		$result = preg_match('#MediaTypes/item/[0-9]*/#', $url, $matches);
		if($result) {
			$ID = preg_replace('#[^0-9]#', '', $matches[0]);
			$pages = MediaPage::get()->innerJoin('MediaType', 'MediaPage.MediaTypeID = MediaType.ID')->where("MediaType.ID = " . Convert::raw2sql($ID));

			// for a new attribute

			if(($this->MediaPageID === 0) || (is_null($this->MediaPageID))) {
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
			else {

				// the write flag is used here to avoid infinite recursion

				if(!self::$writeFlag) {
					foreach($pages as $page) {
						foreach($page->MediaAttributes() as $attribute) {

							// link each attribute against the owner attribute for title edit purposes

							if($attribute->LinkID == $this->ID) {
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

}
