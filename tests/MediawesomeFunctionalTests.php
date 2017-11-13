<?php

use SilverStripe\Dev\FunctionalTest;

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
				'ClassName' => 'MediaHolder',
				'Title' => 'Holder',
				'URLFormatting' => 'y/MM/dd/',
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

		// This should match "holder/year/month/day/media".

		$this->assertEquals(count(explode('/', trim($first->Link(), '/'))), 5);

		// Determine whether the page is accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);

		// Update the URL format.

		$holder->URLFormatting = '-';
		$holder->writeToStage('Stage');
		$holder->publish('Stage', 'Live');

		// This should match "holder/media".

		$this->assertEquals(count(explode('/', trim($first->Link(), '/'))), 2);

		// Determine whether the page remains accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);
	}

}
