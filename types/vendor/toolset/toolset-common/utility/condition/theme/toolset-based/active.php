<?php

/**
 * Toolset_Condition_Theme_Toolset_Based_Active
 *
 * Our encrypted plugins define TOOLSET_BASED_THEME_ACTIVE if one of the plugin is running (right theme active)
 * and TOOLSET_BASED_THEME_INACTIVE when the right theme is not running.
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Toolset_Based_Active
	implements Toolset_Condition_Interface {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null ) {
			return self::$is_met_result === false ? false : true;
		}

		if( defined( 'TOOLSET_BASED_THEME_ACTIVE' ) ) {
			self::$is_met_result = TOOLSET_BASED_THEME_ACTIVE;
			return true;
		}

		return false;
	}

	/**
	 * @return string|false
	 */
	public function get_theme() {
		return is_string( self::$is_met_result ) ? self::$is_met_result : false;
	}
}