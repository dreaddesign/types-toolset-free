<?php

/**
 * Helper class for building external links to documentation and elsewhere.
 *
 * Note that URLs first need to be loaded before you can use self::get_url().
 * 
 * @since 2.0
 */
final class Types_Helper_Url {

	const UTM_SOURCE = 'typesplugin';
	const UTM_CAMPAIGN = 'types';

	private static $utm_medium;
	private static $utm_term;
	private static $utm_content;

	private static $urls = array();

	const WP_TYPES_DOMAIN = 'wp-types.com';


	// All values for utm_medium should be eventually defined here
	const UTM_MEDIUM_HELP = 'help';
	const UTM_MEDIUM_POSTEDIT = 'postedit';


	/**
	 * @param bool|string $medium
	 */
	public static function set_medium( $medium = false ) {
		if( $medium ) {
			self::$utm_medium = $medium;
			return;
		}

		global $pagenow;
		if( ! empty( $pagenow ) ) {
			self::$utm_medium = $pagenow;
			return;
		}

		self::$utm_medium = false;
	}

	private static function get_medium() {
		if( self::$utm_medium === null ) {
			self::set_medium();
		}

		return self::$utm_medium;
	}

	public static function add_urls( $urls ) {
		if( is_array( $urls ) ) {
			self::$urls = array_merge( self::$urls, $urls );
		}
	}


	private static function apply_analytics_arguments_to_url( $url ) {
		if( ! self::get_medium() ) {
			return $url;
		}

		$args = array(
			'utm_source' => self::UTM_SOURCE,
			'utm_campaign' => self::UTM_CAMPAIGN,
			'utm_medium' => self::$utm_medium,
			'utm_term' => self::$utm_term
		);

		// This can === true, in which case we'll skip it, see self::get_url().
		if( is_string( self::$utm_content ) ) {
			$args['utm_content'] = self::$utm_content;
		}

		return add_query_arg(
			$args,
			$url
		);

	}

	/**
	 * Determines whether an URL points to the wp-types.com domain.
	 *
	 * @param string $url
	 * @return bool
	 * @since 2.1
	 */
	private static function is_link_to_wptypes( $url ) {
		$url_parts = parse_url( $url );
		return ( wpcf_getarr( $url_parts, 'host') == self::WP_TYPES_DOMAIN );
	}


	/**
	 * Retrieve the URL with additional arguments.
	 *
	 * @param string $key URL key as defined in self::add_urls().
	 * @param bool|string $utm_content This is a bit more complex than we wanted:
	 *     - false will skip *all* analytics arguments (default).
	 *     - true will continue with adding the analytics arguments but omit the utm_content one
	 *     - If a string is provided, it will be added as utm_content.
	 * @param bool|string $utm_term utm_term argument or false if $key should be used instead.
	 * @param bool|string $utm_medium utm_medium (to be set globally) or false to use a previously set value.
	 * @param bool $add_site_url If this is true and the URL points to wp-types.com, an additional argument with current
	 *    site's URL will be added.
	 *
	 * @return mixed|string The URL or an empty string if the key was invalid.
	 * @since 2.0
	 */
	public static function get_url( $key, $utm_content = false, $utm_term = false, $utm_medium = false, $add_site_url = true ) {
		if( !isset( self::$urls[ $key ] ) ) {
			return '';
		}

		$url = self::$urls[ $key ];
        
		// return url if no arguments
		if( ! $utm_content ) {
			return $url;
		}

		// add utm content
		self::$utm_content = $utm_content;

		// use key for term, if no term isset
		if( ! $utm_term ) {
			$utm_term = $key;
		}

		self::$utm_term = $utm_term;

		// apply medium only if medium isset
		if( $utm_medium ) {
			self::set_medium( $utm_medium );
		}

		// apply arguments
		$url = self::apply_analytics_arguments_to_url( $url );

		return $url;

	}


	private static $documentation_urls_loaded = false;
	
	
	/**
	 * Load URLs to documentation links so they can be obtained via get_url().
	 * 
	 * @since 2.1
	 */
	public static function load_documentation_urls() {
		if( ! self::$documentation_urls_loaded ) {
			$documentation_urls = include( TYPES_DATA . '/documentation-urls.php' );
			Types_Helper_Url::add_urls( $documentation_urls );
			self::$documentation_urls_loaded = true;
		}
	}
}