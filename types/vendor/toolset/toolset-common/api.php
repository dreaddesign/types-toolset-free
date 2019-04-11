<?php
/**
 * List of all available public filters.
 *
 * These filters should only be used to get data (by using apply_filters()) and not be extended (by using add_filter())
 */

/**
 * Filter: toolset_m2m_translated_relationships
 * to get all relationships, which include at least one translated post type
 *
 * @param mixed $user_value will be returned if no relationships with translated post types are available
 *
 * @return IToolset_Relationship_Definition[]
 * @since m2m
 */
if( ! function_exists( 'toolset_filter_toolset_m2m_translated_relationships' ) ) {
	function toolset_filter_toolset_m2m_translated_relationships( $user_value ) {
		do_action( 'toolset_do_m2m_full_init' );

		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// m2m not active
			return $user_value;
		}

		$relationships_with_translated_types = Toolset_Relationship_Utils::get_all_translated_relationships();
		return ! empty( $relationships_with_translated_types ) ? $relationships_with_translated_types : $user_value;
	}
}

add_filter( 'toolset_m2m_translated_relationships', 'toolset_filter_toolset_m2m_translated_relationships' );