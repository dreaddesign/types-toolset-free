<?php

/**
 * An "objectified" version of the check whether WPML is active and configured.
 *
 * @since 2.5.10
 */
class Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		return Toolset_WPML_Compatibility::get_instance()->is_wpml_active_and_configured();
	}
}