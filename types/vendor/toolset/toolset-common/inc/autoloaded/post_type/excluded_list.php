<?php

/**
 * Provide an unified list of special post types that should be excluded from most listings and operations.
 *
 * @since m2m
 */
class Toolset_Post_Type_Exclude_List {

	private static $initial_list = array(
		Toolset_Post_Type_List::CRED_POST_FORM,
		Toolset_Post_Type_List::CRED_USER_FORM,
		Toolset_Post_Type_List::CRED_RELATIONSHIP_FORM,
		Toolset_Post_Type_List::LAYOUT,
		Toolset_Post_Type_List::VIEW_OR_WPA,
		Toolset_Post_Type_List::CONTENT_TEMPLATE,
		Toolset_Post_Type_List::POST_FIELD_GROUP,
		Toolset_Post_Type_List::USER_FIELD_GROUP,
		Toolset_Post_Type_List::TERM_FIELD_GROUP,
		'custom_css',
		'customize_changeset',
		'deprecated_log',
		'mediapage',
		'nav_menu_item',
		'revision',
		'acf-field-group',
		'acf',
		'otgs_ps_bundles',
	);


	private $excluded_post_types = null;


	/**
	 * Retrieve the values.
	 *
	 * Note that the result is cached.
	 *
	 * @return array
	 */
	public function get() {
		if ( null === $this->excluded_post_types ) {

			/**
			 * Filter that allows to add own post types which will be not used in Toolset plugins.
			 *
			 * @param string[] $post_types array of post type slugs.
			 * @since m2m
			 */
			$this->excluded_post_types = toolset_ensarr(
				apply_filters( 'toolset_filter_exclude_own_post_types', self::$initial_list )
			);
		}

		return $this->excluded_post_types;
	}


	/**
	 * Check whether a specific post type is excluded.
	 *
	 * @param string $post_type_slug
	 * @return bool
	 */
	public function is_excluded( $post_type_slug ) {
		$excluded_post_types = $this->get();
		$is_excluded = in_array( $post_type_slug, $excluded_post_types, true );
		return $is_excluded;
	}

}