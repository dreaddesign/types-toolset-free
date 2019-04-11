<?php

if ( ! class_exists( 'Toolset_WPML_Compatibility', false ) ) {


	/**
	 * Handle the basic interactions between WPML and Toolset plugins.
	 *
	 * @since unknown
	 */
	class Toolset_WPML_Compatibility extends Toolset_Wpdb_User {


		// Possible WPML translation modes. Use these constants instead of hardcoded strings as they might
		// change without warning.
		const MODE_DONT_TRANSLATE = 'dont_translate';
		const MODE_TRANSLATE = 'translate';
		const MODE_DISPLAY_AS_TRANSLATED = 'display_as_translated';


		/** @var Toolset_WPML_Compatibility */
		private static $instance;


		/** @var null|string Cache for the current language code. */
		private $current_language;

		/** @var null|string Cache for the default language code. */
		private $default_language;


		/** @var string[] */
		private $previous_languages = array();


		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		public static function initialize() {
			self::get_instance();
		}


		public function __construct( wpdb $wpdb_di = null ) {

			parent::__construct( $wpdb_di );

			add_action( 'init', array( $this, 'maybe_add_wpml_string_stub_shortcode' ), 100 );

			/**
			 * toolset_is_wpml_active_and_configured
			 *
			 * Check whether WPML core is active and configured properly.
			 *
			 * Note: Beware when calling this early, especially before 'init'. The behaviour depends
			 * on WPML and hasn't been tested.
			 *
			 * @since 2.3
			 */
			add_filter(
				'toolset_is_wpml_active_and_configured', array(
				$this,
				'filter_is_wpml_active_and_configured'
			)
			);


			/**
			 * Shows a warning in WPML if the post type belongs to a relationships and it is not the proper translation mode
			 *
			 * @since m2m
			 */
			//add_filter( 'wpml_disable_translation_mode_radio', array( $this, 'wpml_disable_translation_mode_radio' ), 10, 3 );
		}


		/**
		 * In case WPML ST isn't active, add a stub "wpml-string" shortcode that will only
		 * return its content.
		 *
		 * This is to avoid printing of the unprocessed shortcode.
		 *
		 * @since unknown
		 */
		public function maybe_add_wpml_string_stub_shortcode() {
			if ( ! $this->is_wpml_st_active() ) {
				add_shortcode( 'wpml-string', array( $this, 'stub_wpml_string_shortcode' ) );
			}
		}


		/**
		 * Stub for the wpml-string shortcode.
		 *
		 * Make it as if the shortcode wasn't there.
		 *
		 * @param $atts
		 * @param string $value
		 *
		 * @return string
		 * @since unknown
		 */
		public function stub_wpml_string_shortcode(
			/** @noinspection PhpUnusedParameterInspection */
			$atts, $value
		) {
			return do_shortcode( $value );
		}


		/**
		 * Check whether WPML core is active and configured.
		 *
		 * The result is cached for better performance.
		 *
		 * @param bool $use_cache
		 *
		 * @return bool
		 * @since 2.3
		 */
		public function is_wpml_active_and_configured( $use_cache = true ) {

			static $result = null;

			if ( null === $result || ! $use_cache ) {
				global $sitepress;
				$is_wpml_active = (
					defined( 'ICL_SITEPRESS_VERSION' )
					&& ! ICL_PLUGIN_INACTIVE
					&& ! is_null( $sitepress )
					&& class_exists( 'SitePress' )
				);

				$is_wpml_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );

				$result = ( $is_wpml_active && $is_wpml_configured );
			}

			return $result;
		}


		/**
		 * Callback for toolset_is_wpml_active_and_configured.
		 *
		 * Instead of calling this directly, use is_wpml_configured_and_active().
		 *
		 * @param mixed $default_value Ignored.
		 *
		 * @return bool
		 * @since 2.3
		 */
		public function filter_is_wpml_active_and_configured(
			/** @noinspection PhpUnusedParameterInspection */
			$default_value
		) {
			return $this->is_wpml_active_and_configured();
		}


		/**
		 * Check whether WPML ST is active.
		 *
		 * This will return false when WPML is not configured.
		 *
		 * @return bool
		 * @since 2.3
		 */
		public function is_wpml_st_active() {

			if ( ! $this->is_wpml_active_and_configured() ) {
				return false;
			}

			return ( defined( 'WPML_ST_VERSION' ) );
		}

		/**
		 * Check whether WPML TM is active.
		 *
		 * This will return false when WPML is not configured.
		 *
		 * @return bool
		 * @since 2.5
		 */
		public function is_wpml_tm_active() {

			if ( ! $this->is_wpml_active_and_configured() ) {
				return false;
			}

			return ( defined( 'WPML_TM_VERSION' ) );
		}


		/**
		 * Get the version of WPML core, if it's defined.
		 *
		 * @return null|string
		 * @since 2.3
		 */
		public function get_wpml_version() {
			return ( defined( 'ICL_SITEPRESS_VERSION' ) ? ICL_SITEPRESS_VERSION : null );
		}


		/**
		 * Check if a post type is translatable.
		 *
		 * @param string $post_type_slug
		 *
		 * @return bool
		 * @since m2m
		 */
		public function is_post_type_translatable( $post_type_slug ) {
			if( ! $this->is_wpml_active_and_configured() ) {
				// Sometimes, the filter below starts working before the activeness check,
				// creating a little mess - this happens on WPML (re)activation.
				return false;
			}

			return (bool) apply_filters( 'wpml_is_translated_post_type', false, $post_type_slug );
		}


		/**
		 * Check if a post type is translatable and in the "display as translated" mode.
		 *
		 * @param string $post_type_slug
		 *
		 * @return bool
		 * @since 2.5.10
		 */
		public function is_post_type_display_as_translated( $post_type_slug ) {
			if ( ! $this->is_post_type_translatable( $post_type_slug ) ) {
				return false;
			}

			return (bool) apply_filters( 'wpml_is_display_as_translated_post_type', false, $post_type_slug );
		}


		/**
		 * Get the current language.
		 *
		 * Cached.
		 *
		 * @return string
		 * @since m2m
		 */
		public function get_current_language() {
			if ( null === $this->current_language && $this->is_wpml_active_and_configured() ) {
				$this->current_language = apply_filters( 'wpml_current_language', null );
			}

			return $this->current_language;
		}


		/**
		 * Get the default site language.
		 *
		 * Cached.
		 *
		 * @return string
		 * @since m2m
		 */
		public function get_default_language() {
			if ( null === $this->default_language && $this->is_wpml_active_and_configured() ) {
				$this->default_language = apply_filters( 'wpml_default_language', null );
			}

			return $this->default_language;
		}


		/**
		 * @return bool True if the site is currently in the default language.
		 * @since 2.5.10
		 */
		public function is_current_language_default() {
			return (
				! $this->is_wpml_active_and_configured()
				|| $this->get_default_language() === $this->get_current_language()
			);
		}


		/**
		 * Get the language of a provided post.
		 *
		 * @param int $post_id
		 *
		 * @return string Language code or an empty string if none is defined or WPML is not active.
		 */
		public function get_post_language( $post_id ) {
			$post_language_details = apply_filters( 'wpml_post_language_details', null, $post_id );
			$lang = toolset_getarr( $post_language_details, 'language_code', '' );

			if( ! is_string( $lang ) ) {
				$lang = '';
			}

			return $lang;
		}


		/**
		 * Determine whether the current language is "All languages".
		 *
		 * @return bool
		 * @since  2.6.8
		 */
		public function is_showing_all_languages() {
			return $this->get_current_language() === 'all';
		}


		/**
		 * Get an array of post translation IDs from the icl_translations table, indexed by language codes.
		 *
		 * todo consider using WPML hooks if they're available
		 *
		 * @param int $post_id
		 *
		 * @return int[]
		 * @since 2.5.10
		 */
		public function get_post_translations_directly( $post_id ) {

			if ( ! $this->is_wpml_active_and_configured() ) {
				return array();
			}

			$icl_translations_table = $this->icl_translations_table_name();
			$trid = $this->get_post_trid( $post_id );

			if ( null === $trid ) {
				return array();
			}

			$query = $this->wpdb->prepare(
				"SELECT
					element_id AS post_id,
					language_code AS language_code
				FROM
					$icl_translations_table
				WHERE
					element_type LIKE %s
					AND trid = %d",
				'post_%',
				$trid
			);

			$db_results = $this->wpdb->get_results( $query );

			// Return an associative array of post IDs.
			$results = array();
			foreach ( $db_results as $row ) {
				$results[ $row->language_code ] = (int) $row->post_id;
			}

			return $results;

		}


		/**
		 * Retrieve the translation group ID for a post.
		 *
		 * @param int $post_id
		 *
		 * @return int "trid" value or zero.
		 * @since m2m
		 */
		public function get_post_trid( $post_id ) {
			$icl_translations_table = $this->icl_translations_table_name();

			$query = $this->wpdb->prepare(
				"SELECT trid
			FROM `{$icl_translations_table}`
			WHERE
				element_type LIKE %s
				AND element_id = %d
			LIMIT 1",
				'post_%',
				$post_id
			);

			return (int) $this->wpdb->get_var( $query );
		}


		/**
		 * @return string icl_translations table name.
		 */
		public function icl_translations_table_name() {
			return $this->wpdb->prefix . 'icl_translations';
		}


		/**
		 * Get the translation mode value for a given post type.
		 *
		 * If WPML is not active or the post type doesn't exist, self::MODE_DONT_TRANSLATE will be returned.
		 *
		 * @param string $post_type_slug
		 * @return string
		 * @since 2.5.11
		 */
		public function get_post_type_translation_mode( $post_type_slug ) {
			if(
				! $this->is_wpml_active_and_configured()
				|| ! $this->is_post_type_translatable( $post_type_slug )
			) {
				return self::MODE_DONT_TRANSLATE;
			}

			if( $this->is_post_type_display_as_translated( $post_type_slug ) ) {
				return self::MODE_DISPLAY_AS_TRANSLATED;
			}

			return self::MODE_TRANSLATE;
		}


		/**
		 * Set the translation mode of given post type.
		 *
		 * @param string $post_type_slug
		 * @param string $translation_mode One of the MODE_ constants defined on this class.
		 * @return void
		 * @throws InvalidArgumentException if WPML is not active or an invalid translation mode is provided.
		 * @since 2.5.11
		 */
		public function set_post_type_translation_mode( $post_type_slug, $translation_mode ) {
			if( ! $this->is_wpml_active_and_configured() ) {
				throw new InvalidArgumentException( 'Trying to set a post translation mode while WPML is not active.' );
			}

			$allowed_modes = array( self::MODE_TRANSLATE, self::MODE_DONT_TRANSLATE, self::MODE_DISPLAY_AS_TRANSLATED );

			if( ! in_array( $translation_mode, $allowed_modes ) ) {
				throw new InvalidArgumentException( 'Trying to set an invalid translation mode for a post type' );
			}

			do_action( 'wpml_set_translation_mode_for_post_type', $post_type_slug, $translation_mode );
		}


		/**
		 * Set a post as a translation of another post (original).
		 *
		 * @param IToolset_Post $original_post
		 * @param int $translation_post_id ID of the translated post.
		 * @param string $lang_code Language of the translated post.
		 * @throws InvalidArgumentException If called when WPML inactive.
		 * @return void
		 */
		public function add_post_translation( IToolset_Post $original_post, $translation_post_id, $lang_code ) {
			if( ! $this->is_wpml_active_and_configured() ) {
				throw new InvalidArgumentException( 'Cannot add a post translation if WPML is not active and configured.' );
			}
			$element_type = apply_filters( 'wpml_element_type', $original_post->get_type() );

			$set_language_args = array(
				'element_id' => $translation_post_id,
				'element_type' => $element_type,
				'trid' => $original_post->get_trid(),
				'language_code' => $lang_code,
				'source_language_code' => $original_post->get_language()
			);

			do_action( 'wpml_set_element_language_details', $set_language_args );
		}


		/**
		 * Create a duplicate of the given post (using standard WPML mechanism to copy the content).
		 *
		 * Optionally, it is possible to _not_ mark it as an duplicate, but as a regular translation instead.
		 *
		 * @param IToolset_Post $original_post
		 * @param string $lang_code Language of the duplicated post.
		 * @param bool $mark_as_duplicate
		 *
		 * @return int ID of the duplicated post.
		 * @throws InvalidArgumentException If called when WPML inactive.
		 * @throws RuntimeException If it is not possible to perform the call to WPML.
		 */
		public function create_post_duplicate( IToolset_Post $original_post, $lang_code, $mark_as_duplicate = true ) {
			if( ! $this->is_wpml_active_and_configured() ) {
				throw new InvalidArgumentException( 'Cannot add a post translation if WPML is not active and configured.' );
			}

			$copied_post_id = apply_filters( 'wpml_copy_post_to_language', $original_post->get_id(), $lang_code, $mark_as_duplicate );

			return (int) $copied_post_id;
		}

		/**
		 * Shows a warning in WPML if the post type belongs to a relationships and it is not the proper translation mode
		 *
		 * @param array  $disabled_state_for_mode Filterable array.
		 * @param int    $mode WPML translation mode.
		 * @param string $content_slug Post type slug.
		 * @return array
		 * @since m2m
		 */
		public function wpml_disable_translation_mode_radio( $disabled_state_for_mode, $mode, $content_slug ) {

			if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				return $disabled_state_for_mode;
			}

			do_action( 'toolset_do_m2m_full_init' );

			$relationships_query = new Toolset_Relationship_Query_V2();
			$relationships_query->add( $relationships_query->has_domain( 'posts' ) )
				->add( $relationships_query->has_type( $content_slug ) )
				->do_not_add_default_conditions();
			$relationships = $relationships_query->get_results();

			if ( empty( $relationships ) ) {
				return $disabled_state_for_mode;
			}

			foreach ( $relationships as $relationship ) {
				$types = array_merge( $relationship->get_parent_type()->get_types(), $relationship->get_child_type()->get_types() );
				if ( in_array( $content_slug, $types, true ) && defined( 'WPML_CONTENT_TYPE_TRANSLATE' ) && $mode == WPML_CONTENT_TYPE_TRANSLATE ) {
					$disabled_state_for_mode['state'] = true;
					// translators: Relationship name.
					$disabled_state_for_mode['reason_message'] = sprintf( __( 'You cannot set this translation mode because the post type is involved in the relationship "%s".', 'wpcf' ), $relationship->get_display_name() ) . ' <a href="https://toolset.com/documentation/translating-sites-built-with-toolset/translating-related-content/" target="_blank">' . __( 'Learn more' , 'wpcf' ) . '</a>.';
					return $disabled_state_for_mode;
				}
			}
			return $disabled_state_for_mode;
		}



		/**
		 * Switch the current language.
		 *
		 * Warning: You *MUST* revert this by calling switch_language_back() in all cases.
		 *
		 * It is possible to nest these calls, but switch_language() and switch_language_back() must always
		 * come in pairs.
		 *
		 * @param string $lang_code
		 * @since 2.5.10
		 */
		public function switch_language( $lang_code ) {
			array_push( $this->previous_languages, $this->get_current_language() );
			do_action( 'wpml_switch_language', $lang_code );
		}


		/**
		 * Switch the current language back to the previous value after switch_language().
		 *
		 * @since 2.5.10
		 */
		public function switch_language_back() {
			$lang_code = array_pop( $this->previous_languages );
			do_action( 'wpml_switch_language', $lang_code );
		}


		/**
		 * Get the URL for the WPML setting of post type translation modes.
		 *
		 * Note: This works since WPML 3.9.2.
		 *
		 * @return string Escaped URL.
		 * @since 3.8
		 * @throws RuntimeException If WPML is not active and configured.
		 */
		public function get_post_type_translation_settings_url() {
			if( ! $this->is_wpml_active_and_configured() ) {
				throw new RuntimeException( 'Cannot get the translation options URL until WPML is active and configured.');
			}

			$url = esc_url_raw( apply_filters( 'wpml_get_post_translation_settings_link', '' ) );
			if( ! is_string( $url ) ) {
				// Something bad happened, but not our fault.
				return '';
			}

			return $url;
		}
	}
}
