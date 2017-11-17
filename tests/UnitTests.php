<?php

namespace nglasl\mediawesome\tests;

use nglasl\mediawesome\MediaAttribute;
use nglasl\mediawesome\MediaHolder;
use nglasl\mediawesome\MediaPage;
use nglasl\mediawesome\MediaType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 *	The mediawesome specific unit testing.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class UnitTests extends SapphireTest {

	protected $usesDatabase = true;

	protected $requireDefaultRecordsFrom = array(
		MediaPage::class
	);

	public function testMediaAttributes() {

		// Instantiate some media pages with a random type.

		$type = MediaType::get()->first();
		$holder = MediaHolder::create(
			array(
				'Title' => 'Holder',
				'MediaTypeID' => $type->ID
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
		$second = MediaPage::create(
			array(
				'Title' => 'Second',
				'ParentID' => $holder->ID
			)
		);
		$second->writeToStage('Stage');
		$second->publishRecursive();

		// Determine whether a media page has the respective media attributes.

		$attribute = $first->MediaAttributes()->first();
		$expected = $type->MediaAttributes()->first();
		$this->assertEquals($attribute->ID, $expected->ID);
		$this->assertEquals($attribute->getJoin()->Content, null);

		// Update the media attribute content.

		$first->MediaAttributes()->add($attribute, array(
			'Content' => 'Changed'
		));

		// Determine whether this change is reflected by the first page.

		$attribute = $first->MediaAttributes()->first();
		$this->assertEquals($attribute->getJoin()->Content, 'Changed');

		// Confirm this change is not reflected by the second page.

		$attribute = $second->MediaAttributes()->first();
		$this->assertEquals($attribute->ID, $expected->ID);
		$this->assertEquals($attribute->getJoin()->Content, null);

		// The attributes are versioned, so make sure this change wasn't published.

		Versioned::set_stage('Live');
		$first = MediaPage::get()->byID($first->ID);
		$attribute = $first->MediaAttributes()->first();
		$this->assertEquals($attribute->getJoin()->Content, null);
		$first->publishRecursive();

		// Confirm this change is now published.

		$attribute = $first->MediaAttributes()->first();
		$this->assertEquals($attribute->getJoin()->Content, 'Changed');

		// Determine whether a new media attribute appears on the page.

		$count = $first->MediaAttributes()->count();
		$new = MediaAttribute::create(
			array(
				'Title' => 'New',
				'MediaTypeID' => $type->ID
			)
		);
		$new->write();
		$this->assertEquals($first->MediaAttributes()->count(), $count + 1);
		Versioned::set_stage('Stage');
	}

}
