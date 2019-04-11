<?php

/**
 * Provides a safer access to the option with post type values.
 *
 * If the option value is a standard serialized array, the performance is negligible. However, if the option is
 * malformed by some sort of search-replace in string values (stored string length doesn't match the actual one),
 * it will try to salvage the situation without the user even noticing.
 *
 * It specifically fixes on focusing a bug caused by WordPress 4.8.3 where in a certain situation the post type labels,
 * which contain the %s placeholder, are damaged by replacing the '%' character by another placeholder (see
 * https://make.wordpress.org/core/2017/10/31/changed-behaviour-of-esc_sql-in-wordpress-4-8-3/ for details).
 *
 * If the option needs to be fixed, we will further try to detect these placeholders in post type labels
 * and replace them with '%s'. It is very unlikely that someone would save a value like '{18184a8b66ef}s' inside
 * the label AND that the option becomes malformed at the same time, so we take the risk and replace it with '%s'.
 *
 * In order to be able to do this, we need to access the wp_options table directly, because get_option() calls
 * unserialize() before a filter we could reasonably hook into, and at that point we already get just a 'false' value.
 *
 * As a consequence, all occurences of "get_option( WPCF_OPTION_NAME_CUSTOM_TYPES, array() )" must be replaced
 * by a call to Types_Utils_Post_Type_Option::get_post_types().
 *
 * The solution is inspired by https://github.com/Blogestudio/Fix-Serialization/blob/master/fix-serialization.php
 *
 * IMPORTANT: Beware that this class is being manually loaded even before Toolset Common in some cases. Do not move it
 * without considering that and do not use anything fancy like toolset_ensarr() here.
 *
 * @since 2.2.18
 */
class Types_Utils_Post_Type_Option {


	/**
	 * Get the post types option.
	 *
	 * @return array
	 */
	public function get_post_types() {
		$post_types = get_option( WPCF_OPTION_NAME_CUSTOM_TYPES, array() );

		if ( ! is_array( $post_types ) ) {
			$raw_value = $this->get_raw_option();
			if ( is_string( $raw_value ) && ! empty( $raw_value ) ) {
				// Now we know that something went seriously wrong AND we probably have post types to save.
				$post_types = $this->try_fix_serialized_array( $raw_value );
				$post_types = maybe_unserialize( $post_types );
				$post_types = $this->try_fix_post_type_labels( $post_types );
			}
		}

		if ( ! is_array( $post_types ) ) {
			return array();
		}

		return $post_types;
	}


	/**
	 * Get the raw WPCF_OPTION_NAME_CUSTOM_TYPES option from the database.
	 *
	 * @return null|string
	 */
	private function get_raw_option() {
		global $wpdb;

		$option_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				WPCF_OPTION_NAME_CUSTOM_TYPES
			)
		);

		return $option_value;
	}


	/**
	 * Restore a broken serialized array by fixing string lengths.
	 *
	 * @param $broken_serialized_array
	 * @return string
	 */
	private function try_fix_serialized_array( $broken_serialized_array ) {
		$output = preg_replace_callback(
			'!s:(\d+):([\\\\]?"[\\\\]?"|[\\\\]?"((.*?)[^\\\\])[\\\\]?");!',
			array( $this, 'preg_replace_callback' ),
			$broken_serialized_array
		);

		return $output;
	}


	/**
	 * Fix a string length for a single occurence.
	 *
	 * @param array $matches
	 * @return string
	 */
	private function preg_replace_callback( $matches ) {
		if ( count( $matches ) < 4 ) {
			// empty string
			return $matches[0];
		}

		$stored_string = $matches[3];
		$string_mysql_unescaped = $this->unescape_mysql( $stored_string );
		$string_length = strlen( $string_mysql_unescaped );
		$string_without_quotes = $this->unescape_quotes( $stored_string );

		$replacement = 's:' . $string_length . ':"' . $string_without_quotes . '";';

		return $replacement;
	}


	/**
	 * Update the post types option
	 * @param $post_types
	 */
	public function update_post_types( $post_types ) {
		update_option( WPCF_OPTION_NAME_CUSTOM_TYPES, $post_types, true );
	}


	/**
	 * Unescape to avoid dump-text issues.
	 *
	 * @param string $value
	 * @return string
	 */
	private function unescape_mysql( $value ) {
		return str_replace(
			array( "\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"' ),
			array( "\\", "\0", "\n", "\r", "\x1a", "'", '"' ),
			$value
		);
	}


	/**
	 * Fix strange behaviour if you have escaped quotes in your replacement
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function unescape_quotes( $value ) {
		return str_replace( '\"', '"', $value );
	}


	/**
	 * @param array $post_types
	 * @return array
	 */
	private function try_fix_post_type_labels( $post_types ) {
		foreach ( $post_types as $key => $post_type ) {
			if ( ! array_key_exists( 'labels', $post_type ) ) {
				continue;
			}

			foreach ( $post_type['labels'] as $label_name => $label_value ) {
				$fixed_label = preg_replace( '/\{[a-f0-9]{8,}\}s/', '%s', $label_value );
				$post_types[ $key ]['labels'][ $label_name ] = $fixed_label;
			}
		}

		return $post_types;
	}

}

