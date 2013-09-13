<?php

class MediaAttribute extends DataObject {

	private static $db = array(
		'Title' => 'VARCHAR(255)',
		'Content' => 'HTMLText'
	);

	private static $has_one = array(
		'MediaPage' => 'MediaPage'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Content');
		$fields->removeByName('MediaPageID');
		return $fields;
	}

	//implement an update of attribute title to bulk update all attributes
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$params = Controller::curr()->getRequest()->requestVars();
		$url = $params['url'];
		$matches = array();
		$result = preg_match('#MediaTypes/item/[0-9]*/#', $url, $matches);
		if($result) {
			$ID = preg_replace('#[^0-9]#', '', $matches[0]);
			if(($this->MediaPageID === 0) || (is_null($this->MediaPageID))) {
				$pages = MediaPage::get()->innerJoin('MediaType', 'MediaTypeID = MediaType.ID')->filter('MediaType.ID', $ID);

				foreach($pages as $key => $page) {
					if($key === 0) {
						$this->MediaPageID = $page->ID;
						$page->MediaAttributes()->add($this);
					}
					else {
						$new = MediaAttribute::create();
						$new->Title = $this->Title;
						$new->MediaPageID = $page->ID;
						$page->MediaAttributes()->add($new);
						$new->write();
					}
				}
			}
		}
	}

}
