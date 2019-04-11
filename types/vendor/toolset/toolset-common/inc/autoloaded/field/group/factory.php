<?php

/**
 * Abstract factory for field group classes.
 *
 * It ensures that each field group is instantiated only once and it keeps returning that one instance.
 *
 * Note: Cache is indexed by slugs, so if a field group can change it's slug, it is necessary to do
 * an 'wpcf_field_group_renamed' action immediately after renaming.
 *
 * @since 1.9
 */
abstract class Toolset_Field_Group_Factory {


	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return Toolset_Field_Group_Factory Instance of calling class.
	 */
	public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}


	protected function __construct() {
		add_action( 'wpcf_field_group_renamed', array( $this, 'field_group_renamed' ), 10, 2 );
	}


	final private function __clone() { }


	/**
	 * For a given field domain, return the appropriate field group factory instance.
	 *
	 * @param string $domain Valid field domain
	 *
	 * @return Toolset_Field_Group_Factory
	 * @since 2.1
	 */
	public static function get_factory_by_domain( $domain ) {
		switch( $domain ) {
			case Toolset_Field_Utils::DOMAIN_POSTS:
				return Toolset_Field_Group_Post_Factory::get_instance();
			case Toolset_Field_Utils::DOMAIN_USERS:
				return Toolset_Field_Group_User_Factory::get_instance();
			case Toolset_Field_Utils::DOMAIN_TERMS:
				return Toolset_Field_Group_Term_Factory::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * @return string Post type that holds information about this field group type.
	 */
	abstract public function get_post_type();


	/**
	 * @return string Name of the class that represents this field group type (and that will be instantiated). It must
	 * be a child of Toolset_Field_Group.
	 */
	abstract protected function get_field_group_class_name();


	/**
	 * Get a post object that represents a field group.
	 *
	 * @param int|string|WP_Post $field_group Numeric ID of the post, post slug or a post object.
	 *
	 * @return null|WP_Post Requested post object when the post exists and has correct post type. Null otherwise.
	 */
	final protected function get_post( $field_group ) {

		$fg_post = null;

		// http://stackoverflow.com/questions/2559923/shortest-way-to-check-if-a-variable-contains-positive-integer-using-php
		if ( is_scalar( $field_group ) && ( $field_group == (int) $field_group ) && ( (int) $field_group > 0 ) ) {
			$fg_post = WP_Post::get_instance( $field_group );
		} else if ( is_string( $field_group ) ) {
			$query = new WP_Query( array( 'post_type' => $this->get_post_type(), 'name' => $field_group, 'posts_per_page' => 1 ) );
			if( $query->have_posts() ) {
				$fg_post = $query->get_posts();
				$fg_post = $fg_post[0];
			}
		} else {
			$fg_post = $field_group;
		}

		if( $fg_post instanceof WP_Post && $this->get_post_type() == $fg_post->post_type ) {
			return $fg_post;
		} else {
			return null;
		}
	}


	/**
	 * @var array Array of field group instances for this post type, indexed by names (post slugs).
	 */
	private $field_groups = array();


	/**
	 * @param string $field_group_name Name of the field group.
	 * 
     * @return null|Toolset_Field_Group Field group instance or null if it's not cached.
	 */
	private function get_from_cache( $field_group_name ) {
		return toolset_getarr( $this->field_groups, $field_group_name, null );
	}


	/**
	 * Save a field group instance to cache.
	 * 
	 * @param Toolset_Field_Group $field_group
	 */
	private function save_to_cache( $field_group ) {
		$this->field_groups[ $field_group->get_slug() ] = $field_group;
	}


	/**
	 * Remove field group instance from cache.
	 * @param string $field_group_name
	 */
	private function clear_from_cache( $field_group_name ) {
		unset( $this->field_groups[ $field_group_name ] );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group_source Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @return null|Toolset_Field_Group Field group or null if it can't be loaded.
	 */
	final public function load_field_group( $field_group_source ) {

		$post = null;

		// If we didn't get a field group name, we first need to get the post so we can look into the cache.
		if( !is_string( $field_group_source ) ) {
			$post = $this->get_post( $field_group_source );
			if( null == $post ) {
				// There is no such post (or has wrong type).
				return null;
			}
			$field_group_name = $post->post_name;
		} else {
			$field_group_name = $field_group_source;
		}

		// Try to get an existing instance.
		$field_group = $this->get_from_cache( $field_group_name );
		if( null != $field_group ) {
			return $field_group;
		}

		// We might already have the post by now.
		if( null == $post ) {
			$post = $this->get_post( $field_group_source );
		}

		// There is no such post (or has wrong type).
		if( null == $post ) {
			return null;
		}

		// Create new field group instance
		try {
			$class_name = $this->get_field_group_class_name();
			$field_group = new $class_name( $post );
		} catch( Exception $e ) {
			return null;
		}

		$this->save_to_cache( $field_group );
		return $field_group;
	}


	/**
	 * Update cache after a field group is renamed.
	 *
	 * @param string $original_name The old name of the field group.
	 * @param Toolset_Field_Group $field_group The field group involved, with already updated name.
	 */
	public function field_group_renamed( $original_name, $field_group ) {
		if( $field_group->get_post_type() == $this->get_post_type() ) {
			$this->clear_from_cache( $original_name );
			$this->save_to_cache( $field_group );
		}
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 * @param string $status Only 'draft'|'publish' are expected. Groups with the 'draft' status will appear as deactivated.
	 * @param string|null $purpose Field group purpose. Defaults to PURPOSE_GENERIC. Accepted values depend on the type of the field group.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 *
	 * @since 1.9
	 * @since m2m Added the 'purpose' argument.
	 *
	 * @refactoring ! Make this testable.
	 */
	final public function create_field_group( $name, $title = '', $status = 'draft', $purpose = null ) {

		if( sanitize_title( $name ) != $name ) {
			return null;
		}

		$title = wp_strip_all_tags( $title );

		$post_id = wp_insert_post(
			array(
				'post_type' => $this->get_post_type(),
				'post_name' => $name,
				'post_title' => empty( $title ) ? $name : $title,
				'post_status' => $status,
			)
		);

		if( 0 == $post_id ) {
			return null;
		}

		// Store the mandatory postmeta, just to be safe. I'm not sure about invariants here.
		update_post_meta( $post_id, Toolset_Field_Group::POSTMETA_FIELD_SLUGS_LIST, '' );

		$field_group = $this->load_field_group( $post_id );

		if( null === $purpose ) {
			$purpose = Toolset_Field_Group::PURPOSE_GENERIC;
		}
		$field_group->set_purpose( $purpose );

		$field_group->execute_group_updated_action();

		return $field_group;
	}


	/**
	 * Get field groups based on query arguments.
	 *
	 * @param array $query_args Optional. Arguments for the WP_Query that will be applied on the underlying posts.
	 *     Post type query is added automatically.
	 *     Additional arguments are allowed:
	 *     - 'types_search': String for extended search. See WPCF_Field_Group::is_match() for details.
	 *     - 'is_active' bool: If defined, only active/inactive field groups will be returned.
	 *     - 'purpose' string: See Toolset_Field_Group::get_purpose(). Default is Toolset_Field_Group::PURPOSE_GENERIC.
	 *        Special value '*' will return groups of all purposes.
	 * 
	 * @return Toolset_Field_Group[]
	 * @since 1.9
	 * @since m2m Added the 'purpose' argument.
	 * @refactoring ! Make the code testable.
	 */
	public function query_groups( $query_args = array() ) {

		// Read specific arguments
		$search_string = toolset_getarr( $query_args, 'types_search' );
		$is_active = toolset_getarr( $query_args, 'is_active', null );
		$purpose = toolset_getarr( $query_args, 'purpose', Toolset_Field_Group::PURPOSE_GENERIC );

		// Query posts
		$query_args = array_merge( $query_args, array( 'post_type' => $this->get_post_type(), 'posts_per_page' => -1 ) );
		$meta_query = array();

		// Group's "activeness" is defined by the post status.
		if( null !== $is_active ) {
			unset( $query_args['is_active'] );
			$query_args['post_status'] = ( $is_active ? 'publish' : 'draft' );
		}

		// Group's purpose is stored in a postmeta
		if( '*' !== $purpose ) {
			$meta_query[] = array(
				'key' => Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
				'value' => $purpose,
			);

			// Also handle the missing postmeta for generic groups
			if( Toolset_Field_Group::PURPOSE_GENERIC === $purpose ) {
				$meta_query[] = array(
					'key' => Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
					'compare' => 'NOT EXISTS',
					'value' => $purpose, // we might need any non-empty value for the meta query to work because bug #23268
				);

				$meta_query['relation'] = 'OR';
			}
		}
		unset( $query_args['purpose'] );

		if( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query = new WP_Query( $query_args );
		$posts = $query->get_posts();

		// Transform posts into Toolset_Field_Group
		$all_groups = array();
		foreach( $posts as $post ) {
			$field_group = $this->load_field_group( $post );
			if( null != $field_group ) {
				$all_groups[] = $field_group;
			}
		}

		// Filter groups by the search string.
		$selected_groups = array();
		if( empty( $search_string ) ) {
			$selected_groups = $all_groups;
		} else {
			/** @var Toolset_Field_Group $group */
			foreach ( $all_groups as $group ) {
				if ( $group->is_match( $search_string ) ) {
					$selected_groups[] = $group;
				}
			}
		}

		return $selected_groups;
	}


	/**
	 * Get a map of all field group slugs to their display names.
	 * 
	 * @return string[]
	 * @since 2.0
	 */
	public function get_group_slug_to_displayname_map() {
		$groups = $this->query_groups( array( 'purpose' => '*' ) );
		$group_names = array();
		foreach( $groups as $group ) {
			$group_names[ $group->get_slug() ] = $group->get_display_name();
		}
		return $group_names;
	}

}