<?php

/**
 *	The mediawesome specific functional testing.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediawesomeFunctionalTests extends FunctionalTest {

	protected $usesDatabase = true;

	protected $requireDefaultRecordsFrom = array(
		'MediaPage'
	);

	public function testURLs() {

		// Instantiate a media page with a random type.

		$holder = MediaHolder::create(
			array(
				'Title' => 'Holder',
				'MediaTypeID' => MediaType::get()->first()->ID
			)
		);
		$holder->writeToStage('Stage');
		$holder->publish('Stage', 'Live');
		$first = MediaPage::create(
			array(
				'Title' => 'First',
				'ParentID' => $holder->ID
			)
		);
		$first->writeToStage('Stage');
		$first->publish('Stage', 'Live');

		// Determine whether the page is accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);

		// Update the URL format.

		$holder->URLFormatting = 'Y/m/d/';
		$holder->writeToStage('Stage');
		$holder->publish('Stage', 'Live');

		// This should match "holder/year/month/day/media".

		$this->assertEquals(count(explode('/', trim($first->Link(), '/'))), 5);

		// Determine whether the page remains accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);
	}

}
