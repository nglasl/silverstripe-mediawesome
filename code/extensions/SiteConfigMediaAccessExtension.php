<?php

/**
 *	Extension to configure custom media permissions.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class SiteConfigMediaAccessExtension extends DataExtension {

	private static $db = array(
		'MediaAccess' => 'VARCHAR(255)'
	);

	private static $defaults = array(
		'MediaAccess' => 'ADMIN'
	);

	public function updateCMSFields(FieldList $fields) {

		$permissions = array(
			'ADMIN' => 'Administrators <strong>and</strong> Developers',
			'SITETREE_EDIT_ALL' => 'Content Authors'
		);
		Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
		if(Permission::check('EDIT_SITECONFIG') === false) {
			$fields->addFieldToTab('Root.Access', $options = ReadonlyField::create(
				'Media',
				'Who can customise media?',
				strip_tags($permissions[$this->owner->MediaAccess])
			));
		}
		else {
			$fields->addFieldToTab('Root.Access', $options = OptionsetField::create(
				'MediaAccess',
				'Who can customise media?',
				$permissions
			));
		}
	}

}
