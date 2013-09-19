<?php

class MediaHolder extends Page {

	private static $description = '<strong>Holds:</strong> Blogs, Events, Media Releases, News, Publications, Speeches <strong>or Custom Media</strong>';

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $allowed_children = array(
		'MediaHolder',
		'MediaPage'
	);

	private static $default_child = 'MediaPage';

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// make the type read only if a child exists

		($this->allChildren()->where("ClassName != 'MediaHolder'")->exists() && $this->MediaType()->exists()) ?
			$fields->addFieldToTab('Root.Main', ReadonlyField::create(
				'Media',
				'Media Type',
				$this->MediaType()->Title
			), 'Title') :
			$fields->addFieldToTab('Root.Main', DropdownField::create(
				'MediaTypeID',
				'Media Type',
				array_merge(array(0 => ''), MediaType::get()->map()->toArray())
			), 'Title');

		// allow addition of custom media types

		$fields->addFieldToTab('Root.MediaTypes', GridField::create(
			'MediaTypes',
			'Media Types',
			MediaType::get(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')
		)->setModelClass('MediaType'));
		return $fields;
	}

	// check if there is another media holder within this media holder

	public function checkMediaHolder() {
		return $this->allChildren()->where("ClassName = 'MediaHolder'");
	}

}

class MediaHolder_Controller extends Page_Controller {

	// retrieve a paginated list of children for the template

	public function getPaginatedChildren($limit = 5) {
		return PaginatedList::create($this->data()->allChildren()->reverse(), $this->getRequest())->setPageLength($limit);
	}

}
