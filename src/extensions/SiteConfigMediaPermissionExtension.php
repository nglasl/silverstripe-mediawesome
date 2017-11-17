<?php

namespace nglasl\mediawesome;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

/**
 *	This allows permission configuration for customisation of media.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
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

		// Allow extension customisation.

		$this->owner->extend('updateSiteConfigMediaPermissionExtensionCMSFields', $fields);
	}

}
