<?php

/**
 * WPML interoperability handler.
 *
 * WIP - this stub is to be filled with everything WPML-related.
 * Look into: vendor/toolset/types/embedded/includes/wpml.php
 *
 * @since 2.2.9
 */
class Types_Interop_Handler_Wpml implements Types_Interop_Handler_Interface {


	private static $instance;

	private function __construct() { }

	private function __clone() { }


	public static function initialize() {
		self::get_instance();
	}


	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Check whether the current language is the default one.
	 *
	 * The result is cached for better performance.
	 *
	 * @return bool
	 * @since 2.2.9
	 */
	public function is_current_language_default() {
		static $result = null;

		if( null === $result ) {
			$current_language = apply_filters( 'wpml_current_language', null );
			$default_language = apply_filters( 'wpml_default_language', null );
			$result = ( $current_language === $default_language );
		}

		return $result;
	}


	/**
	 * Get translation of string.
	 *
	 * @param string $string The original string value to translate
	 * @param string $string_name String name
	 * @param string $domain String domain
	 *
	 * @link https://wpml.org/wpml-hook/wpml_translate_single_string/
	 *
	 * @return false|string Translated string or false if no such string is registered.
	 * @since 2.2.9
	 */
	public function get_translation( $string, $string_name, $domain ) {

		// check if translation exists
		$is_registered = apply_filters(
			'wpml_string_id',
			null,
			array(
				'context' => $domain,
				'name' => $string_name
			)
		);

		if ( $is_registered === null ) {
			return false;
		}

		// string is registered, return translation
		return apply_filters(
			'wpml_translate_single_string',
			$string,
			$domain,
			$string_name
		);
	}

}