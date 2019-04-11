<?php

/**
 * This condition is met in two cases: When WPML isn't active and configured or when the current
 * language is the default one.
 *
 * @since 2.5.10
 */
class Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		$wpml_service = Toolset_WPML_Compatibility::get_instance();

		return (
			! $wpml_service->is_wpml_active_and_configured()
			|| $wpml_service->get_current_language() === $wpml_service->get_default_language()
		);
	}
}