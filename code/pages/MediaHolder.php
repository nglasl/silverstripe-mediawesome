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

	private static $allowed_actions = array(
		'dateFilterForm',
		'dateFilter',
		'clearFilter'
	);

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
		$from = $this->getRequest()->getVar('from');
		$tag = $this->getRequest()->getVar('tag');

		// apply the applicable filters which have been selected

		$children = MediaPage::get()->where('ParentID = ' . Convert::raw2sql($this->data()->ID));
		if($from) {
			$children = $children->where("Date >= '" . Convert::raw2sql($from) . " 00:00:00'");
		}
		if($tag) {
			$children = $children->filter('Tags.Title:ExactMatch', $tag);
		}
		return PaginatedList::create(
			$children->sort(Convert::raw2sql($sort) . ' ' . Convert::raw2sql($order)),
			$this->getRequest()
		)->setPageLength($limit);
	}

	public function dateFilterForm() {

		// display the form to allow filtering from a specified date

		$children = MediaPage::get()->where('ParentID = ' . Convert::raw2sql($this->data()->ID));
		$form = Form::create(
			$this,
			'dateFilterForm',
			FieldList::create(
				DateField::create(
					'from',
					''
				)->setConfig('showcalendar', true)->setConfig('min', $children->min('Date'))->setConfig('max', $children->max('Date'))->setAttribute('placeholder', 'From'),
				HiddenField::create(
					'tag'
				)
			),
			FieldList::create(
				FormAction::create(
					'dateFilter',
					'Filter'
				),
				FormAction::create(
					'clearFilter',
					'Clear'
				)
			)
		);

		// if there is an existing filter, display this in the form

		$form->setFormMethod('get');
		$form->loadDataFrom($this->getRequest()->getVars());

		// remove validation if a clear has been triggered

		if($this->getRequest()->getVar('action_clearFilter')) {
			$form->unsetValidator();
		}
		return $form;
	}

	public function dateFilter() {

		// apply the from filter, keeping the set tag filter

		$from = $this->getRequest()->getVar('from');
		$tag = $this->getRequest()->getVar('tag');
		$link = $this->AbsoluteLink();
		$separator = '?';
		if($from) {
			$parser = new DateTime($from);
			$link = HTTP::setGetVar('from', $parser->Format('Y-m-d'), $link, $separator);
			$separator = '&';
		}
		if($tag) {
			$link = HTTP::setGetVar('tag', $tag, $link, $separator);
		}
		$this->redirect($link);
	}

	public function clearFilter() {

		// reset the form filter, keeping any remaining tag filters applied

		$tag = $this->getRequest()->getVar('tag');
		$link = $tag ? HTTP::setGetVar('tag', $tag, $this->AbsoluteLink(), '?') : $this->AbsoluteLink();
		$this->redirect($link);
	}

}
