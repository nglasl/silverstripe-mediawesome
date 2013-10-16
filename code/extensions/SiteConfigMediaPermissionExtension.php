<?php

/**
 *	Mediawesome extension which allows permission configuration for customisation of media.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class SiteConfigMediaPermissionExtension extends DataExtension {

	/**
	 *	Append an additional media permission field to the site configuration.
	 */

	private static $db = array(
		'MediaPermission' => "Enum('ADMIN, SITETREE_EDIT_ALL', 'ADMIN')"
	);

	/**
	 *	Allow permission configuration for customisation of media.
	 */

	public function updateCMSFields(FieldList $fields) {

		$permissions = array(
			'ADMIN' => 'Administrators and Developers',
			'SITETREE_EDIT_ALL' => 'Content Authors'
		);
		Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');
		if(Permission::check('EDIT_SITECONFIG') === false) {
			$fields->addFieldToTab('Root.Access', $options = ReadonlyField::create(
				'Media',
				'Who can customise media?',
				$permissions[$this->owner->MediaPermission]
			));
		}
		else {
			$fields->addFieldToTab('Root.Access', $options = OptionsetField::create(
				'MediaPermission',
				'Who can customise media?',
				$permissions
			));
		}
	}

}
