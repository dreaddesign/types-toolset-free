<?php

/**
 * Factory for instantiating IToolset_Post_Type instances.
 *
 * Never use directly. For obtaining these models, see Toolset_Post_Type_Repository.
 *
 * @since m2m
 */
class Toolset_Post_Type_Factory {

	/**
	 * @param string $slug Post type slug.
	 * @param array $definition The definition array from Types.
	 * @param IToolset_Post_Type_Registered|null $registered_post_type If the post type is registered on the site,
	 *     this must not be null.
	 *
	 * @return IToolset_Post_Type_From_Types
	 */
	public function post_type_from_types( $slug, $definition, IToolset_Post_Type_Registered $registered_post_type = null ) {
		return new Toolset_Post_Type_From_Types( $slug, $definition, $registered_post_type );
	}


	/**
	 * @param WP_Post_Type $wp_post_type The core object representing the post type.
	 *
	 * @return IToolset_Post_Type_Registered
	 */
	public function registered_post_type( WP_Post_Type $wp_post_type ) {
		return new Toolset_Post_Type_Registered( $wp_post_type );
	}
}