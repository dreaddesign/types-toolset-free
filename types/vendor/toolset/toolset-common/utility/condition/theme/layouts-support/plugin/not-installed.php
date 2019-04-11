<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Plugin_Not_Installed
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Plugin_Not_Installed
	extends Toolset_Condition_Theme_Layouts_Support_Plugin_Available {

	private static $is_met_result;

	public function get_supported_plugin_integration() {
		return parent::get_supported_plugin_integration();
	}

	public function is_met() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result;
		}

		if( ! parent::is_met() ) {
			// there is no integration plugin available for the current theme
			return false;
		}

		$integration_plugin = $this->get_supported_plugin_integration();

		$all_plugins = get_plugins();

		foreach( $all_plugins as $plugin ) {
			if( $plugin['Name'] == $integration_plugin['plugin_name'] ) {
				self::$is_met_result = false;
				return false;
			}
		}

		self::$is_met_result = true;
		return true;
	}
}