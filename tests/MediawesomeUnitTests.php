<?php

/**
 *	The mediawesome specific unit testing.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediawesomeUnitTests extends SapphireTest {

	protected $usesDatabase = true;

	protected $requireDefaultRecordsFrom = array(
		'MediaType'
	);

	public function testMediaAttributes() {

		// Instantiate some media pages with a random type.

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
		$second = MediaPage::create(
			array(
				'Title' => 'Second',
				'ParentID' => $holder->ID
			)
		);
		$second->writeToStage('Stage');
		$second->publish('Stage', 'Live');

		// Determine whether the media attributes line up.

		foreach($first->MediaAttributes() as $attribute) {
			if($attribute->LinkID === -1) {

				// This is the master attribute.

				$master = $attribute;
			}
			else {

				// Determine the master attribute for comparison.

				$master = MediaAttribute::get()->byID($attribute->LinkID);
				$this->assertEquals($attribute->Title, $master->Title);
			}

			// Update the master attribute.

			$master->Title = 'Changed';
			$master->write();

			// Determine whether this change is reflected by the first page.

			$this->assertEquals($attribute->Title, $master->Title);

			// This only needs to be done once.

			break;
		}

		// Determine whether this change is reflected by the second page.

		foreach($second->MediaAttributes() as $attribute) {
			if($attribute->LinkID === $master->ID) {
				$this->assertEquals($attribute->Title, $master->Title);

				// This is now done.

				break;
			}
		}

		// Determine whether this change is reflected by a new media page.

		$third = MediaPage::create(
			array(
				'Title' => 'Third',
				'ParentID' => $holder->ID
			)
		);
		$third->writeToStage('Stage');
		$third->publish('Stage', 'Live');
		foreach($third->MediaAttributes() as $attribute) {
			if($attribute->LinkID === $master->ID) {
				$this->assertEquals($attribute->Title, $master->Title);

				// This is now done.

				break;
			}
		}
	}

}
