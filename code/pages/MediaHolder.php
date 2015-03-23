<?php

/**
 *	Displays media holder/page children, with optional date/tag filters.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MediaHolder extends Page {

	private static $has_one = array(
		'MediaType' => 'MediaType'
	);

	private static $allowed_children = array(
		'MediaHolder',
		'MediaPage'
	);

	private static $default_child = 'MediaPage';

	private static $description = '<strong>Holds:</strong> Blogs, Events, News, Publications <strong>or Custom Media</strong>';

	/**
	 *	Allow selection and customisation of CMS media types/tags.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Display the media type as read only if media page children exist.

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

		// Allow customisation of media types, depending on the current CMS user permissions.

		$fields->findOrMakeTab('Root.ManageMedia.TypesAttributes', 'Types and Attributes');
		$fields->findOrMakeTab('Root.ManageMedia')->setTitle('Manage ALL Media');
		$fields->addFieldToTab('Root.ManageMedia.TypesAttributes', GridField::create(
			'TypesAttributes',
			'Types and Attributes',
			MediaType::get(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')
		)->setModelClass('MediaType'));

		// Allow customisation of media categories and tags.

		$fields->findOrMakeTab('Root.ManageMedia.CategoriesTags', 'Categories and Tags');
		$fields->addFieldToTab('Root.ManageMedia.CategoriesTags', GridField::create(
			'CategoriesTags',
			'Categories and Tags',
			MediaTag::get(),
			GridFieldConfig_RecordEditor::create()->removeComponentsByType('GridFieldDeleteAction')
		)->setModelClass('MediaTag'));

		// Allow extension customisation.

		$this->extend('updateMediaHolderCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Retrieve any media holder children.
	 *
	 *	@return data list
	 */

	public function getCheckMediaHolder() {

		return $this->AllChildren()->where("ClassName = 'MediaHolder'");
	}

}

class MediaHolder_Controller extends Page_Controller {

	private static $allowed_actions = array(
		'getDateFilterForm',
		'dateFilter',
		'clearFilter'
	);

	/**
	 *	Determine the template for this media holder.
	 */

	public function index() {

		// Use a custom media type holder template if one exists.

		$type = $this->data()->MediaType();
		$templates = array();
		if($type->exists()) {
			$templates[] = "{$this->data()->ClassName}_" . str_replace(' ', '', $type->Title);
		}
		$templates[] = $this->data()->ClassName;
		$templates[] = 'Page';
		$this->extend('updateTemplates', $templates);
		return $this->renderWith($templates);
	}

	/**
	 *	Retrieve a paginated list of media holder/page children for your template, with optional date/tag filters parsed from the GET request.
	 *
	 *	@parameter/@URLfilter <{MEDIA_PER_PAGE}> integer
	 *	@parameter/@URLfilter <{SORT_FIELD}> string
	 *	@parameter/@URLfilter <{SORT_ORDER}> string
	 *	@URLfilter <{FROM_DATE}> date
	 *	@URLfilter <{TAG_FILTER}> string
	 *	@return paginated list
	 */

	public function getPaginatedChildren($limit = 5, $sort = 'Date', $order = 'DESC') {

		// Retrieve custom request filters.

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
		$category = $this->getRequest()->getVar('category');
		$tag = $this->getRequest()->getVar('tag');

		// Apply custom request filters to media page children.

		$children = MediaPage::get()->where('ParentID = ' . (int)$this->data()->ID);
		if($from) {
			$children = $children->where("Date >= '" . Convert::raw2sql("{$from} 00:00:00") . "'");
		}
		if($category) {
			$children = $children->filter('Categories.Title:ExactMatch', $category);
		}
		if($tag) {
			$children = $children->filter('Tags.Title:ExactMatch', $tag);
		}

		// Allow extension customisation.

		$this->extend('updatePaginatedChildren', $children);
		return PaginatedList::create(
			$children->sort(Convert::raw2sql($sort) . ' ' . Convert::raw2sql($order)),
			$this->getRequest()
		)->setPageLength($limit);
	}

	/**
	 *	Retrieve a paginated list of media holder/page children for your template, with optional date/tag filters parsed from the GET request.
	 *
	 *	@parameter/@URLfilter <{MEDIA_PER_PAGE}> integer
	 *	@parameter/@URLfilter <{SORT_FIELD}> string
	 *	@parameter/@URLfilter <{SORT_ORDER}> string
	 *	@URLfilter <{FROM_DATE}> date
	 *	@URLfilter <{TAG_FILTER}> string
	 *	@return paginated list
	 */

	public function PaginatedChildren($limit = 5, $sort = 'Date', $order = 'DESC') {

		// This provides consistency when it comes to defining parameters from the template.

		return $this->getPaginatedChildren($limit, $sort, $order);
	}

	/**
	 *	Retrieve a simple date filter form.
	 *
	 *	@return form
	 */

	public function getDateFilterForm() {

		// Display a form that allows filtering from a specified date.

		$children = MediaPage::get()->where('ParentID = ' . (int)$this->data()->ID);
		$form = Form::create(
			$this,
			'getDateFilterForm',
			FieldList::create(
				DateField::create(
					'from',
					''
				)->setConfig('showcalendar', true)->setConfig('min', $children->min('Date'))->setConfig('max', $children->max('Date'))->setAttribute('placeholder', 'From'),
				HiddenField::create(
					'category'
				),
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
		$form->setFormMethod('get');

		// Display existing request filters.

		$form->loadDataFrom($this->getRequest()->getVars());

		// Remove validation if clear has been triggered.

		if($this->getRequest()->getVar('action_clearFilter')) {
			$form->unsetValidator();
		}

		// Allow extension customisation.

		$this->extend('updateFilterForm', $form);
		return $form;
	}

	/**
	 *	Request media page children from the filtered date.
	 */

	public function dateFilter() {

		// Apply the from date filter.

		$from = $this->getRequest()->getVar('from');
		$link = $this->AbsoluteLink();
		$separator = '?';
		if($from) {
			$parser = new DateTime($from);
			$link = HTTP::setGetVar('from', $parser->Format('Y-m-d'), $link, $separator);
			$separator = '&';
		}

		// Preserve the category/tag filters if they exist.

		$category = $this->getRequest()->getVar('category');
		$tag = $this->getRequest()->getVar('tag');
		if($category) {
			$link = HTTP::setGetVar('category', $category, $link, $separator);
			$separator = '&';
		}
		if($tag) {
			$link = HTTP::setGetVar('tag', $tag, $link, $separator);
		}

		// Allow extension customisation.

		$this->extend('updateFilter', $link);

		// Request the filtered paginated children.

		return $this->redirect($link);
	}

	/**
	 *	Request all media page children.
	 */

	public function clearFilter() {

		// Clear the from date filter.

		$link = $this->AbsoluteLink();
		$separator = '?';

		// Preserve the category/tag filters if they exist.

		$category = $this->getRequest()->getVar('category');
		$tag = $this->getRequest()->getVar('tag');
		if($category) {
			$link = HTTP::setGetVar('category', $category, $link, $separator);
			$separator = '&';
		}
		if($tag) {
			$link = HTTP::setGetVar('tag', $tag, $link, $separator);
		}

		// Request the paginated children.

		return $this->redirect($link);
	}

}
