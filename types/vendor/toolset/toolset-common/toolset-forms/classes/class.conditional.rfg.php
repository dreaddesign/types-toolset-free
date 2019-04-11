<?php

/**
 * Class WPToolset_Forms_Conditional_RFG
 *
 * Subclass of WPToolset_Forms_Conditional, which disables parent::__construct
 * and offers the method get_conditions() to allow the user getting all registered conditions.
 *
 * @since 2.6.5
 */
class WPToolset_Forms_Conditional_RFG extends WPToolset_Forms_Conditional {

	/**
	 * @var string
	 */
	private $form_selector;

	/**
	 * Other than the parent class, this does not need the $form_id
	 *
	 * @param string $form_selector Selector of the form.
	 */
	public function __construct( $form_selector = '#post' ) {
		// IMPORTANT: Do not run parent::__construct() here

		// for parent usages
		$this->__formID = trim( $form_selector, '#' );

		// only for this subclass
		$this->form_selector = $form_selector;
	}

	/**
	 * Get registered conditions (trigger & fields)
	 *
	 * @return array
	 */
	public function get_conditions() {
		$this->_parseData();

		return array(
			$this->form_selector => array(
				'triggers' => $this->_triggers,
				'fields' => $this->_fields,
				'custom_triggers' => $this->_custom_triggers,
				'custom_fields' => $this->_custom_fields
			)
		);
	}
}
