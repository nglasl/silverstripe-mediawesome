<?php

/**
 *	This will provide the media type context for a media attribute.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class MediaAttributeAddNewButton extends GridFieldAddNewButton {

	protected $type;

	public function __construct($type) {

		parent::__construct('buttons-before-left');
		$this->type = $type;
	}

	public function getHTMLFragments($gridfield) {

		$fragments = parent::getHTMLFragments($gridfield);
		if(isset($fragments[$this->targetFragment])) {
			$fragment = $fragments[$this->targetFragment];
			$fragment->setValue(str_replace('/new', "/new?type={$this->type}", $fragment->getValue()));
		}
		return $fragments;
	}

}
