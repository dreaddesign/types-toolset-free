<?php

/**
 * Post field group.
 *
 * @since 2.0
 */
class Types_Field_Group_Post extends Types_Field_Group {


	const POST_TYPE = 'wp-types-group';

	/**
	 * Postmeta that contains a comma-separated list of post type slugs where this field group is assigned.
	 *
	 * Note: There might be empty items in the list: ",,,post-type-slug,," Make sure to avoid those.
	 *
	 * Note: Empty value means "all groups". There also may be legacy value "all" with the same meaning.
	 *
	 * @since unknown
	 */
	const POSTMETA_POST_TYPE_LIST = '_wp_types_group_post_types';


	/**
	 * @param WP_Post $field_group_post Post object representing a post field group.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $field_group_post ) {
		parent::__construct( $field_group_post );
		if( self::POST_TYPE != $field_group_post->post_type ) {
			throw new InvalidArgumentException( 'incorrect post type' );
		}
	}


	/**
	 * @return WPCF_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected function get_field_definition_factory() {
		return WPCF_Field_Definition_Factory_Post::get_instance();
	}

	/**
	 * Assign a post type to the group
	 * @param $post_type
	 */
	public function assign_post_type( $post_type ) {
		$post_types = $this->get_assigned_to_types();
		$post_types[] = $post_type;

		$this->store_post_types( $post_types );
	}

	/**
	 * Stores an array of post types as list in database
	 *
	 * @param array $post_types
	 */
	protected function store_post_types( $post_types ) {
		// validate post types
		foreach( $post_types as $type ) {
			if( empty( $type ) || ! get_post_type( $type ) )
				unset( $post_types[$type] );
		}

		$post_types = empty( $post_types )
			? ''
			: implode( ',', $post_types );

		update_post_meta( $this->get_id(), self::POSTMETA_POST_TYPE_LIST, $post_types );
	}


	/**
	 * @inheritdoc
	 *
	 * @return array
	 * @since 2.1
	 */
	protected function fetch_assigned_to_types() {
		$db_assigned_to = get_post_meta( $this->get_id(), self::POSTMETA_POST_TYPE_LIST, true );

		// in old types version we store "all"
		if ( 'all' == $db_assigned_to ) {
			return array();
		}

		// Keep your eyes open on storing values,
		// This is needed because legacy code produces values like ,,,,a-post-type,,
		$db_assigned_to = trim( $db_assigned_to, ',' );

		// empty means all post types are selected
		if ( empty( $db_assigned_to ) ) {
			return array();
		}

		// we have selected post types
		return explode( ',', $db_assigned_to );

	}


	/**
	 * @inheritdoc
	 * @return WP_Post[] Individual posts using this group.
	 * @since 2.1
	 */
	protected function fetch_assigned_to_items() {
		$assigned_posts = $this->get_assigned_to_types();

		if( empty( $assigned_posts ) ) {
			$assigned_posts = array( 'all' );
		}

		$items = get_posts(
			array(
				'post_type' => $assigned_posts,
				'post_status' => 'any',
				'posts_per_page' => -1,
			)
		);

		return $items;
	}


	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . '/admin.php?page=wpcf-edit&group_id=' . $this->get_id();
	}

}