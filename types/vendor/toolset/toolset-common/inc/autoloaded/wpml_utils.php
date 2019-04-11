<?php

/**
 * WPML-related helper functions.
 *
 * @deprecated Do not add new methods. Merge this class with Toolset_Wpml_Compatibility.
 *
 * @since m2m
 */
class Toolset_Wpml_Utils {


	/**
	 * @return string icl_translations table name.
	 * @deprecated Use Toolset_WPML_Compatibility::icl_translations_table_name
	 */
	public static function icl_translations_tn() {
		global $wpdb;
		return $wpdb->prefix . 'icl_translations';
	}


	/**
	 * Query elements from the icl_translations table by translation group id.
	 *
	 * @param $trid
	 * @return array Element IDs.
	 */
	public static function query_elements_by_trid( $trid ) {
		global $wpdb;

		$icl_tn = self::icl_translations_tn();
		$query = $wpdb->prepare(
			"SELECT element_id FROM `{$icl_tn}` WHERE trid = %d",
			$trid
		);

		$results = $wpdb->get_col( $query );

		return $results;
	}


	/**
	 * Query translation group ID from a single icl_translations row ID.
	 *
	 * @param $translation_id
	 * @return int trid or zero.
	 */
	public static function get_trid_from_translation_id( $translation_id ) {
		global $wpdb;

		$icl_tn = self::icl_translations_tn();
		$query = $wpdb->prepare(
			"SELECT trid FROM `{$icl_tn}` WHERE translation_id = %d LIMIT 1",
			$translation_id
		);

		$trid = $wpdb->get_var( $query );

		return (int) $trid;
	}


	/**
	 * Query translation group ID by element ID and type.
	 *
	 * @param $element_id
	 * @param string $element_type Element type or at least part of the value.
	 * @return int trid or zero.
	 */
	public static function get_trid_from_element_id( $element_id, $element_type = 'post_%' ) {
		global $wpdb;

		$icl_tn = self::icl_translations_tn();
		$query = $wpdb->prepare(
			"SELECT trid FROM `{$icl_tn}` WHERE element_id = %d AND element_type LIKE %s LIMIT 1",
			$element_id, $element_type
		);

		$trid = $wpdb->get_var( $query );

		return (int) $trid;
	}


	/**
	 * Retrieve the translation group ID for a post.
	 *
	 * @param int $post_id
	 * @return int "trid" value or zero.
	 * @since m2m
	 * @deprecated Use Toolset_WPML_Compatibility::get_post_trid
	 */
	public static function get_post_trid( $post_id ) {
		global $wpdb;

		$icl_translations_table = self::icl_translations_tn();

		$query = $wpdb->prepare(
			"SELECT trid
			FROM `{$icl_translations_table}`
			WHERE
				element_type LIKE %s
				AND element_id = %d
			LIMIT 1",
			'post_%',
			$post_id
		);

		return (int) $wpdb->get_var( $query );
	}


	/**
	 * Get an array of post translation IDs from the icl_translations table, indexed by language codes.
	 *
	 * @param int $post_id
	 * @return int[]
	 * @since m2m
	 * @deprecated Use Toolset_WPML_Compatibility::get_post_translations_directly
	 */
	public static function get_post_translations_directly( $post_id ) {

		// todo check for the situations when the icl_translations table is not there
		// todo consider using WPML hooks if they're available

		global $wpdb;

		$icl_translations_table = self::icl_translations_tn();

		$trid = self::get_post_trid( $post_id );

		if( null == $trid ) {
			return array();
		}

		$query = $wpdb->prepare(
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

		$db_results = $wpdb->get_results( $query );

		// Return an associative array of post IDs.
		$results = array();
		foreach( $db_results as $row ) {
			$results[ $row->language_code ] = (int) $row->post_id;
		}

		return $results;

	}


	/**
	 * Check if a post type is translatable.
	 *
	 * @param string $post_type_slug
	 * @return bool
	 * @since m2m
	 * @deprecated Use Toolset_WPML_Compatibility::is_post_type_translatable() instead.
	 */
	public static function is_post_type_translatable( $post_type_slug ) {
		return Toolset_WPML_Compatibility::get_instance()->is_post_type_translatable( $post_type_slug );
	}


	/**
	 * Get the current language.
	 *
	 * Cached.
	 *
	 * @return string
	 * @since m2m
	 * @deprecated Use Toolset_WPML_Compatibility::get_current_language()
	 */
	public static function get_current_language() {
		return Toolset_WPML_Compatibility::get_instance()->get_current_language();
	}


	/**
	 * Get the default site language.
	 *
	 * Cached.
	 *
	 * @return string
	 * @since m2m
	 * @deprecated Use Toolset_WPML_Compatibility::get_default_language()
	 */
	public static function get_default_language() {
		return Toolset_WPML_Compatibility::get_instance()->get_default_language();
	}


	/**
	 * From the set of available translations, choose the "best" one.
	 *
	 * Best means current language > default language > any language.
	 *
	 * @param string[] $available_translations Language codes to choose from. Must not be empty
	 * @param bool $always_return_something If neither the curent nor the default translations are available,
	 *     either choose a random one or return null.
	 *
	 * @return null|string
	 * @throws InvalidArgumentException
	 */
	public static function choose_best_translation( $available_translations, $always_return_something = false ) {
		if( ! is_array( $available_translations ) || empty( $available_translations ) ) {
			throw new InvalidArgumentException();
		}

		if( count( $available_translations ) === 1 ) {
			return array_pop( $available_translations );
		} elseif( array_key_exists( self::get_current_language(), $available_translations ) ) {
			return self::get_current_language();
		} elseif( array_key_exists( self::get_default_language(), $available_translations ) ) {
			return self::get_default_language();
		} elseif( $always_return_something ) {
			// Failsafe
			return array_pop( $available_translations );
		} else {
			return null;
		}
	}


	/**
	 * Check whether a default language of a post exists.
	 *
	 * Returns true if WPML is not active, as in that case, all posts are considered
	 * to be in the "default" language.
	 *
	 * Also returns true if the provided post itself is in the default language.
	 *
	 * @param int $post_id ID of the post
	 * @return boolean
	 * @since m2m
	 */
	public static function has_default_language_translation( $post_id ) {
		$wpml_service = Toolset_WPML_Compatibility::get_instance();
		if( ! $wpml_service->is_wpml_active_and_configured() ) {
			return true;
		}

		$default_language = $wpml_service->get_default_language();

		$translated_post_id = apply_filters( 'wpml_object_id', (int) $post_id, 'any', false, $default_language );

		return ( null !== $translated_post_id );
	}


	/**
	 * Gets active languages information
	 *
	 * @return array
	 * @since m2m
	 * @link https://wpml.org/documentation/getting-started-guide/language-setup/custom-language-switcher/
	 */
	public static function get_active_languages() {
		return apply_filters( 'wpml_active_languages', null, '' );
	}
}
