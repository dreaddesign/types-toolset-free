<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Theme_Installed
 *
 * This condition returns true if an Layouts Integration Plugin is active
 * and the supported-by-integration theme is installed and not active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Theme_Not_Active
	extends Toolset_Condition_Theme_Layouts_Support_Theme_Installed {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result === false ? false : true;
		}

		if( ! $theme_for_integration = parent::get_theme() ) {
			// no integration plugin installed
			self::$is_met_result = false;
			return true;
		}

		$current_theme = wp_get_theme();

		if( $theme_for_integration->get( 'Name' ) != $current_theme->get( 'Name' ) ) {
			self::$is_met_result = $theme_for_integration;
			return true;
		}

		// theme is active
		self::$is_met_result = false;
		return false;
	}

	/**
	 * @return WP_Theme|false
	 */
	public function get_theme() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result;
		}

		return false;
	}

}