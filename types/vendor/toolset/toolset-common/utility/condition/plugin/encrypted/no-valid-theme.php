<?php

/**
 * Toolset_Condition_Plugin_Encrypted_No_Valid_Theme
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Encrypted_No_Valid_Theme implements Toolset_Condition_Interface {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null ) {
			return self::$is_met_result;
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

		$all_plugins = get_plugins();

		foreach( $all_plugins as $plugin_path => $plugin ) {
			if( strpos( $plugin['Name'], 'Toolset' ) !== false      // Toolset Plugin
				&& strpos( $plugin['Name'], 'Types' ) === false     // except Types
				&& is_plugin_active( $plugin_path )                 // and is active
			) {
				preg_match( '#Working only while (.*?) is active#', $plugin['Description'], $description );
				error_log( '$description ' . print_r( $description, true ) );
				$theme_name = isset( $description[1] ) && ! empty( $description[1] )
					? $description[1]
					: true;

				// true, there is a encrypted Toolset commercial plugin active,
				// but not working because of an invalid theme
				self::$is_met_result = $theme_name;
				return true;
			}
		}

		// only types installed
		self::$is_met_result = false;
		return false;
	}

	/**
	 * @return bool
	 */
	public function get_theme() {
		return is_string( self::$is_met_result ) ? self::$is_met_result : false;
	}

}