<?php

namespace OTGS\Toolset\Common\Condition\Installer;

/**
 * Check whether Installer is available, by checking Types and WPML status.
 *
 * @package OTGS\Toolset\Common\Condition\Installer
 */
class IsAvailable implements \Toolset_Condition_Interface {


	/** @var \Toolset_Condition_Plugin_Types_Active */
	private $is_types_active_condition;


	/** @var \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured */
	private $is_wpml_active_condition;


	/**
	 * IsAvailable constructor.
	 *
	 * @param \Toolset_Condition_Plugin_Types_Active|null $is_types_active_di
	 * @param \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured|null $is_wpml_active_di
	 */
	public function __construct(
		\Toolset_Condition_Plugin_Types_Active $is_types_active_di = null,
		\Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active_di = null
	) {
		$this->is_types_active_condition = $is_types_active_di ?: new \Toolset_Condition_Plugin_Types_Active();
		$this->is_wpml_active_condition = $is_wpml_active_di ?: new \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured();
	}


	/**
	 * @return bool
	 */
	public function is_met() {
		return ( $this->is_types_active_condition->is_met() || $this->is_wpml_active_condition->is_met() );
	}
}