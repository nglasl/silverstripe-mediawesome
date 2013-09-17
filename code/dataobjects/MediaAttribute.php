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

	private static $flag = false;

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->removeByName('Content');
		$fields->removeByName('LinkID');
		$fields->removeByName('MediaPageID');
		return $fields;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$params = Controller::curr()->getRequest()->requestVars();
		$url = $params['url'];
		$matches = array();
		$result = preg_match('#MediaTypes/item/[0-9]*/#', $url, $matches);
		if($result) {
			$ID = preg_replace('#[^0-9]#', '', $matches[0]);
			$pages = MediaPage::get()->innerJoin('MediaType', 'MediaTypeID = MediaType.ID')->filter('MediaType.ID', $ID);
			if(($this->MediaPageID === 0) || (is_null($this->MediaPageID))) {
				foreach($pages as $key => $page) {
					if($key === 0) {
						$this->MediaPageID = $page->ID;
						$this->LinkID = -1;
						self::$flag = true;
						$page->MediaAttributes()->add($this);
					}
					else {
						$new = MediaAttribute::create();
						$new->Title = $this->Title;
						$new->MediaPageID = $page->ID;
						$new->LinkID = $this->ID;
						$page->MediaAttributes()->add($new);
						$new->write();
					}
				}
			}
			else {
				if(!self::$flag) {
					foreach($pages as $page) {
						foreach($page->MediaAttributes() as $attribute) {
							if($attribute->LinkID == $this->ID) {
								$attribute->Title = $this->Title;
								self::$flag = true;
								$attribute->write();
							}
						}
					}
					self::$flag = false;
				}
			}
		}
		
	}

}
