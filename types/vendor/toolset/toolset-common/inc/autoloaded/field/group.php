<?php

/**
 * Abstract representation of a field group (without defined type).
 *
 * Descendants of this class allways must be instantiated through appropriate factory class inheriting
 * from Toolset_Field_Group_Factory.
 *
 * Any method that permanently changes any group data needs to call execute_group_updated_action() afterwards.
 *
 * @since 1.9
 */
abstract class Toolset_Field_Group {

	/**
	 * Stores the slugs of the fields that belong to this group, as a comma separated list.
	 */
	const POSTMETA_FIELD_SLUGS_LIST = '_wp_types_group_fields';


	const FIELD_SLUG_DELIMITER = ',';


	/**
	 * Postmeta key where the field group purpose is stored.
	 *
	 * If the postmeta is missing or empty, PURPOSE_GENERIC is the default value.
	 *
	 * @since m2M
	 */
	const POSTMETA_GROUP_PURPOSE = 'types_field_group_purpose';


	/** Default field group purpose: Simply a group that can be assigned to multiple post types. */
	const PURPOSE_GENERIC = 'generic';


	/**
	 * @var WP_Post Post object that represents the field group.
	 */
	protected $post;


	/** @var Toolset_Post_Type_Repository|null */
	private $_post_type_repository;


	/**
	 * Toolset_Field_Group constructor.
	 *
	 * Note that it is protected - inheriting classes have to implement an override that should, additionally, check
	 * if the post has correct type.
	 *
	 * @param WP_Post $field_group_post Post object that represents the field group.
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 * @throws InvalidArgumentException
	 */
	protected function __construct( $field_group_post, Toolset_Post_Type_Repository $post_type_repository_di = null ) {
		if( ! $field_group_post instanceof WP_Post ) {
			throw new InvalidArgumentException( 'First argument is not a post.' );
		}
		$this->post = $field_group_post;

		$this->_post_type_repository = $post_type_repository_di;
	}


	/**
	 * @return Toolset_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected abstract function get_field_definition_factory();


	/**
	 * @return WP_Post Post object representing the field group.
	 */
	protected function get_post() {
		return $this->post;
	}


	/**
	 * @return int ID of the field group.
	 */
	public function get_id() {
		return $this->get_post()->ID;
	}


	/**
	 * @return string Unique name (post slug) of the field group.
	 */
	public function get_slug() {
		return sanitize_title( $this->get_post()->post_name );
	}


	/**
	 * @return string Field group description. Sanitized as a text field.
	 */
	public function get_description() {
		return sanitize_text_field( $this->get_post()->post_content );
	}


	/**
	 * Note that for displaying group name to the user you should use get_display_name() instead.
	 *
	 * @return string Field group title.
	 */
	public function get_name() {
		return sanitize_text_field( $this->get_post()->post_title );
	}


	/**
	 * Get group name as it should be displayed to the user.
	 *
	 * Handles string translation if applicable.
	 */
	public function get_display_name() {
		return wpcf_translate(
			sprintf( 'group %d name', $this->get_id() ),
			$this->get_name()
		);
	}


	/**
	 * @param null|bool $value If boolean value is provided, the group will be activated or deactivated accordingly.
	 *     For null, nothing happens.
	 * @return bool True if the field group is active, false if deactivated.
	 * @since 1.9
	 */
	public function is_active( $value = null ) {

		if( null !== $value ) {
			$this->update_post( array( 'post_status' => ( $value ? 'publish' : 'draft' ) ) );
		}

		$post = $this->get_post();
		return ( $post->post_status == 'publish' ? true : false );
	}


	/**
	 * @return string Status of the underlying post. Limited to 'publish'|'draft' (default).
	 */
	public function get_post_status() {
		return ( $this->is_active() ? 'publish' : 'draft' );
	}


	/**
	 * @return int ID of the user who edited the field group last.
	 */
	public function get_author() {
		$post = $this->get_post();
		return (int) $post->post_author;
	}


	/*
	 * $group['meta_box_context'] = 'normal';
    $group['meta_box_priority'] = 'high';
    $group['filters_association'] = get_post_meta( $post->ID, '_wp_types_group_filters_association', true );
	 */


	/**
	 * Change name of the field group.
	 *
	 * Do not confuse with the title. *All* changes of the name must happen through this method, otherwise
	 * unexpected behaviour of the Toolset_Field_Group_Factory can occur.
	 *
	 * @param string $value New value of the post name. Note that it may be further modified by WordPress before saving.
	 */
	public function set_name( $value ) {
		$result = $this->update_post( array( 'post_name' => sanitize_title( $value ) ) );
		if( true == $result ) {
			do_action( 'wpcf_field_group_renamed', $value, $this );
		}
	}


	/**
	 * Update the underlying post object.
	 *
	 * Also refreshes the stored post object and fires an action notifying about the change.
	 *
	 * @param array $args Arguments for wp_update_post(). ID doesn't have to be provided, it will be added automatically.
	 * @return bool True on success, false otherwise.
	 * @since 2.1
	 */
	private function update_post( $args ) {

		$args = array_merge( array( 'ID' => $this->get_id() ), $args );

		$updated_post_id = wp_update_post( $args );

		if( 0 !== $updated_post_id ) {
			// Refresh the post object
			$this->post = WP_Post::get_instance( $updated_post_id );

			$this->execute_group_updated_action();
			return true;
		} else {
			return false;
		}
	}


	/**
	 * @return string The underlying post type of the post representing the field group.
	 */
	public function get_post_type() {
		$post = $this->get_post();
		return $post->post_type;
	}


	/**
	 * @var null|array Array of field slugs that belong to this field group, or null if it was not loaded from database yet.
	 */
	private $field_slugs = null;


	/**
	 * Check if a field slug is valid and can be stored in the database.
	 *
	 * @param string $field_slug
	 * @return bool True if the slug is valid.
	 */
	public function is_valid_field_slug( $field_slug ) {
		return !empty( $field_slug ) && is_string( $field_slug ) && ( sanitize_title( $field_slug ) == $field_slug );
	}


	/**
	 * Check if an array of field slugs is valid.
	 *
	 * @param array|mixed $field_slugs
	 * @return bool True if an array of valid field slugs was provided.
	 */
	protected function validate_field_slugs( $field_slugs ) {
		if( !is_array( $field_slugs ) ) {
			return false;
		}

		// Every element needs to be valid
		if( count( $field_slugs ) != count ( array_filter( $field_slugs, array( $this, 'is_valid_field_slug' ) ) ) ) {
			return false;
		}

		return true;
	}


	/**
	 * @return array Slugs of fields that belong to this field group, or empty array when database doesn't contain
	 * valid data.
	 *
	 * Consider using get_field_definitions() instead.
	 */
	public function get_field_slugs() {
		if( null == $this->field_slugs ) {
			$field_slug_list = get_post_meta( $this->get_id(), self::POSTMETA_FIELD_SLUGS_LIST, true );

			/**
			 * Filter 'types_post_field_group_fields'
			 * Allows to change the fields list of a post group.
			 *
			 * @since m2m
			 */
			$field_slug_list = apply_filters( 'types_post_field_group_fields', $field_slug_list );

			if( !is_string( $field_slug_list ) ) {
				$field_slug_list = '';
			}

			$field_slugs = explode( self::FIELD_SLUG_DELIMITER, $field_slug_list );

			// Remove empty slugs
			foreach( $field_slugs as $key => $slug ) {
				if( empty( $slug ) ) {
					unset( $field_slugs[ $key ] );
				}
			}

			$this->field_slugs = $this->validate_field_slugs( $field_slugs ) ? $field_slugs : array();
		}
		return $this->field_slugs;
	}


	/**
	 * Get all existing definitions of fields that belong to this group.
	 *
	 * @return Toolset_Field_Definition[]
	 */
	public function get_field_definitions() {
		$slugs = $this->get_field_slugs();
		$field_definitions = array();
		$factory = $this->get_field_definition_factory();
		foreach( $slugs as $slug ) {
			$field_definition = $factory->load_field_definition( $slug );
			if( null != $field_definition && $field_definition->is_managed_by_types() ) {
				$field_definitions[] = $field_definition;
			}
		}
		return $field_definitions;
	}


	/**
	 * Update the set of field slugs that belong to this field group.
	 *
	 * @param array $field_slugs Array of valid field slugs.
	 * @return bool True if the database was updated successfully, false otherwise.
	 */
	protected function set_field_slugs( $field_slugs ) {
		if( !$this->validate_field_slugs( $field_slugs ) ) {
			return false;
		}

		$this->field_slugs = $field_slugs;

		$field_slug_list = implode( self::FIELD_SLUG_DELIMITER, $field_slugs );

		$updated = update_post_meta( $this->get_id(), self::POSTMETA_FIELD_SLUGS_LIST, $field_slug_list );

		$this->execute_group_updated_action();

		return ( $updated != false );
	}


	/**
	 * Remove field definition from this group.
	 *
	 * @param Toolset_Field_Definition $field_definition
	 *
	 * @return bool
	 */
	public function remove_field_definition( $field_definition ) {

		if( ! ( $field_definition instanceof Toolset_Field_Definition ) ) {
			return false;
		}

		$field_slugs = $this->get_field_slugs();

		$slug_to_remove = $field_definition->get_slug();
		$key = array_search( $slug_to_remove, $field_slugs );
		if( $key !== false ) {
			unset( $field_slugs[ $key ] );
			$is_success = $this->set_field_slugs( $field_slugs );
		} else {
			$is_success = true;
		}

		return $is_success;
	}


	/**
	 * Associate a field definition with this group.
	 *
	 * @param Toolset_Field_Definition $field_definition
	 *
	 * @return bool True on success, false otherwise.
	 * @since 2.0
	 */
	public function add_field_definition( $field_definition ) {

		if( ! ( $field_definition instanceof Toolset_Field_Definition ) ) {
			return false;
		}

		$field_slugs = $this->get_field_slugs();

		$slug_to_add = $field_definition->get_slug();
		if( !in_array( $slug_to_add, $field_slugs ) ) {
			$field_slugs[] = $slug_to_add;
			$is_success = $this->set_field_slugs( $field_slugs );
		} else {
			$is_success = true;
		}

		return $is_success;
	}


	/**
	 * Check if a string is contained within the field group definition.
	 *
	 * Searches in ID, slug, title and description. Case insensitive.
	 *
	 * @param string $search_string String to look for.
	 * @return bool True if found.
	 */
	public function is_match( $search_string ) {
		return (
			Types_Utils::is_string_match( $search_string, $this->get_id() )
			|| Types_Utils::is_string_match( $search_string, $this->get_slug() )
			|| Types_Utils::is_string_match( $search_string, $this->get_name() )
			|| Types_Utils::is_string_match( $search_string, $this->get_display_name() )
			|| Types_Utils::is_string_match( $search_string, $this->get_description() )
		);
	}


	public function contains_field_definition( $field_definition ) {
		if( $field_definition instanceof Toolset_Field_Definition ) {
			return in_array( $field_definition, $this->get_field_definitions(), true );
		} elseif( is_string( $field_definition ) ) {
			return in_array( $field_definition, $this->get_field_slugs(), true );
		} else {
			return false;
		}
	}


	/**
	 * Execute the wpcf_group_updated action.
	 *
	 * Needs to be called after each (permanent) change to a group.
	 */
	public function execute_group_updated_action() {

		/**
		 * Executed after a group has been updated in the database.
		 *
		 * @param int $id Group ID.
		 * @param Toolset_Field_Group $group The group object.
		 *
		 * @since 1.9
		 */
		do_action( 'wpcf_group_updated', $this->get_id(), $this );
	}


	/**
	 * @var null|false|string[] Cache for get_assigned_to_types().
	 *
	 * @since 2.1
	 */
	private $assigned_to_types = null;


	/**
	 * Fetch all post types assigned to this group from the database.
	 *
	 * @return false|string[] Array of post type slugs (empty array meaning "all post types") or false if not applicable.
	 * @since 2.1
	 */
	protected function fetch_assigned_to_types() {
		return false;
	}


	/**
	 * Get all post types assigned to this group from the database.
	 *
	 * Returns an array of post type slugs or false if not applicable. Empty array means either
	 * "all post types" for generic-purpose field groups or "nothing" for special-purpose field group.
	 * You can either check this with $this->has_special_purpose() or, even simpler, check for a specific
	 * post type with $this->is_assigned_to_type( $type ).
	 *
	 * Note: The result is cached.
	 *
	 * @return false|string[]
	 * @since 2.1
	 * @since m2m The meaning of empty array has changed for special-purpose field groups.
	 */
	public function get_assigned_to_types() {
		if( null === $this->assigned_to_types ) {
			$this->assigned_to_types = $this->fetch_assigned_to_types();
		}

		return $this->assigned_to_types;
	}


	/**
	 * Updates the assinged types
	 *
	 * After assigned a new assined type, this method must be called in order to update the cached property `assigned_to_types`
	 *
	 * @param String[] $types List of types
	 */
	protected function update_assigned_types( $types ) {
		$this->assigned_to_types = $types;
	}


	private $assigned_to_items = null;


	/**
	 * Determine whether this group is assigned to a particular post type.
	 *
	 * Takes into account special purpose groups.
	 *
	 * @param string|IToolset_Post_Type $type_input
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function is_assigned_to_type( $type_input ) {

		if ( $type_input instanceof IToolset_Post_Type ) {
			$post_type_slug = $type_input->get_slug();
			$post_type = $type_input;
		} elseif( is_string( $type_input ) || is_int( $type_input )) {
			$post_type_slug = $type_input;
			$post_type = null;
		} else {
			throw new InvalidArgumentException( 'Invalid argument type for the post type.' );
		}

		$assigned_types = $this->get_assigned_to_types();

		// Type assignment is not applicable here.
		if( false === $assigned_types ) {
			return false;
		}

		// Empty array means either "all post types" for generic-purpose field groups
		// or "nothing" for special-purpose field group.
		if( empty( $assigned_types ) ) {
			if( $this->has_special_purpose() ) {
				return false;
			} else {
				// Ok, we have a generic field group assigned to "all post types".
				// Now, the post type has a chance to reject the group.
				$post_type = (
					null === $post_type
						? $this->get_post_type_repository()->get( $post_type_slug )
						: $post_type
				);
				if( null === $post_type ) {
					// Can't get the post type, assume default behaviour.
					return true;
				}
				return $post_type->allows_field_group( $this );
			}
		}

		// Check explicit type assignment.
		return in_array( $post_type_slug, $assigned_types, true );
	}


	/**
	 * Fetch all items that are using this group.
	 *
	 * @return array|false Array of items (whose type varies upon the field domain) or false if not applicable.
	 * @since 2.1
	 */
	protected function fetch_assigned_to_items() {
		return false;
	}


	/**
	 * Get all items that are using this group.
	 *
	 * Cached.
	 *
	 * @return array|false Array of items (whose type varies upon the field domain) or false if not applicable.
	 * @since 2.1
	 */
	public function get_assigned_to_items() {
		if( null === $this->assigned_to_items ) {
			$this->fetch_assigned_to_items();
		}

		return $this->assigned_to_items;
	}


	/**
	 * Keys in the group export object.
	 *
	 * @since 2.1
	 */
	const XML_ID = 'ID';
	const XML_SLUG = 'slug';
	const XML_NAME = 'post_title';
	const XML_TYPE = 'post_type';
	const XML_DESCRIPTION = 'post_content';
	const XML_IS_ACTIVE = 'is_active';
	const XML_LEGACY_EXCERPT = 'post_excerpt';
	const XML_LEGACY_POST_STATUS = 'post_status';
	const XML_META_SECTION = 'meta';


	/**
	 * Get raw data of this field group to be exported.
	 *
	 * @refactoring This should go to a dedicated viewmodel.
	 *
	 * @return array
	 * @since 2.1
	 */
	protected function get_export_fields() {

		$field_slugs = $this->get_field_slugs();
		$field_slugs = implode( ',', $field_slugs );

		$result = array(
			self::XML_ID => $this->get_id(),
			self::XML_SLUG => $this->get_slug(),
			self::XML_NAME => $this->get_name(),
			self::XML_TYPE => $this->get_post_type(),
			self::XML_IS_ACTIVE => $this->is_active(),
			self::XML_LEGACY_EXCERPT => '',
			self::XML_LEGACY_POST_STATUS => $this->get_post_status(),
			self::XML_META_SECTION => array(
				self::POSTMETA_FIELD_SLUGS_LIST => $field_slugs
			),
			WPCF_AUTHOR => $this->get_author()
		);

		return $result;
	}


	/**
	 * Get array of keys that should be used for generating field group checksum.
	 *
	 * @return string[]
	 * @since 2.1
	 */
	protected function get_keys_for_export_checksum() {
		return array(
			self::XML_NAME, self::XML_TYPE, self::XML_LEGACY_EXCERPT, self::XML_LEGACY_POST_STATUS, self::XML_IS_ACTIVE,
			WPCF_AUTHOR, self::XML_META_SECTION
		);
	}


	/**
	 * Create an export object for this group, containing complete information including checksum and annotation.
	 *
	 * @return array
	 * @since 2.1
	 */
	public function get_export_object() {

		$data = $this->get_export_fields();

		$ie_controller = Types_Import_Export::get_instance();

		$data = $ie_controller->add_checksum_to_object( $data, $this->get_keys_for_export_checksum() );

		$data = $ie_controller->annotate_object( $data, $this->get_name(), $this->get_slug() );

		return $data;
	}


	/**
	 * Check whether the field group needs to be handled separately from others.
	 *
	 * One-purpose field groups are helper structures which should not appear in listings
	 * and the user should not be able to assign, unassign or delete them (they can edit group's
	 * fields, title, etc.).
	 *
	 * Furthermore, special purpose field groups that have no assignment
	 * should not be assigned to all element types by default.
	 *
	 * So far, this is properly implemented only for post field groups.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function has_special_purpose() {
		return ( $this->get_purpose() !== self::PURPOSE_GENERIC );
	}


	/**
	 * Get field group purposes that are acceptable for this type of field group (depending on its domain).
	 *
	 * @return string[]
	 * @since m2m
	 */
	protected function get_allowed_group_purposes() {
		return array(
			self::PURPOSE_GENERIC,
		);
	}


	/**
	 * Get the group purpose.
	 *
	 * See has_special_purpose() for details.
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_purpose() {
		$purpose = get_post_meta( $this->get_id(), self::POSTMETA_GROUP_PURPOSE, true );

		if (
			! is_string( $purpose )
			|| ! in_array( $purpose, $this->get_allowed_group_purposes(), true )
		) {
			$purpose = self::PURPOSE_GENERIC;
		}

		return $purpose;
	}


	/**
	 * Set the group purpose.
	 *
	 * See has_special_purpose() for details.
	 *
	 * @param string $purpose
	 * @throws InvalidArgumentException If the purpose value is not on the list of allowed ones.
	 * @since m2m
	 */
	public function set_purpose( $purpose ) {
		if ( ! in_array( $purpose, $this->get_allowed_group_purposes(), true ) ) {
			throw new InvalidArgumentException( 'Unable to set an invalid field group purpose' );
		}

		update_post_meta( $this->get_id(), self::POSTMETA_GROUP_PURPOSE, $purpose );
	}


	/**
	 * @return Toolset_Post_Type_Repository
	 */
	protected function get_post_type_repository() {
		if( null === $this->_post_type_repository ) {
			$this->_post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}
		return $this->_post_type_repository;
	}


	/**
	 * @return bool
	 * @since 2.8
	 */
	public function contains_repeating_field_group() {
		// Note: Must not be overridden until types-1593 is implemented. @refactoring
		$has_rfg = array_reduce( $this->get_field_slugs(), function( $has_rfg, $field_slug ) {
			return $has_rfg || (bool) preg_match( '/(' . Types_Field_Group_Repeatable::PREFIX . '[a-z0-9_]+)/', $field_slug );
		}, false );

		return $has_rfg;
	}
}
