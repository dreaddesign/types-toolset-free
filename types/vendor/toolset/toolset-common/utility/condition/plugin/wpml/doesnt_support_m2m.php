<?php

/**
 * Check whether there's a problem with old WPML version and m2m.
 *
 * @since m2m
 */
class Toolset_Condition_Plugin_Wpml_Doesnt_Support_M2m implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		$m2m_controller = Toolset_Relationship_Controller::get_instance();

		if( ! Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured() ) {
			return false;
		}

		return version_compare(
			Toolset_WPML_Compatibility::get_instance()->get_wpml_version(),
			Toolset_Relationship_Controller::MINIMAL_WPML_VERSION,
			'<'
		);
	}
}
