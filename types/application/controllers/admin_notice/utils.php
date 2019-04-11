<?php

/**
 * Class of helper functions that don't fit anywhere else.
 *
 * @since 1.9
 */
final class Types_Utils {


	/**
	 * Shortcut method for retrieving public built-in taxonomies.
	 *
	 * @param string $output_mode 'objects'|'names'
	 *
	 * @return object[] Array of taxonomy objects or names.
	 * @since 1.9
	 */
	public static function get_builtin_taxonomies( $output_mode = 'objects' ) {
		// todo add simple caching
		return get_taxonomies( array( 'public' => true, '_builtin' => true ), $output_mode );
	}


	/**
	 * Get a list of taxonomies that should be excluded from any sort of Types/Toolset functionality.
	 *
	 * @return string[] Taxonomy slugs.
	 * @since 2.2.9
	 */
	public static function get_excluded_taxonomies() {
		return array( 'nav_menu', 'link_category', 'post_format' );
	}


	/**
	 * Get registered taxonomies that can be edited by Types/Toolset.
	 *
	 * That includes custom taxonomies from Types and the built-in ones (which are not excluded).
	 *
	 * @return WP_Taxonomy[] Taxonomy objects.
	 * @since 2.2.9
	 */
	public static function get_editable_taxonomies() {
		$custom_taxonomies = array_keys( wpcf_ensarr( get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() ) ) );
		$builtin_taxonomies = self::get_builtin_taxonomies( 'names' );
		$allowed_taxonomies = array_merge( $custom_taxonomies, $builtin_taxonomies );

		$excluded_taxonomies = self::get_excluded_taxonomies();
		$allowed_taxonomies = array_diff( $allowed_taxonomies, $excluded_taxonomies );

		$taxonomies = get_taxonomies( '', 'objects' );
		foreach( $taxonomies as $taxonomy_slug => $taxonomy ) {
			if( ! in_array( $taxonomy_slug, $allowed_taxonomies ) ) {
				unset( $taxonomies[ $taxonomy_slug ] );
			}
		}

		return $taxonomies;
	}


	/**
	 * Get a definitive set of all taxonomies recognized by Types.
	 *
	 * That also includes inactive taxonomies.
	 * Respects if some builtin taxonomy is overridden by Types.
	 *
	 * @return array
	 * @since 1.9
	 */
	public static function get_all_taxonomies() {
		// todo add simple caching
		$taxonomies = array();

		// Read Types taxonomies first.
		$types_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
		if ( is_array( $types_taxonomies ) ) {
			foreach ( $types_taxonomies as $slug => $data ) {
				$taxonomies[ $slug ] = $data;
			}
		}

		// Get all taxonomies and add them to the set, but avoid overwriting Types taxonomies
		$all_taxonomies = self::object_to_array_deep( get_taxonomies( array( 'public' => true ) , 'objects' ) );
		foreach ( $all_taxonomies as $slug => $data ) {
			// check if taxonomies are already saved as custom taxonomies
			if ( isset( $taxonomies[ $slug ] ) ) {
				continue;
			}

			if ( ! isset( $data['slug'] ) ) {
				$data['slug'] = $slug;
			}

			$taxonomies[ $slug ] = $data;
		}

		return $taxonomies;
	}


	/**
	 * Transform an object and all it's fields recursively into an associative array. If any object's field is
	 * an array, individual elements of the array will be transformed as well.
	 *
	 * @param object|array $object The object or array of objects to transform.
	 * @return array
	 * @since 1.9
	 */
	public static function object_to_array_deep( $object ) {
		if ( is_array( $object ) || is_object( $object ) ) {
			$result = array();
			foreach ( $object as $key => $value ) {
				$result[ $key ] = self::object_to_array_deep( $value );
			}

			return $result;
		}

		return $object;
	}


	/**
	 * Try to convert a taxonomy slug to a label.
	 *
	 * @param string $slug Taxonomy slug.
	 * @param string $label_name One of the available labels of the taxonomy.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/get_taxonomies Taxonomy object description.
	 *
	 * @return string Selected taxonomy label or slug if the label was not found.
	 * @since 1.9
	 */
	public static function taxonomy_slug_to_label( $slug, $label_name = 'name' ) {
		$all_taxonomies = self::get_all_taxonomies();

		$taxonomy_display_name = wpcf_getnest( $all_taxonomies, array( $slug, 'labels', $label_name ), $slug );

		return $taxonomy_display_name;
	}


	/**
	 * Check if searched string is a substring of the value.
	 *
	 * @param string $search_string
	 * @param string $value
	 * @return bool
	 * @since 1.9
	 */
	public static function is_string_match( $search_string, $value ) {
		return ( false !== strpos( mb_strtolower( $value ), mb_strtolower( trim( $search_string ) ) ) );
	}


	/**
	 * Insert elements into source array at a specified position.
	 *
	 * @param array $source Source array.
	 * @param array $to_insert Array of elements to insert.
	 * @param int|array $position When integer is provided, zero or positive value means index of the first element that
	 *     will not be included before $to_insert. Negative value defines the position from the end of the source array
	 *     (-1 will insert at the very end, -2 before last element, etc.). When an array is provided, it is expected to
	 *     have form:
	 *     - 'key': Key to select an element in the source array
	 *     - 'where': Insert 'before'|'after' the selected element
	 *
	 * @return array
	 * @since 1.9.1
	 */
	public static function insert_at_position( $source, $to_insert, $position) {

		if( is_array( $position ) ) {
			$pivot_key = wpcf_getarr( $position, 'key', null );
			$direction = wpcf_getarr( $position, 'where', 'after', array( 'after', 'before' ) );

			if( array_key_exists( $pivot_key, $source ) ) {
				$pivot_index = array_search( $pivot_key, array_keys( $source ) );
				$position = ( 'before' == $direction ) ? $pivot_index : $pivot_index + 1;
			} else {
				$position = ( 'before' == $direction ) ? 0 : -1;
			}
		}

		// $position should be index of the first element that will NOT be included before $to_insert.
		$position = (int) $position;

		if( 0 > $position ) {
			// E.g.: When $position == -1, the inserted elements should be placed after the last element of $source.
			// $position will point after the last element of $source, new elements will be inserted after it.
			$position = count( $source ) + 1 + $position;
			//echo "pos=$position\n";

			// Handle too low $position value - insert elements before whole $source.
			if( 0 > $position ) {
				$position = 0;
			}
		}

		$first_source_part = array_slice( $source, 0, $position );
		$second_source_part = array_slice( $source, $position );
		$result = array_merge( $first_source_part, $to_insert, $second_source_part );

		return $result;
	}


	/**
	 * Return an ID of an attachment by searching the database with the file URL.
	 *
	 * First checks to see if the $url is pointing to a file that exists in
	 * the wp-content directory. If so, then we search the database for a
	 * partial match consisting of the remaining path AFTER the wp-content
	 * directory. Finally, if a match is found the attachment ID will be
	 * returned.
	 *
	 * Taken from:
	 * @link http://frankiejarrett.com/get-an-attachment-id-by-url-in-wordpress/
	 *
	 * @param string $url URL of the file.
	 * @return int|null Attachment ID if it exists.
	 * @since 1.9.1
	 * @deprecated Since 2.2.7. Use Toolset_Utils::get_attachment_id_by_url() instead. To be removed in 2.3 or later.
	 */
	public static function get_attachment_id_by_url( $url ) {

		// Split the $url into two parts with the wp-content directory as the separator.
		$parsed_url = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );

		// Return null if image is not on domain (WP_CONTENT_URL).
		if ( count( $parsed_url ) === 1 ) {
			return null;
		}

		// Get the host of the current site and the host of the $url, ignoring www.
		$this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
		$file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );

		// Return nothing if there aren't any $url parts or if the current host and $url host do not match.
		$attachment_path = $parsed_url[1];
		if ( ! isset( $attachment_path ) || empty( $attachment_path ) || ( $this_host != $file_host ) ) {
			return null;
		}

		// Now we're going to quickly search the DB for any attachment GUID with a partial path match.
		// Example: /uploads/2013/05/test-image.jpg
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s",
			'%' . $attachment_path
		);

		$attachment = $wpdb->get_col( $query );

		if ( is_array( $attachment ) && ! empty( $attachment ) ) {
			return array_shift( $attachment );
		}

		return null;
	}


}
