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
			'ADMIN' => 'Administrators and developers',
			'SITETREE_EDIT_ALL' => 'Content authors'
		);
		Requirements::css(MEDIAWESOME_PATH . '/css/mediawesome.css');

		// Confirm that the current CMS user has permission.

		if(Permission::check('EDIT_SITECONFIG') === false) {
			$fields->addFieldToTab('Root.Access', $options = ReadonlyField::create(
				'Media',
				'Who can customise media?',
				$permissions[$this->owner->MediaPermission]
			));
		}
		else {

			// Display the permission configuration.

			$fields->addFieldToTab('Root.Access', $options = OptionsetField::create(
				'MediaPermission',
				'Who can customise media?',
				$permissions
			));
		}
	}

}
