<?php

/**
 *	Mediawesome CMS media type attribute.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaAttribute extends DataObject {

	private static $db = array(
		'OriginalTitle' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'Content' => 'HTMLText',
		'LinkID' => 'Int'
	);

	private static $has_one = array(
		'MediaPage' => 'MediaPage'
	);

	/**
	 *	Flag a write occurrence to prevent infinite recursion.
	 */

	private static $writeFlag = false;

	/**
	 *	Allow access for CMS users viewing media type attributes.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canView($member = null) {
		return true;
	}

	/**
	 *	Determine access for the current CMS user editing media type attributes.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canEdit($member = null) {
		return $this->checkPermissions($member);
	}

	/**
	 *	Determine access for the current CMS user editing media type attributes.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canCreate($member = null) {
		return $this->checkPermissions($member);
	}

	/**
	 *	Restrict access for CMS users deleting media type attributes.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canDelete($member = null) {
		return false;
	}

	/**
	 *	Determine access for the current CMS user from the site configuration media customisation permissions.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function checkPermissions($member = null) {
		$configuration = SiteConfig::current_site_config();
		return Permission::check($configuration->MediaAccess, 'any', $member);
	}

	/**
	 *	Display appropriate CMS media type attribute fields.
	 */

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

	/**
	 *	Confirm that the current media type attribute is valid.
	 */

	public function validate() {
		$result = parent::validate();

		// make sure a media attribute has been given a title

		$this->Title ? $result->valid() : $result->error('Title required!');

		// allow validation extension

		$this->extend('validate', $result);

		return $result;
	}

	/**
	 *	Assign this media type attribute to each media page of the respective type.
	 */

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

	/**
	 *	Permanently reference an attribute name, even if it has been changed through the CMS.
	 *
	 *	@return string
	 */

	public function templateClass() {
		return strtolower($this->OriginalTitle);
	}

	/**
	 *	The default rendition of an attribute object for templates.
	 *
	 *	@return string
	 */

	public function forTemplate() {
		return "{$this->Title}: {$this->Content}";
	}

}
