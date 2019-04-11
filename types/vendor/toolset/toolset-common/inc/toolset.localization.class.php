<?php

/**
 * toolset.localization.class.php
 *
 * Common localization for shared code on the Toolset and also common way for adding textdomains
 *
 * @since unknown
 */

if ( ! defined( 'TOOLSET_I18N' ) ) {
	define( 'TOOLSET_I18N', true );
}

if ( ! defined( 'TOOLSET_LOCALIZATION_ABSPATH' ) ) {
	define( 'TOOLSET_LOCALIZATION_ABSPATH', TOOLSET_COMMON_PATH . '/languages' );
}


if ( ! class_exists( 'Toolset_Localization', false ) ) {

	/**
	 * Toolset_Localization
	 *
	 * Methods for registering textdomains, defaults to register the wpv-views one.
	 *
	 * @since unknown
	 */
	class Toolset_Localization {

		/**
		 * @param $textdomain (string) the textdomain to use
		 * @param $path (string) the path to the folder containing the mo files
		 * @param $mo_name (string) the .mo file name, using %s as a placeholder for the locale - do not add the .mo extension!
		 */
		function __construct( $textdomain = 'wpv-views', $path = TOOLSET_LOCALIZATION_ABSPATH , $mo_name = 'views-%s' ) {
			// Set instance properties
			$this->textdomain = $textdomain;
			$this->path = $path;
			$this->mo_name = $mo_name;
			$this->mo_processed_name = '';
			// Set init action
			add_action( 'init', array( $this, 'load_textdomain' ) );
		}
		
		/**
		 * Initializes localization given a textdomain, a path and a .mo file name
		 *
		 * @uses load_textdomain
		 *
		 * @since July 18, 2014
		 */
		function load_textdomain() {
			// get_user_locale() was introduced in WordPress 4.7
			$locale = ( function_exists( 'get_user_locale') ? get_user_locale() : get_locale() );
			$this->mo_processed_name = sprintf( $this->mo_name, $locale );
			load_textdomain( $this->textdomain, $this->path . '/' . $this->mo_processed_name . '.mo' );
		}

	}

}