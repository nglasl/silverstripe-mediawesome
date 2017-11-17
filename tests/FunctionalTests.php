<?php

namespace nglasl\mediawesome\tests;

use nglasl\mediawesome\MediaHolder;
use nglasl\mediawesome\MediaPage;
use nglasl\mediawesome\MediaType;
use SilverStripe\Dev\FunctionalTest;

/**
 *	The mediawesome specific functional testing.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class FunctionalTests extends FunctionalTest {

	protected $usesDatabase = true;

	protected $requireDefaultRecordsFrom = array(
		MediaPage::class
	);

	public function testURLs() {

		$this->logInWithPermission();

		// Instantiate a media page with a random type.

		$holder = MediaHolder::create(
			array(
				'ClassName' => MediaHolder::class,
				'Title' => 'Holder',
				'URLFormatting' => 'y/MM/dd/',
				'MediaTypeID' => MediaType::get()->first()->ID
			)
		);
		$holder->writeToStage('Stage');
		$holder->publishRecursive();
		$first = MediaPage::create(
			array(
				'Title' => 'First',
				'ParentID' => $holder->ID
			)
		);
		$first->writeToStage('Stage');
		$first->publishRecursive();

		// This should match "holder/year/month/day/media".

		$this->assertEquals(count(explode('/', trim($first->Link(), '/'))), 5);

		// Determine whether the page is accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);

		// Update the URL format.

		$holder->URLFormatting = '-';
		$holder->writeToStage('Stage');
		$holder->publishRecursive();

		// This should match "holder/media".

		$this->assertEquals(count(explode('/', trim($first->Link(), '/'))), 2);

		// Determine whether the page remains accessible.

		$response = $this->get($first->Link());
		$this->assertEquals($response->getStatusCode(), 200);
	}

}
