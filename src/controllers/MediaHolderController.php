<?php

namespace nglasl\mediawesome;

use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\HTTP;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\PaginatedList;

/**
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaHolderController extends \PageController {

	private static $allowed_actions = array(
		'handleURL',
		'getDateFilterForm',
		'dateFilter',
		'clearFilters'
	);

	/**
	 *	Determine the template for this media holder.
	 */

	public function index() {

		// Use a custom media type holder template if one exists.

		$type = $this->data()->MediaType();
		$templates = array();
		if($type->exists()) {
			$templates[] = 'MediaHolder_' . str_replace(' ', '', $type->Title);
		}
		$templates[] = 'MediaHolder';
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
	 *	@URLfilter <{CATEGORY_FILTER}> string
	 *	@URLfilter <{TAG_FILTER}> string
	 *	@return paginated list
	 */

	public function getPaginatedChildren($limit = 5, $sort = 'Date', $order = 'DESC') {

		// Retrieve custom request filters.

		$request = $this->getRequest();
		if($limitVar = $request->getVar('limit')) {
			$limit = ($limitVar > 100) ? 100 : $limitVar;
		}
		if($sortVar = $request->getVar('sort')) {
			$sort = $sortVar;
		}
		if($orderVar = $request->getVar('order')) {
			$order = $orderVar;
		}
		$from = $request->getVar('from');
		$category = $request->getVar('category');
		$tag = $request->getVar('tag');

		// Apply custom request filters to media page children.

		$children = MediaPage::get()->filter('ParentID', $this->data()->ID);

		// Validate the date request filter.

		if($from) {
			$valid = true;
			$date = array();
			foreach(explode('-', $from) as $segment) {
				if(!is_numeric($segment)) {
					$valid = false;
					break;
				}
				else {
					$date[] = str_pad($segment, 2, '0', STR_PAD_LEFT);
				}
			}
			if($valid) {

				// This is used to determine the direction to filter, so it makes sense from a user's perspective.

				if($order === 'DESC') {
					$date[count($date) - 1]++;
					$direction = '<';
				}
				else {
					$direction = '>=';
				}
				$from = implode('-', $date);
				$children = $children->where(array(
					"Date {$direction} ?" => $from
				));
			}
		}

		// Determine both category and tag result sets separately, since they both share a database table.

		$temporary = $children;
		if($category) {
			$children = $categoryChildren = $temporary->filter('Categories.Title', $category);
		}
		if($tag) {
			$children = $tagChildren = $temporary->filter('Tags.Title', $tag);
		}

		// Merge both category and tag result sets.

		if($category && $tag) {
			$intersection = array_uintersect($categoryChildren->toArray(), $tagChildren->toArray(), function($first, $second) {

				return $first->ID - $second->ID;
			});
			$children = ArrayList::create($intersection);
		}

		// Allow extension customisation.

		$this->extend('updatePaginatedChildren', $children);
		return PaginatedList::create(
			$children->sort(Convert::raw2sql($sort) . ' ' . Convert::raw2sql($order)),
			$request
		)->setPageLength($limit);
	}

	/**
	 *	Retrieve a paginated list of media holder/page children for your template, with optional date/tag filters parsed from the GET request.
	 *
	 *	@parameter/@URLfilter <{MEDIA_PER_PAGE}> integer
	 *	@parameter/@URLfilter <{SORT_FIELD}> string
	 *	@parameter/@URLfilter <{SORT_ORDER}> string
	 *	@URLfilter <{FROM_DATE}> date
	 *	@URLfilter <{CATEGORY_FILTER}> string
	 *	@URLfilter <{TAG_FILTER}> string
	 *	@return paginated list
	 */

	public function PaginatedChildren($limit = 5, $sort = 'Date', $order = 'DESC') {

		// This provides consistency when it comes to defining parameters from the template.

		return $this->getPaginatedChildren($limit, $sort, $order);
	}

	/**
	 *	Handle the current URL, parsing a year/month/day/media format, and directing towards any valid controller actions that may be defined.
	 *
	 *	@URLparameter <{YEAR}> integer
	 *	@URLparameter <{MONTH}> integer
	 *	@URLparameter <{DAY}> integer
	 *	@URLparameter <{MEDIA_URL_SEGMENT}> string
	 *	@return http response
	 */

	public function handleURL() {

		// Retrieve the formatted URL.

		$request = $this->getRequest();
		$URL = $request->param('URL');

		// Determine whether a controller action resolves.

		if($this->hasAction($URL) && $this->checkAccessAction($URL)) {
			$output = $this->$URL($request);

			// The current request URL has been successfully parsed.

			while(!$request->allParsed()) {
				$request->shift();
			}
			return $output;
		}
		else if(!is_numeric($URL)) {

			// Determine whether a media page child once existed, and redirect appropriately.

			$response = $this->resolveURL();
			if($response) {

				// The current request URL has been successfully parsed.

				while(!$request->allParsed()) {
					$request->shift();
				}
				return $response;
			}
			else {

				// The URL doesn't resolve.

				return $this->httpError(404);
			}
		}

		// Determine the formatted URL segments.

		$segments = array(
			$URL
		);
		$remaining = $request->remaining();
		if($remaining) {
			$remaining = explode('/', $remaining);

			// Determine the media page child to display.

			$child = null;
			$action = null;

			// Iterate the formatted URL segments.

			$iteration = 1;
			foreach($remaining as $segment) {
				if(is_null($action)) {

					// Update the current request.

					$request->shift();
					if($child) {

						// Determine whether a controller action has been defined.

						$action = $segment;
						break;
					}
					else if(!is_numeric($segment)) {
						if($iteration === 4) {

							// The remaining URL doesn't match the month/day/media format.

							return $this->httpError(404);
						}

						// Determine the media page child to display, using the URL segment and date.

						$children = MediaPage::get()->filter(array(
							'ParentID' => $this->data()->ID,
							'URLSegment' => $segment
						));
						if(!empty($segments)) {

							// Apply a partial match against the date, since the previous URL segments may only contain the year/month.

							$date = array();
							foreach($segments as $previous) {
								$date[] = str_pad($previous, 2, '0', STR_PAD_LEFT);
							}
							$children = $children->filter(array(
								'Date:StartsWith' => implode('-', $date)
							));
						}
						$child = $children->first();

						// Determine whether a media page child once existed, and redirect appropriately.

						if(is_null($child)) {
							$response = $this->resolveURL();
							if($response) {

								// The current request URL has been successfully parsed.

								while(!$request->allParsed()) {
									$request->shift();
								}
								return $response;
							}
							else {

								// The URL doesn't match the month/day/media format.

								return $this->httpError(404);
							}
						}
					}
				}
				$segments[] = $segment;
				$iteration++;
			}

			// Retrieve the media page child controller, and determine whether an action resolves.

			if($child) {
				$controller = ModelAsController::controller_for($child);

				// Determine whether a controller action resolves.

				if(is_null($action)) {
					return $controller;
				}
				else if($controller->hasAction($action) && $controller->checkAccessAction($action)) {
					$output = $controller->$action($request);

					// The current request URL has been successfully parsed.

					while(!$request->allParsed()) {
						$request->shift();
					}
					return $output;
				}
				else {

					// The controller action doesn't resolve.

					return $this->httpError(404);
				}
			}
		}

		// Retrieve the paginated children using the date filter segments.

		$newRequest = new HTTPRequest('GET', $this->Link(), array_merge($request->getVars(), array(
			'from' => implode('-', $segments)
		)));
		$newRequest->setSession($request->getSession());

		// The new request URL doesn't require parsing.

		while(!$newRequest->allParsed()) {
			$newRequest->shift();
		}

		// Handle the new request URL.

		return $this->handleRequest($newRequest);
	}

	/**
	 *	Determine whether a media page child once existed for the current request, and redirect appropriately.
	 *
	 *	@return http response
	 */

	private function resolveURL() {

		// Retrieve the current request URL segments.

		$request = $this->getRequest();
		$URL = $request->getURL();
		$holder = substr($URL, 0, strpos($URL, '/'));
		$page = substr($URL, strrpos($URL, '/') + 1);

		// Determine whether a media page child once existed.

		$resolution = self::find_old_page(array(
			$holder,
			$page
		));
		$comparison = trim($resolution, '/');

		// Make sure the current request URL doesn't match the resolution.

		if($resolution && ($page !== substr($comparison, strrpos($comparison, '/') + 1))) {

			// Retrieve the current request parameters.

			$parameters = $request->getVars();
			unset($parameters['url']);

			// Appropriately redirect towards the updated media page URL.

			$response = new HTTPResponse();
			return $response->redirect(self::join_links($resolution, !empty($parameters) ? '?' . http_build_query($parameters) : null), 301);
		}
		else {

			// The media page child doesn't resolve.

			return null;
		}
	}

	/**
	 *	Retrieve a simple date filter form.
	 *
	 *	@return form
	 */

	public function getDateFilterForm() {

		// Display a form that allows filtering from a specified date.

		$children = MediaPage::get()->filter('ParentID', $this->data()->ID);
		$form = Form::create(
			$this,
			'getDateFilterForm',
			FieldList::create(
				$date = DateField::create(
					'from',
					'From'
				)->setMinDate($children->min('Date'))->setMaxDate($children->max('Date')),
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
					'clearFilters',
					'Clear'
				)
			)
		);
		$form->setFormMethod('get');

		// Remove validation if clear has been triggered.

		$request = $this->getRequest();
		if($request->getVar('action_clearFilters')) {
			$form->unsetValidator();
		}

		// Allow extension customisation.

		$this->extend('updateFilterForm', $form);

		// Display existing request filters.

		$form->loadDataFrom($request->getVars());
		return $form;
	}

	/**
	 *	Request media page children from the filtered date.
	 */

	public function dateFilter() {

		// Apply the from date filter.

		$request = $this->getRequest();
		$from = $request->getVar('from');
		$link = $this->Link();
		$separator = '?';
		if($from) {

			// Determine the formatted URL to represent the request filter.

			$date = DBDate::create()->setValue($from);
			$link .= $date->Format('y/MM/dd/');
		}

		// Preserve the category/tag filters if they exist.

		$category = $request->getVar('category');
		$tag = $request->getVar('tag');
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

	public function clearFilters() {

		// Clear any custom request filters.

		return $this->redirect($this->Link());
	}

}
