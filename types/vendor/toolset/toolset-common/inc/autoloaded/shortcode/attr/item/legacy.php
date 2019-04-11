<?php

/**
 * Class Toolset_Shortcode_Attr_Item_Legacy
 *
 * Adds support for the Types legacy format, like "$parent" and "$post-type".
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Legacy extends Toolset_Shortcode_Attr_Item_Id {
	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $chain_link;

	/**
	 * @var Toolset_Relationship_Service
	 */
	private $service_relationship;

	/**
	 * Toolset_Shortcode_Attr_Item_Legacy constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $chain_link
	 * @param Toolset_Relationship_Service $service
	 *
	 * @internal param Types_Wordpress_Post $wp_post_api
	 */
	public function __construct( Toolset_Shortcode_Attr_Interface $chain_link, Toolset_Relationship_Service $service ) {
		$this->chain_link           = $chain_link;
		$this->service_relationship = $service;

	}

	/**
	 * @param array $data
	 *
	 * @return $this|int ->chain_link->get();
	 */
	public function get( array $data ) {
		if ( ! $role_slug = $this->handle_attr_synonyms( $data ) ) {
			return $this->chain_link->get( $data );
		}

		if ( substr( $role_slug, 0, 1 ) != '$' ) {
			// legacy format must start with $
			return $this->chain_link->get( $data );
		}

		global $post;

		if ( ! is_object( $post ) || ! property_exists( $post, 'ID' ) || ! property_exists( $post, 'post_type' ) ) {
			// no data without $post
			return $this->chain_link->get( $data );
		}

		$role_slug = substr( $role_slug, 1 );

		if( $role_slug == 'parent' ) {
			if ( property_exists( $post, 'post_parent' ) && ! empty( $post->post_parent ) ) {
				// this targets the wp build-in relationship between posts of same post type (hierarchical cpt)
				return $this->return_single_id( $post->post_parent );
			}

			return $this->chain_link->get( $data );
		}
		
		if( $role_slug == 'current_page' ) {
			if ( is_single() || is_page() ) {
				global $wp_query;
				if ( isset( $wp_query->posts[0] ) ) {
					$current_post = $wp_query->posts[0];
					return $this->return_single_id( $current_post->ID );
				}
			}
			
			/**
			 * Get the current top post.
			 *
			 * Some Toolset plugins might need to set the top current post under some scenarios,
			 * like Toolset Views when doing AJAX pagination or AJAX custom search.
			 * In those cases, they can use this filter to get the top current post they are setting
			 * and override the ID to apply as the $current_page value.
			 *
			 * @not Toolset plugins should set this just in time, not globally, when needed, meaning AJAX calls or whatever.
			 *
			 * @param $top_post 	null
			 *
			 * @return $top_post 	null/WP_Post object 	The top current post, if set by any Toolset plugin.
			 *
			 * @since 2.3.0
			 */
			$top_current_post = apply_filters( 'toolset_filter_get_top_current_post', null );
			if ( $top_current_post ) {
				return $this->return_single_id( $top_current_post->ID );
			}

			return $this->chain_link->get( $data );
		}

		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// m2m disabled
			if( $requested_id = $this->service_relationship->legacy_find_parent_id_by_child_id_and_parent_slug( $post->ID, $role_slug ) ) {
				return $this->return_single_id( $requested_id );
			}

			return $this->chain_link->get( $data );
		}

		// find parent by using the slug (to support legacy use of the shortcode [types id="$parent_slug"])
		$parents_with_specific_slug = $this->service_relationship->find_parents_by_child_id_and_parent_slug( $post->ID, $role_slug );

		if( count( $parents_with_specific_slug ) > 1 ) {
			// todo show a message to the admin that he should replace the old shortcode structure by the new
			// as long as this message is not implemented we show the first found item (with the foreach after this if block).
		}

		foreach( $parents_with_specific_slug as $parent ) {
			return $this->return_single_id( $parent->get_id() );
		}

		return $this->chain_link->get( $data );
	}
}