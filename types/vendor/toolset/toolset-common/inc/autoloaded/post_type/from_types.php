<?php

/**
 * Represents a post type defined in Types.
 *
 * This post type may or may not be registered in the site. If it is, it should get a Toolset_Post_Type_Registered
 * instance in the constructor.
 *
 * Always use Toolset_Post_Type_Repository for obtaining instances.
 *
 * @since m2m
 */
class Toolset_Post_Type_From_Types extends Toolset_Post_Type_Abstract implements IToolset_Post_Type_From_Types {

	/** @var string Post type slug */
	private $slug;


	/** @var array Definition array coming from Types */
	private $definition;


	/** @var IToolset_Post_Type_Registered|null If the type is currently registered, we'll have the model here. */
	private $registered_post_type;


	/** @var Toolset_Constants */
	private $constants;


	// Various constants for the definition array.
	//
	// There should be no need for using these constants outside of the class.

	const DEF_LABELS = 'labels';

	const DEF_SLUG = 'slug';

	const DEF_REWRITE = 'rewrite';

	const DEF_REWRITE_SLUG = 'slug';

	const DEF_IS_BUILTIN = '_builtin';

	const DEF_LAST_EDIT_AUTHOR = '_wpcf_author_id'; // WPCF_AUTHOR

	const DEF_IS_INTERMEDIARY_POST_TYPE = 'is_intermediary_post_type';

	const DEF_IS_REPEATING_FIELD_GROUP = 'is_repeating_field_group';

	const DEF_PUBLIC = 'public';

	const DEF_DISABLED = 'disabled';

	// Do not rename this value, it's hardcoded in Types (because of timing issues).
	const DEF_NEEDS_FLUSH_REWRITE_RULES = '_needs_flush_rewrite_rules';


	/**
	 * Toolset_Post_Type_From_Types constructor.
	 *
	 * @param string $slug Post type slug.
	 * @param array $definition The definition array from Types.
	 * @param IToolset_Post_Type_Registered|null $registered_post_type If the post type is registered on the site,
	 *     this must not be null.
	 * @param Toolset_Constants|null $constants_di
	 * @param Toolset_WPML_Compatibility|null $wpml_compatibility_di
	 */
	public function __construct(
		$slug, $definition,
		IToolset_Post_Type_Registered $registered_post_type = null,
		Toolset_Constants $constants_di = null,
		Toolset_WPML_Compatibility $wpml_compatibility_di = null
	) {
		parent::__construct( $wpml_compatibility_di );

		$this->slug = $slug;

		/**
		 * Allow third party to adjust the post type definition.
		 *
		 * This filter is defined primarily in Types, here we'll just keep it to make sure
		 * we'll get the same data as the mechanism that handles post type registration.
		 *
		 * @since unknown
		 */
		$sanitized_definition = $this->sanitize_definition( $definition );

		$filtered_definition = apply_filters( 'types_post_type', $sanitized_definition, $slug );

		if( $filtered_definition !== $sanitized_definition ) {
			$filtered_definition = $this->sanitize_definition( $filtered_definition );
		}

		$this->definition = $filtered_definition;

		$this->registered_post_type = $registered_post_type;

		$this->constants = ( null === $constants_di ? new Toolset_Constants() : $constants_di );
	}


	/**
	 * Sanitize a single post type label value on a definition array.
	 *
	 * Default to an empty string if the label is missing entirely.
	 *
	 * @param array &$definition The definition array from Types
	 * @param string $label_name
	 */
	private function sanitize_label( & $definition, $label_name ) {

		$label_value = toolset_getnest( $definition, array( self::DEF_LABELS, $label_name ), '' );

		if( empty( $label_value ) ) {
			$label_value = '';

			if( in_array( $label_name, Toolset_Post_Type_Labels::mandatory() ) ) {
				$label_value = $definition['slug'];
			}
		}

		// It is important to avoid saving empty values: If the element is missing entirely,
		// Types will use the default value instead. Saving an empty string can break the GUI.
		if( ! empty( $label_value ) ) {
			$definition[ self::DEF_LABELS ][ $label_name ] = sanitize_text_field( $label_value );
		}
	}


	/**
	 * Sanitize the definition array from Types.
	 *
	 * Tries to mimick the behaviour from the Edit Post Type page in Types.
	 * When a singular or plural labels are missing, they will be replaced by a post slug.
	 *
	 * @param array $definition
	 *
	 * @return array Sanitized definition array.
	 */
	private function sanitize_definition( $definition ) {

		$definition = wp_parse_args( $definition, $this->get_default_definition() );

		$slug = sanitize_title( toolset_getarr( $definition, self::DEF_SLUG ), $this->slug );

		$definition[ self::DEF_SLUG ] = $slug;

		$definition[ self::DEF_LABELS ] = toolset_ensarr( toolset_getarr( $definition, self::DEF_LABELS ) );

		$labels = Toolset_Post_Type_Labels::all();
		foreach ( $labels as $label ) {
			$this->sanitize_label( $definition, $label );
		}

		if( empty( $definition[ self::DEF_LABELS ][ Toolset_Post_Type_Labels::NAME ] ) ) {
			$this->set_label( Toolset_Post_Type_Labels::NAME, $slug );
		}

		if( empty( $definition[ self::DEF_LABELS ][ Toolset_Post_Type_Labels::SINGULAR_NAME ] ) ) {
			$this->set_label( Toolset_Post_Type_Labels::SINGULAR_NAME, $slug );
		}

		if ( isset( $definition[ self::DEF_REWRITE ][ self::DEF_REWRITE_SLUG ] ) ) {

			$rewrite_slug = toolset_getnest( $definition, array( self::DEF_REWRITE, self::DEF_REWRITE_SLUG ), '' );
			$rewrite_slug = trim( strtolower( remove_accents( $rewrite_slug ) ) );

			$definition[ self::DEF_REWRITE ][ self::DEF_REWRITE_SLUG ] = $rewrite_slug;
		}

		// We're not using this class for built-in types now, but in case this changes:
		// if ( wpcf_is_builtin_post_types($definition['slug']) ) {
		//     $definition['_builtin'] = true;
		// }

		return $definition;
	}


	/**
	 * Get default values for the definition array.
	 *
	 * Taken from wpcf_custom_types_default() from Types.
	 *
	 * @return array
	 */
	private function get_default_definition() {
		return array(
			'labels' => array(
				'name' => '',
				'singular_name' => '',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New %s',
				'edit_item' => 'Edit %s',
				'new_item' => 'New %s',
				'view_item' => 'View %s',
				'search_items' => 'Search %s',
				'not_found' => 'No %s found',
				'not_found_in_trash' => 'No %s found in Trash',
				'parent_item_colon' => 'Parent %s',
				'menu_name' => '%s',
				'all_items' => '%s',
			),
			'slug' => '',
			'description' => '',
			'public' => self::DEF_PUBLIC,
			'capabilities' => array(),
			'menu_position' => null,
			'menu_icon' => '',
			'taxonomies' => array(), // This is a legacy option, do not use anymore.
			'supports' => array(
				'title' => true,
				'editor' => true,
				'trackbacks' => false,
				'comments' => false,
				'revisions' => false,
				'author' => false,
				'excerpt' => false,
				'thumbnail' => false,
				'custom-fields' => false,
				'page-attributes' => false,
				'post-formats' => false,
			),
			'rewrite' => array(
				'enabled' => true,
				'slug' => '',
				'with_front' => true,
				'feeds' => true,
				'pages' => true,
			),
			'has_archive' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_menu_page' => '',
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'hierarchical' => false,
			'query_var_enabled' => true,
			'query_var' => '',
			'can_export' => true,
			'show_rest' => false,
			'rest_base' => '',
			'show_in_nav_menus' => true,
			'register_meta_box_cb' => '',
			'permalink_epmask' => 'EP_PERMALINK',
			'update' => false,
		);
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * Never use directly: Change the slug via Toolset_Post_Type_Repository::rename() instead.
	 *
	 * @param string $new_value
	 */
	public function set_slug( $new_value ) {
		$new_slug = sanitize_title( $new_value );
		$this->slug = $new_slug;
		$this->definition[ self::DEF_SLUG ] = $new_slug;
	}



	/**
	 * Get the definition array from Types.
	 *
	 * Do not use directly if possible: Instead, implement the getter you need.
	 *
	 * @return array
	 */
	public function get_definition() {
		return $this->definition;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	function is_from_types() {
		return true;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	function is_registered() {
		return ( null !== $this->registered_post_type );
	}


	/**
	 * @inheritdoc
	 * @return WP_Post_Type|null
	 */
	public function get_wp_object() {
		if( ! $this->is_registered() ) {
			return null;
		}

		return $this->registered_post_type->get_wp_object();
	}


	/**
	 * @return IToolset_Post_Type_Registered|null
	 */
	public function get_registered_post_type() {
		if( ! $this->is_registered() ) {
			return null;
		}

		return $this->registered_post_type;
	}


	/**
	 * @param IToolset_Post_Type_Registered $registered_post_type
	 * @since 2.6.3
	 */
	public function set_registered_post_type( IToolset_Post_Type_Registered $registered_post_type ) {
		if( $registered_post_type->get_slug() !== $this->get_slug() ) {
			throw new InvalidArgumentException();
		}

		$this->registered_post_type = $registered_post_type;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_intermediary() {
		return $this->get_flag_from_definition( self::DEF_IS_INTERMEDIARY_POST_TYPE );
	}


	/**
	 * Flag a (fresh) post type as an intermediary one.
	 *
	 * @param bool $should_stay_visible
	 */
	public function set_as_intermediary( $should_stay_visible = false ) {
		$this->set_flag_to_definition( self::DEF_IS_INTERMEDIARY_POST_TYPE, true );

		if( ! $should_stay_visible ) {
			$this->set_is_public( false );

			// We probably should set all other visibility-related properties to false (it might cause
			// problems if we decide to set an existing visible post as intermediary).
		}
	}


	/**
	 * Remove the intermediary flag from the post type.
	 */
	public function unset_as_intermediary() {
		$this->set_flag_to_definition( self::DEF_IS_INTERMEDIARY_POST_TYPE, false );
	}


	/**
	 * "touch" the post type before saving, update the timestamp and user who edited it last.
	 */
	public function touch() {
		$this->definition[ $this->constants->constant( 'TOOLSET_EDIT_LAST' ) ] = time();
		$this->definition[ self::DEF_LAST_EDIT_AUTHOR ] = get_current_user_id();
	}


	public function get_last_edit_author() {
		return (int) toolset_getarr( $this->definition, self::DEF_LAST_EDIT_AUTHOR, 0 );
	}


	public function get_last_edit_timestamp() {
		return (int) toolset_getarr( $this->definition, $this->constants->constant( 'TOOLSET_EDIT_LAST' ), 0 );
	}


	private function get_flag_from_definition( $key ) {
		return ( array_key_exists( $key, $this->definition ) && $this->definition[ $key ] );
	}


	private function set_flag_to_definition( $key, $value ) {
		$this->definition[ $key ] = (bool) $value;
	}


	/**
	 * @inheritdoc
	 * @param string $label_name
	 * @return string
	 */
	public function get_label( $label_name = Toolset_Post_Type_Labels::NAME ) {
		$label = toolset_getnest( $this->definition, array( self::DEF_LABELS, $label_name ), '' );

		if ( ! empty( $label ) ) {
			return $label;
		} elseif ( Toolset_Post_Type_Labels::NAME !== $label_name ) {
			return $this->get_label( Toolset_Post_Type_Labels::NAME );
		} else {
			return $this->get_slug();
		}
	}


	/**
	 * Set a specific post type label.
	 * @param string $label_name Label name from Toolset_Post_Type_Labels.
	 * @param string $value Value of the label.
	 */
	public function set_label( $label_name, $value ) {
		$all_labels = Toolset_Post_Type_Labels::all();
		if ( ! in_array( $label_name, $all_labels ) ) {
			throw new InvalidArgumentException();
		}

		$this->definition[ self::DEF_LABELS ][ $label_name ] = sanitize_text_field( $value );
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_builtin() {
		return (bool) toolset_getarr( $this->definition, self::DEF_IS_BUILTIN );
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_public() {
		return (
			'public' === toolset_getarr( $this->definition, self::DEF_PUBLIC, 'hidden', array( 'hidden', 'public' ) )
		);
	}


	/**
	 * Set the 'public' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_public( $value ) {
		$this->definition[ self::DEF_PUBLIC ] = ( $value ? 'public' : 'hidden' );
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_disabled() {
		return (
			'1' === toolset_getarr( $this->definition, self::DEF_DISABLED, '', array( '1', '' ) )
		);
	}


	/**
	 * Set the 'disabled' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_disabled( $value ) {
		if( $value ){
			$this->definition[ self::DEF_DISABLED ] = '1';
		} else {
			unset( $this->definition[ self::DEF_DISABLED ] );
		}
	}

	/**
	 * Set the flag indicating whether this post type acts as a repeating field group.
	 *
	 * @param bool $value
	 */
	public function set_is_repeating_field_group( $value ) {
		$this->set_flag_to_definition( self::DEF_IS_REPEATING_FIELD_GROUP, (bool) $value );
		if ( $value ) {
			$this->definition['supports'] = array( 'post_title' => 1, 'author' => 1, 'custom-fields' => 1 );
		}
		$this->set_is_public( false );
	}


	/**
	 * @return bool True if the post type is used as a repeating field group.
	 */
	public function is_repeating_field_group() {
		return $this->get_flag_from_definition( self::DEF_IS_REPEATING_FIELD_GROUP );
	}


	/**
	 * @return bool True if the post type has a special purpose and shouldn't be used elsewhere.
	 */
	public function has_special_purpose() {
		return ( $this->is_intermediary() || $this->is_repeating_field_group() );
	}

	/**
	 * @inheritdoc
	 *
	 * @param Toolset_Field_Group $field_group
	 *
	 * @return bool
	 */
	public function allows_field_group( Toolset_Field_Group $field_group ) {
		if(
			$field_group instanceof Toolset_Field_Group_Post
			&& ! $field_group->has_special_purpose()
			&& ( $this->is_intermediary() || $this->is_repeating_field_group() )
		) {
			return false;
		}

		return true;
	}
}