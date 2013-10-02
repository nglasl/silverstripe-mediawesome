<?php

/**
 *	Extension to configure custom media permissions.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class SiteConfigAccessExtension extends DataExtension {

	private static $db = array(
		'MediaAccess' => 'VARCHAR(255)'
	);

	public function updateCMSFields(FieldList $fields) {

		$permissions = array(
			'ADMIN' => 'Administrators',
			'SITETREE_REORGANISE' => 'Content Authors',
			'DEVELOPER' => 'Developers'
		);
		$fields->addFieldToTab('Root.Access', OptionsetField::create(
			'MediaAccess',
			'Who can customise media?',
			$permissions
		));
	}

}
