<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Theme_Installed
 *
 * This condition returns true if an Layouts Integration Plugin is active
 * and the supported-by-integration theme is installed (active or not)
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Theme_Installed
	extends Toolset_Condition_Theme_Layouts_Support_Plugin_Active {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null ) {
			// return cached result
			return self::$is_met_result === false ? false : true;
		}

		if( ! $theme_supported_by_integration = parent::get_supported_theme_name() ) {
			self::$is_met_result = false;
			return false;
		}

		$installed_themes = wp_get_themes();

		foreach( $installed_themes as $theme ) {
			if( $theme->get( 'Name' ) == $theme_supported_by_integration ) {
				self::$is_met_result = $theme;
				return true;
			}
		}

		self::$is_met_result = false;
		return false;
	}

	/**
	 * @return WP_Theme|false
	 */
	public function get_theme() {
		if( self::$is_met_result === null ) {
			self::is_met();
		}

		return self::$is_met_result;
	}

}