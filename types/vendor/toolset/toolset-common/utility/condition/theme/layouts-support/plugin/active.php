<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Plugin_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Plugin_Active
	implements Toolset_Condition_Interface {

	public function is_met() {
		if( defined( 'TOOLSET_INTEGRATION_PLUGIN_THEME_NAME' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return false|string
	 */
	public function get_supported_theme_name() {
		if( defined( 'TOOLSET_INTEGRATION_PLUGIN_THEME_NAME' ) ) {
			return TOOLSET_INTEGRATION_PLUGIN_THEME_NAME;
		}

		return false;
	}
}