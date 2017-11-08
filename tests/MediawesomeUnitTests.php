<?php

/**
 *	The mediawesome specific unit testing.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediawesomeUnitTests extends SapphireTest {

	protected $usesDatabase = true;

	protected $requireDefaultRecordsFrom = array(
		'MediaPage'
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
		$holder->publish('Stage', 'Live');
		$first = MediaPage::create(
			array(
				'Title' => 'First',
				'ParentID' => $holder->ID
			)
		);
		$first->writeToStage('Stage');
		$first->publish('Stage', 'Live');
		$second = MediaPage::create(
			array(
				'Title' => 'Second',
				'ParentID' => $holder->ID
			)
		);
		$second->writeToStage('Stage');
		$second->publish('Stage', 'Live');

		// Determine whether a media page has the respective media attributes.

		$attribute = $first->MediaAttributes()->first();
		$expected = $type->MediaAttributes()->first();
		$this->assertEquals($attribute->ID, $expected->ID);
		$this->assertEquals($attribute->Content, null);

		// Update the media attribute content.

		$first->MediaAttributes()->add($attribute, array(
			'Content' => 'Changed'
		));

		// Determine whether this change is reflected by the first page.

		$attribute = $first->MediaAttributes()->first();
		$this->assertEquals($attribute->Content, 'Changed');

		// Confirm this change is not reflected by the second page.

		$attribute = $second->MediaAttributes()->first();
		$this->assertEquals($attribute->ID, $expected->ID);
		$this->assertEquals($attribute->Content, null);
	}

}
