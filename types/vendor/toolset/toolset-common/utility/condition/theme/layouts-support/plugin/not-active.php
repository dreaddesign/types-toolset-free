<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Plugin_Not_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Plugin_Not_Active
	extends Toolset_Condition_Theme_Layouts_Support_Plugin_Not_Installed {

	private static $is_met_result;

	/**
	 * @return bool
	 */
	public function get_plugin() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result;
		}

		return false;
	}

	public function is_met() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result === false ? false : true;
		}

		if( parent::is_met() ) {
			// plugin is not even installed
			return false;
		}

		$integration_plugin = parent::get_supported_plugin_integration();

		$all_plugins = get_plugins();

		foreach( $all_plugins as $plugin_path => $plugin ) {
			if( $plugin['Name'] == $integration_plugin['plugin_name'] ) {
				if( is_plugin_inactive( $plugin_path ) ) {
					$plugin['Path'] = $plugin_path;
					self::$is_met_result = $plugin;
					return true;
				}
			}
		}

		self::$is_met_result = false;
		return false;
	}
}