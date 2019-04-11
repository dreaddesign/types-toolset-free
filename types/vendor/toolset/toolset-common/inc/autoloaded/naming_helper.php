<?php

/**
 * Handles checking for uniqueness of names or slugs across several domains and provides methods for
 * generating simple unique slugs.
 *
 * Currently supports post type slugs, relationship definition slugs and rewrite slugs for post types and taxonomies.
 *
 * @since m2m
 */
class Toolset_Naming_Helper {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private function __construct() { }

	private function __clone() { }


	// Available domains
	const DOMAIN_POST_TYPE_REWRITE_SLUGS = 'post_type_rewrite_slugs';
	const DOMAIN_TAXONOMY_REWRITE_SLUGS = 'taxonomy_rewrite_slugs';
	const DOMAIN_POST_TYPE_SLUGS = 'post_type_slugs';
	const DOMAIN_RELATIONSHIPS = 'relationships';

	// This is determined by the wp_posts table structure.
	const MAX_POST_TYPE_SLUG_LENGTH = 20;


	// See https://codex.wordpress.org/Reserved_Terms
	private $reserved_terms = array(
		'attachment',
		'attachment_id',
		'author',
		'author_name',
		'calendar',
		'cat',
		'category',
		'category__and',
		'category__in',
		'category__not_in',
		'category_name',
		'comments_per_page',
		'comments_popup',
		'custom',
		'customize_messenger_channel',
		'customized',
		'cpage',
		'day',
		'debug',
		'embed',
		'error',
		'exact',
		'feed',
		'hour',
		'link_category',
		'm',
		'minute',
		'monthnum',
		'more',
		'name',
		'nav_menu',
		'nonce',
		'nopaging',
		'offset',
		'order',
		'orderby',
		'p',
		'page',
		'page_id',
		'paged',
		'pagename',
		'pb',
		'perm',
		'post',
		'post__in',
		'post__not_in',
		'post_format',
		'post_mime_type',
		'post_status',
		'post_tag',
		'post_type',
		'posts',
		'posts_per_archive_page',
		'posts_per_page',
		'preview',
		'robots',
		's',
		'search',
		'second',
		'sentence',
		'showposts',
		'static',
		'subpost',
		'subpost_id',
		'tag',
		'tag__and',
		'tag__in',
		'tag__not_in',
		'tag_id',
		'tag_slug__and',
		'tag_slug__in',
		'taxonomy',
		'tb',
		'term',
		'terms',
		'theme',
		'title',
		'type',
		'w',
		'withcomments',
		'withoutcomments',
		'year'
	);


	/**
	 * Maps domain to a method which can be used for conflict check.
	 *
	 * Each method accepts two arguments, first is the candidate value and second can be an ID to be ignored
	 * during the check (when renaming, for example).
	 *
	 * @return string[]
	 * @since m2m
	 */
	private function get_domain_to_method_mapping() {
		return array(
			self::DOMAIN_POST_TYPE_REWRITE_SLUGS => 'check_slug_conflicts_in_post_type_rewrite_rules',
			self::DOMAIN_TAXONOMY_REWRITE_SLUGS => 'check_slug_conflicts_in_taxonomy_rewrite_rules',
			self::DOMAIN_POST_TYPE_SLUGS => 'check_post_type_slug_conflicts',
			self::DOMAIN_RELATIONSHIPS => 'check_relationship_slug_conflicts'
		);
	}


	/**
	 * Check a slug for conflict with slugs used for post type permalink rewriting.
	 *
	 * @param string $value Value to check.
	 * @param string $exclude_id Post type slug to exclude from checking.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 * @since 2.1
	 */
	public function check_slug_conflicts_in_post_type_rewrite_rules( $value, $exclude_id ) {

		// Merge currently registered post types (which might include some from other plugins) and
		// Types settings (which might include deactivated post types).
		$post_type_settings = array();
		if( defined( 'WPCF_OPTION_NAME_CUSTOM_TYPES' ) ) {
			$post_type_settings = toolset_ensarr( get_option( WPCF_OPTION_NAME_CUSTOM_TYPES, array() ) );
		}
		$post_type_settings = array_merge( $post_type_settings, get_post_types( array(), 'objects' ) );

		foreach( $post_type_settings as $post_type ) {

			// Read information from the post type object or Types settings
			if( is_object( $post_type ) ) {
				$slug = $post_type->name;
				$is_permalink_rewriting_enabled = (bool) toolset_getarr( $post_type->rewrite, 'enabled' );
				$rewrite_slug = toolset_getarr( $post_type->rewrite, 'slug' );
				$is_custom_slug_used = !empty( $rewrite_slug );
			} else {
				$slug = toolset_getarr( $post_type, 'slug' );
				$is_permalink_rewriting_enabled = (bool) toolset_getnest( $post_type, array( 'rewrite', 'enabled' ) );
				$is_custom_slug_used = ( toolset_getnest( $post_type, array( 'rewrite', 'custom' ) ) == 'custom' );
				$rewrite_slug = toolset_getnest( $post_type, array( 'rewrite', 'slug' ) );
			}

			if( $slug == $exclude_id ) {
				continue;
			}

			if( $is_permalink_rewriting_enabled ) {
				$conflict_candidate = ( $is_custom_slug_used ? $rewrite_slug : $slug );

				if( $conflict_candidate == $value ) {

					$conflict = array(
						'conflicting_id' => $slug,
						'message' => sprintf(
							__( 'The same value is already used in permalink rewrite rules for the custom post type "%s". Using it again can cause issues with permalinks.', 'wpcf' ),
							esc_html( $slug )
						)
					);

					return $conflict;
				}
			}
		}

		// No conflicts found.
		return false;
	}


	/**
	 * Check a slug for conflict with slugs used for taxonomy permalink rewriting.
	 *
	 * @param string $value Value to check.
	 * @param string $exclude_id Taxonomy slug to exclude from checking.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 * @since 2.1
	 */
	public function check_slug_conflicts_in_taxonomy_rewrite_rules( $value, $exclude_id ) {

		// Merge currently registered taxonomies (which might include some from other plugins) and
		// Types settings (which might include deactivated taxonomies).
		$taxonomy_settings = array();
		if( defined( 'WPCF_OPTION_NAME_CUSTOM_TAXONOMIES' ) ) {
			$taxonomy_settings = toolset_ensarr( get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() ) );
		}
		$taxonomy_settings = array_merge( $taxonomy_settings, get_taxonomies( array(), 'objects' ) );

		foreach( $taxonomy_settings as $taxonomy ) {

			// Read information from the taxonomy object or Types settings
			if( is_object( $taxonomy ) ) {
				$slug = $taxonomy->name;
				$rewrite_slug = toolset_getarr( $taxonomy->rewrite, 'slug' );
				$is_permalink_rewriting_enabled = !empty( $rewrite_slug );
			} else {
				$slug = toolset_getarr( $taxonomy, 'slug' );
				$is_permalink_rewriting_enabled = (bool) toolset_getnest( $taxonomy, array( 'rewrite', 'enabled' ) );
				$rewrite_slug = toolset_getnest( $taxonomy, array( 'rewrite', 'slug' ) );
			}

			if( $slug == $exclude_id ) {
				continue;
			}

			// Detect if there is a conflict
			$is_custom_slug_used = !empty( $rewrite_slug );

			if( $is_permalink_rewriting_enabled ) {
				$conflict_candidate = ( $is_custom_slug_used ? $rewrite_slug : $slug );

				if( $conflict_candidate == $value ) {

					$conflict = array(
						'conflicting_id' => $slug,
						'message' => sprintf(
							__( 'The same value is already used in permalink rewrite rules for the taxonomy "%s". Using it again can cause issues with permalinks.', 'wpcf' ),
							esc_html( $slug )
						)
					);

					return $conflict;
				}
			}
		}

		// No conflicts found.
		return false;
	}


	/**
	 * Checks among registered post types and post types defined in Types, even if they are not active.
	 *
	 * Also checks for conflicts with post type slugs because of potential rewrite rule conflicts.
	 *
	 * @param string $value Candidate post type slug
	 * @param null $ignored
	 *
	 * @return array|false
	 * @since m2m
	 */
	public function check_post_type_slug_conflicts( $value, $ignored = null ) {

		// Handle terms reserved by WordPress.
		if( in_array( $value, $this->reserved_terms ) ) {
			$conflict = array(
				'conflicting_id' => $value,
				'message' => sprintf(
					__( 'The post type slug "%s" is reserved by WordPress.', 'wpcf' ),
					esc_html( $value )
				)
			);

			return $conflict;
		}

		$post_types = array_merge(
			array_keys( toolset_ensarr( get_option( WPCF_OPTION_NAME_CUSTOM_TYPES, array() ) ) ),
			get_post_types( array(), 'names' )
		);

		if( in_array( $value, $post_types, true ) ) {
			$conflict = array(
				'conflicting_id' => $value,
				'message' => sprintf(
					__( 'The post type slug "%s" is already registered or defined by Types.', 'wpcf' ),
					esc_html( $value )
				)
			);

			return $conflict;
		}


		global $wpdb;
		$conflicting_page = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page'",
				$value
			)
		);
		if ( !empty( $conflicting_page ) ) {
			$conflict = array(
				'conflicting_id' => $value,
				'message' => sprintf(
					__( 'The post type slug "%s" cannot be used because there is already a page by that name.', 'wpcf' ),
					esc_html( $value )
				)
			);

			return $conflict;
		}

		return false;
	}


	/**
	 * @param string $value Candidate relationship definition slug.
	 * @param null $ignored
	 *
	 * @return array|false
	 */
	public function check_relationship_slug_conflicts( $value, $ignored = null ) {

		// todo define reserved keywords

		$m2m_controller = Toolset_Relationship_Controller::get_instance();
		$m2m_controller->initialize_full();

		$query = new Toolset_Relationship_Query( array() );
		$relationships = $query->get_results();

		foreach( $relationships as $relationship_definition ) {
			if( $relationship_definition->get_slug() == $value ) {
				$conflict = array(
					'conflicting_id' => $value,
					'message' => sprintf(
						__( 'The relationship slug "%s" is already being used.', 'wpcf' ),
						$value
					)
				);

				return $conflict;
			}
		}

		// No conflict
		return false;
	}


	/**
	 * Check if the given value is unique within a domain.
	 *
	 * @param string $value Candidate name or slug.
	 * @param mixed $exclude_id An ID to exclude from checking (interpretation depends on the domain).
	 * @param string $domain One of the DOMAIN_* constants.
	 *
	 * @return bool
	 */
	public function is_name_unique( $value, $exclude_id, $domain ) {
		$domain_to_method = $this->get_domain_to_method_mapping();
		if( ! array_key_exists( $domain, $domain_to_method ) ) {
			throw new InvalidArgumentException( 'Domain not supported.' );
		}

		$conflict = call_user_func( array( $this, $domain_to_method[ $domain ] ), $value, $exclude_id );

		return ( false === $conflict );
	}


	private function get_valid_suffix_delimiter( $domain ) {
		switch( $domain ) {
			case self::DOMAIN_POST_TYPE_SLUGS:
				return '_';
			default:
				return '-';
		}
	}


	/**
	 * Generates an unique slug based on a candidate value in a way similar to WordPress.
	 *
	 * When a slug is already used, either add "-2" to it or increase the number at its end and try again
	 * until an unique one is found.
	 *
	 * @param string $slug_candidate
	 * @param mixed $exclude_id An ID to exclude from checking (interpretation depends on the domain).
	 * @param string $domain One of the DOMAIN_* constants.
	 *
	 * @return null|string An unique slug or null when it was not possible to generate it.
	 * @since m2m
	 */
	public function generate_unique_slug( $slug_candidate, $exclude_id, $domain ) {

		$slug_candidate = trim( $slug_candidate );
		if( empty( $slug_candidate ) ) {
			return null;
		}

		// If the slug is already unique, we're done.
		if( $this->is_name_unique( $slug_candidate, $exclude_id, $domain ) ) {
			return $slug_candidate;
		}

		$suffix_delimiter = $this->get_valid_suffix_delimiter( $domain );

		// If current slug has a number at it's end, we'll use it and start incrementing it. If not,
		// we will just add a number as a suffix.
		$slug_parts = explode( $suffix_delimiter, trim( $slug_candidate ) );
		// there will allways be at least one part
		$last_slug_part = $slug_parts[ count( $slug_parts ) - 1 ];

		if( is_numeric( $last_slug_part ) ) {
			$numeric_suffix =  $last_slug_part + 1;
			$slug_base = implode( ' ', array_slice( $slug_parts, 0, -1 ) );
		} else {
			$numeric_suffix = 2;
			$slug_base = $slug_candidate;
		}

		// Keep incrementing the suffix until an unique slug is found.
		do {
			$slug_candidate = $slug_base . $suffix_delimiter . $numeric_suffix;
			++$numeric_suffix;
		} while( ! $this->is_name_unique( $slug_candidate, $exclude_id, $domain ) );

		return $slug_candidate;

	}


	/**
	 * Generate a valid post type slug.
	 *
	 * Uses generate_unique_slug() and, in addition, ensures that the result is not longer than 20 characters,
	 * which is the limit imposed by WordPress database structure.
	 *
	 * It might lead to not very pretty but always reliable results.
	 *
	 * @param string $slug_candidate
	 *
	 * @return string Unique post type slug.
	 */
	public function generate_unique_post_type_slug( $slug_candidate ) {

		$trimming_length = self::MAX_POST_TYPE_SLUG_LENGTH;
		$slug_candidate_base = sanitize_title( $slug_candidate );

		do {
			$slug_candidate = substr( $slug_candidate_base, 0, $trimming_length );
			$slug_candidate = $this->generate_unique_slug( $slug_candidate, null, self::DOMAIN_POST_TYPE_SLUGS );
			--$trimming_length;
		} while( strlen( $slug_candidate ) > self::MAX_POST_TYPE_SLUG_LENGTH && $trimming_length > 0 );

		if( strlen( $slug_candidate ) > self::MAX_POST_TYPE_SLUG_LENGTH ) {
			// This will, in reality, never happen.
			return null;
		}

		return sanitize_title( $slug_candidate );
	}


	/**
	 * Validate a post type slug.
	 *
	 * Check that is not too long, is sanitized and (optionally) unique.
	 *
	 * @param string $post_type_slug
	 * @param bool $has_to_be_unique
	 *
	 * @return bool True if the post type is valid.
	 */
	public function is_post_type_slug_valid( $post_type_slug, $has_to_be_unique = true ) {
		if( sanitize_title( trim( $post_type_slug ) ) !== $post_type_slug ) {
			return false;
		}

		if( strlen( $post_type_slug ) > self::MAX_POST_TYPE_SLUG_LENGTH ) {
			return false;
		}

		if( $has_to_be_unique && ! $this->is_name_unique( $post_type_slug, null, self::DOMAIN_POST_TYPE_SLUGS  ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Returns array of reserved WordPress names (https://codex.wordpress.org/Reserved_Terms)
	 *
	 * @return array
	 *
	 * @since 2.5.2
	 */
	public function get_reserved_terms() {
		return $this->reserved_terms;
	}
}
