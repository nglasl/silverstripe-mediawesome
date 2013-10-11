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

		($this->AllChildren()->where("ClassName != 'MediaHolder'")->exists() && $this->MediaType()->exists()) ?
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

		// allow addition of custom media tags

		$fields->addFieldToTab('Root.MediaTags', GridField::create(
			'MediaTags',
			'Media Tags',
			MediaTag::get(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')
		)->setModelClass('MediaTag'));

		// allow customisation of the cms fields displayed

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	// check if there is another media holder within this media holder

	public function checkMediaHolder() {
		return $this->AllChildren()->where("ClassName = 'MediaHolder'");
	}

}

class MediaHolder_Controller extends Page_Controller {

	public function index() {

		// if a custom template for the specific holder type has been defined, use this

		$type = $this->data()->MediaType();
		$templates = array();
		if($type->exists()) {
			$templates[] = "{$this->data()->ClassName}_" . str_replace(' ', '', $type->Title);
		}
		$templates[] = $this->data()->ClassName;
		$templates[] = 'Page';
		return $this->renderWith($templates);
	}

	// retrieve a paginated list of children for your template, with optional filters

	public function getPaginatedChildren($limit = 5, $sort = 'Date', $order = 'DESC') {

		// if a custom filter has occurred, these attributes will take precedence

		if($limitVar = $this->getRequest()->getVar('limit')) {
			$limit = $limitVar;
		}
		if($sortVar = $this->getRequest()->getVar('sort')) {
			$sort = $sortVar;
		}
		if($orderVar = $this->getRequest()->getVar('order')) {
			$order = $orderVar;
		}
		$tag = $this->getRequest()->getVar('tag');
		$for = $this->getRequest()->getVar('for');
		$from = $this->getRequest()->getVar('from');

		// apply the applicable filters which have been selected

		$children = MediaPage::get()->where('ParentID = ' . Convert::raw2sql($this->data()->ID));
		if($tag) {
			$children = $children->filter('Tags.Title:ExactMatch', $tag);
		}
		if($for) {
			$children = $children->where("Date = '" . Convert::raw2sql($for) . "'");
		}
		else if($from) {
			$children = $children->where("Date >= '" . Convert::raw2sql($from) . "'");
		}
		return PaginatedList::create(
			$children->sort(Convert::raw2sql($sort) . ' ' . Convert::raw2sql($order)),
			$this->getRequest()
		)->setPageLength($limit);
	}

}
