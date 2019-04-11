<?php

/**
 * Handle checking for slug conflicts among different domains.
 *
 * Used by the slug_conflict_checker.js script. Compares given value with all values within specified domains
 * and reports if a conflict is found.
 *
 * Currently supported domains are:
 * - Post type rewrite slugs (value of the slug used for permalink rewriting or the post type slug if rewriting is
 *   not enabled for that post type)
 * - Taxonomy rewrite slugs (analogous to post types)
 *
 */
class Types_Ajax_Handler_Check_Slug_Conflicts extends Types_Ajax_Handler_Abstract {

	// Definition of supported domains
	const DOMAIN_POST_TYPE_REWRITE_SLUGS = 'post_type_rewrite_slugs';
	const DOMAIN_TAXONOMY_REWRITE_SLUGS = 'taxonomy_rewrite_slugs';

	private static $supported_domains = array(
		self::DOMAIN_POST_TYPE_REWRITE_SLUGS,
		self::DOMAIN_TAXONOMY_REWRITE_SLUGS
	);


	/**
	 * todo document
	 * @param array $arguments Original action arguments.
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array( 'nonce' => Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS )
		);

		// Read and validate input
		$domains = wpcf_getpost( 'domains' );
		$value = wpcf_getpost( 'value' );
		$exclude = wpcf_getpost( 'exclude' );
		$exclude_id = wpcf_getarr( $exclude, 'id', 0 );
		$exclude_domain = wpcf_getarr( $exclude, 'domain' );
		$diff_domains = array_diff( $domains, self::$supported_domains );

		if( !is_array( $domains )
			|| !empty( $diff_domains )
			|| !is_string( $value )
			|| !is_array( $exclude )
			|| 0 === $exclude_id
			|| !in_array( $exclude_domain, self::$supported_domains )
		) {
			$this->ajax_finish( array(), false );
		}


		$conflict = $this->check_slug_conflicts( $value, $domains, $exclude_domain, $exclude_id );


		// Parse output (report a conflict if there is any)
		if( false === $conflict ) {
			$this->ajax_finish( array( 'isConflict' => false ), true );
		} else {

			$message = sprintf(
				'<strong>%s</strong>: %s',
				__( 'Warning', 'wpcf' ),
				wpcf_getarr( $conflict, 'message' )
			);

			$this->ajax_finish(
				array(
					'isConflict' => true,
					'displayMessage' => $message
				),
				true
			);
		}

	}


	/**
	 * Check given slug for conflicts across defined domains.
	 *
	 * @param string $value Value to check.
	 * @param string[] $domains Array of valid domains
	 * @param string $exclude_domain Domain of the excluded object.
	 * @param string|int|null $exclude_id Id of the excluded object.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 *
	 * @since 2.1
	 */
	private function check_slug_conflicts( $value, $domains, $exclude_domain, $exclude_id ) {

		foreach( $domains as $domain ) {
			$conflict = $this->check_slug_conflicts_in_domain( $value, $domain, ( $domain == $exclude_domain ) ? $exclude_id : null );
			if( false !== $conflict ) {
				return $conflict;
			}
		}

		// No conflicts found
		return false;
	}


	/**
	 * Check given slug for conflicts in one domain.
	 *
	 * @param string $value Value to check.
	 * @param string $domain Domain name.
	 * @param string|int|null $exclude_id ID of an object to exclude within this domain, or null if there is none.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 *
	 * @since 2.1
	 */
	private function check_slug_conflicts_in_domain( $value, $domain, $exclude_id = null ) {
		switch( $domain ) {
			case self::DOMAIN_POST_TYPE_REWRITE_SLUGS:
				return $this->check_slug_conflicts_in_post_type_rewrite_rules( $value, $exclude_id );
			case self::DOMAIN_TAXONOMY_REWRITE_SLUGS:
				return $this->check_slug_conflicts_in_taxonomy_rewrite_rules( $value, $exclude_id );
			default:
				return false;
		}
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
	private function check_slug_conflicts_in_taxonomy_rewrite_rules( $value, $exclude_id ) {

		// Merge currently registered taxonomies (which might include some from other plugins) and
		// Types settings (which might include deactivated taxonomies).
		$taxonomy_settings = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
		if( !is_array( $taxonomy_settings ) ) {
			return false;
		}
		$taxonomy_settings = array_merge( $taxonomy_settings, get_taxonomies( array(), 'objects' ) );

		foreach( $taxonomy_settings as $taxonomy ) {

			// Read information from the taxonomy object or Types settings
			if( is_object( $taxonomy ) ) {
				$slug = $taxonomy->name;
				$rewrite_slug = wpcf_getarr( $taxonomy->rewrite, 'slug' );
				$is_permalink_rewriting_enabled = !empty( $rewrite_slug );
			} else {
				$slug = wpcf_getarr( $taxonomy, 'slug' );
				$is_permalink_rewriting_enabled = (bool) wpcf_getnest( $taxonomy, array( 'rewrite', 'enabled' ) );
				$rewrite_slug = wpcf_getnest( $taxonomy, array( 'rewrite', 'slug' ) );
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
	 * Check a slug for conflict with slugs used for post type permalink rewriting.
	 *
	 * @param string $value Value to check.
	 * @param string $exclude_id Post type slug to exclude from checking.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 * @since 2.1
	 */
	private function check_slug_conflicts_in_post_type_rewrite_rules( $value, $exclude_id ) {

		// Merge currently registered post types (which might include some from other plugins) and
		// Types settings (which might include deactivated post types).
		$post_type_option = new Types_Utils_Post_Type_Option();
		$post_type_settings = $post_type_option->get_post_types();
		if( !is_array( $post_type_settings ) ) {
			return false;
		}
		$post_type_settings = array_merge( $post_type_settings, get_post_types( array(), 'objects' ) );

		foreach( $post_type_settings as $post_type ) {

			// Read information from the post type object or Types settings
			if( is_object( $post_type ) ) {
				$slug = $post_type->name;
				$is_permalink_rewriting_enabled = (bool) wpcf_getarr( $post_type->rewrite, 'enabled' );
				$rewrite_slug = wpcf_getarr( $post_type->rewrite, 'slug' );
				$is_custom_slug_used = !empty( $rewrite_slug );
			} else {
				$slug = wpcf_getarr( $post_type, 'slug' );
				$is_permalink_rewriting_enabled = (bool) wpcf_getnest( $post_type, array( 'rewrite', 'enabled' ) );
				$is_custom_slug_used = ( wpcf_getnest( $post_type, array( 'rewrite', 'custom' ) ) == 'custom' );
				$rewrite_slug = wpcf_getnest( $post_type, array( 'rewrite', 'slug' ) );
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

}