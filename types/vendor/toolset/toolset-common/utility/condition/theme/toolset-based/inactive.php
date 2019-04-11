<?php

/**
 * Toolset_Condition_Theme_Toolset_Based_Active
 *
 * Our encrypted plugins define TOOLSET_BASED_THEME_ACTIVE if one of the plugin is running (right theme active)
 * and TOOLSET_BASED_THEME_INACTIVE when the right theme is not running.
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Toolset_Based_Inactive
	implements Toolset_Condition_Interface {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null ) {
			return self::$is_met_result === false ? false : true;
		}

		$views_active = new Toolset_Condition_Plugin_Views_Active();
		if( $views_active->is_met() ) {
			// false, if views is active
			self::$is_met_result = false;
			return false;
		}

		$layouts_active = new Toolset_Condition_Plugin_Layouts_Active();
		if( $layouts_active->is_met() ) {
			// false, if layouts is active
			self::$is_met_result = false;
			return false;
		}

		$cred_active = new Toolset_Condition_Plugin_Cred_Active();
		if( $cred_active->is_met() ) {
			// false, if access is active
			self::$is_met_result = false;
			return false;
		}

		$access_active = new Toolset_Condition_Plugin_Access_Active();
		if( $access_active->is_met() ) {
			// false, if access is active
			self::$is_met_result = false;
			return false;
		}

		if( defined( 'TOOLSET_BASED_THEME_ACTIVE' ) ) {
			// it can happen that twice toolset based themes are running on the same site
			self::$is_met_result = false;
			return false;
		}

		if( defined( 'TOOLSET_BASED_THEME_INACTIVE' ) ) {
			// no other tbt active and one inactive tbt
			self::$is_met_result = TOOLSET_BASED_THEME_INACTIVE;
			return true;
		}

		self::$is_met_result = false;
		return false;
	}

	/**
	 * @return string|false
	 */
	public function get_theme() {
		return is_string( self::$is_met_result ) ? self::$is_met_result : false;
	}

}