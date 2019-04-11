<?php

/**
 * Repository for post types (IToolset_Post_Type).
 *
 * Entry point for getting, querying, creating or updating post types. In production code, use it as a singleton.
 *
 * Note: The Toolset_Post_Type API is not intended for dealing with registering post types on each request. That part is
 * still being handled in legacy Types code, namely in wpcf_custom_types_init().
 *
 * Note: The mechanism for creating post types is still incomplete as it was implemented for a specific purpose.
 * If you use it, make sure you implement all the potentially missing pieces.
 *
 * @since m2m
 */
class Toolset_Post_Type_Repository {

	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self(
				Toolset_Naming_Helper::get_instance(),
				new Toolset_Condition_Plugin_Types_Active(),
				new Toolset_Post_Type_Factory()
			);
			self::$instance->initialize();
		}

		return self::$instance;
	}


	/** Name of the Types option where custom post type definitions are stored. */
	const POST_TYPES_OPTION_NAME = 'wpcf-custom-types';


	private $is_initialized = false;


	/** @var IToolset_Post_Type[] */
	private $post_types = array();


	/** @var Toolset_Naming_Helper */
	private $naming_helper;

	/** @var Toolset_Condition_Plugin_Types_Active */
	private $is_types_active;

	/** @var Toolset_Post_Type_Factory */
	private $post_type_factory;


	public function __construct(
		Toolset_Naming_Helper $naming_helper_di,
		Toolset_Condition_Plugin_Types_Active $is_types_active_di,
		Toolset_Post_Type_Factory $post_type_factory_di
	) {
		$this->naming_helper = $naming_helper_di;
		$this->is_types_active = $is_types_active_di;
		$this->post_type_factory = $post_type_factory_di;
	}


	/**
	 * Needs to be called when instantiating.
	 */
	public function initialize() {
		if ( $this->is_initialized ) {
			return;
		}

		$this->post_types = $this->load_all_post_types();

		if( ! did_action( 'init' ) ) {
			// Run immediately after CPTs have been registered by Types.
			//
			// This is necessary if the post type repository gets initialized too early
			// and does the initial loading before CPTs in Types are registered (at init:10 or earlier).
			add_action( 'init', array( $this, 'finish_loading_registered_post_types' ), 11 );
		}

		$this->is_initialized = true;
	}

	/**
	 * Reloads all post types to the repository
	 */
	public function refresh_all_post_types() {
		$this->post_types = $this->load_all_post_types();
	}

	/**
	 * Load all post types known on the site
	 *
	 * @return Toolset_Post_Type_Registered[]
	 */
	private function load_all_post_types() {
		$post_types = $this->load_registered_post_types();

		if( $this->is_types_active->is_met() ) {
			$post_types = $this->load_post_types_from_types( $post_types );
		}

		return $post_types;
	}


	/**
	 * Load registered post types again and add them where they are missing:
	 *
	 * - if the CPT is defined in Types, add it to the IToolset_Post_Type_From_Types object (which will
	 *   let the CPT object know that it's registered (== active))
	 * - if the CPT is not from Types but hasn't been stored in the repository at all, add it.
	 *
	 * @since 2.6.3
	 */
	public function finish_loading_registered_post_types() {
		$post_types = $this->load_registered_post_types();

		foreach( $post_types as $registered_post_type ) {
			// The post type was newly registered, just add it to the repository.
			if( ! array_key_exists( $registered_post_type->get_slug(), $this->post_types ) ) {
				$this->post_types[ $registered_post_type->get_slug() ] = $registered_post_type;
			}

			/** @var IToolset_Post_Type_From_Types|IToolset_Post_Type $stored_post_type */
			$stored_post_type = $this->post_types[ $registered_post_type->get_slug() ];
			if( $stored_post_type->is_from_types() ) {
				$stored_post_type->set_registered_post_type( $registered_post_type );
			}
		}
	}

	/**
	 * Load post types currently registered on the site.
	 *
	 * If a post type cannot be loaded for some reason, it will be skipped.
	 *
	 * @return Toolset_Post_Type_Registered[] Post type models with slugs as array keys.
	 */
	private function load_registered_post_types() {

		/** @var WP_Post_Type[] $wp_post_types */
		$wp_post_types = get_post_types( array(), 'objects' );

		$results = array();
		foreach ( $wp_post_types as $wp_post_type ) {
			try {
 				$results[ $wp_post_type->name ] = $this->post_type_factory->registered_post_type( $wp_post_type );
			} catch( Exception $e ) {
				continue;
			}
		}

		return $results;
	}


	/**
	 * Load post types defined in Types.
	 *
	 * @param Toolset_Post_Type_Registered[] $registered_post_types Registered post types. If a post type from Types
	 * is also registered, its Toolset_Post_Type_Registered instance will be wrapped
	 * in a Toolset_Post_Type_From_Types one.
	 *
	 * @return IToolset_Post_Type[] All post type models (registered + from Types) with slugs as array keys.
	 */
	private function load_post_types_from_types( $registered_post_types ) {

		$custom_types = toolset_ensarr( get_option( self::POST_TYPES_OPTION_NAME, array() ) );

		$results = $registered_post_types;
		foreach ( $custom_types as $slug => $definition ) {

			// Mimicking behaviour from Types.
			if ( empty( $definition ) || $this->is_custom_definition_for_builtin_post_type( $slug, $definition ) ) {
				continue;
			}

			$registered_post_type = toolset_getarr( $registered_post_types, $slug, null );
			$results[ $slug ] = $this->post_type_factory->post_type_from_types( $slug, $definition, $registered_post_type );
		}

		return $results;
	}


	/**
	 * Determine if the post type slug and definition belong to a built-in post type.
	 *
	 * @param string $slug
	 * @param array $definintion Post type definition from Types
	 *
	 * @return bool
	 */
	private function is_custom_definition_for_builtin_post_type( $slug, $definintion ) {
		if ( isset( $definintion['_builtin'] ) && $definintion['_builtin'] ) {
			return true;
		}

		$builtin_post_types = get_post_types( array( 'public' => true, '_builtin' => true ) );

		return in_array( $slug, $builtin_post_types );
	}


	/**
	 * Check whether a certain post type exists.
	 *
	 * @param string $post_type_slug
	 *
	 * @return bool
	 */
	public function has( $post_type_slug ) {
		return array_key_exists( $post_type_slug, $this->post_types );
	}


	/**
	 * Get a post type model.
	 *
	 * @param string $post_type_slug
	 *
	 * @return IToolset_Post_Type|null Post type model or null if it doesn't exist.
	 */
	public function get( $post_type_slug ) {
		if ( ! $this->has( $post_type_slug ) ) {
			return null;
		}

		return $this->post_types[ $post_type_slug ];
	}


	/**
	 * @param $post_type_slug
	 *
	 * @return IToolset_Post_Type_From_Types|null
	 */
	public function get_from_types( $post_type_slug ) {
		$post_type = $this->get( $post_type_slug );

		if( ! $post_type instanceof IToolset_Post_Type_From_Types ) {
			return null;
		}

		return $post_type;
	}


	/**
	 * Get all post types.
	 *
	 * @return IToolset_Post_Type[]
	 */
	public function get_all() {
		return $this->post_types;
	}


	/**
	 * Save a post type model that has been changed.
	 *
	 * Warning: Only post types defined in Types are supported.
	 *
	 * Warning: There are some things missing compared to the Types saving mechanism.
	 *
	 * @param IToolset_Post_Type_From_Types $post_type
	 *
	 * @return bool True on success.
	 */
	public function save( IToolset_Post_Type_From_Types $post_type ) {

		$post_type->touch();

		// These things are missing compared to Types: Needs to be fixed if this method is used to update
		// post types that are not completely hidden.

		// if ( !$definition['_builtin'] ) {
		//     wpcf_custom_types_register_translation( $post_type, $definition );
		// }

		$custom_types = get_option( self::POST_TYPES_OPTION_NAME, array() );

		$post_type_definition = $post_type->get_definition();

		// Signal Types to run flush_rewrite_rules() after registering the post type.
		$post_type_definition[ Toolset_Post_Type_From_Types::DEF_NEEDS_FLUSH_REWRITE_RULES ] = true;

		$custom_types[ $post_type->get_slug() ] = $post_type_definition;

		update_option( self::POST_TYPES_OPTION_NAME, $custom_types, true );

		if ( ! $post_type->is_builtin() ) {
			/**
			 * Legacy action coming from Types, indicating that a custom post type has been saved.
			 *
			 * @since unknown
			 */
			do_action( 'wpcf_custom_types_save', $post_type_definition );
		}

		return true;
	}


	/**
	 * Create a new post type.
	 *
	 * It doesn't persist the changes, you need to manually call save() once the post type is configured.
	 *
	 * @param string $post_type_slug Valid post type slug. Consider using Toolset_Naming_Helper::generate_unique_post_type_slug()
	 * @param string $label_name The "Name" label (plural).
	 * @param string $label_singular_name The "Singular name" label.
	 *
	 * @return IToolset_Post_Type_From_Types
	 * @throws RuntimeException
	 */
	public function create( $post_type_slug, $label_name, $label_singular_name ) {

		if( ! $this->is_types_active->is_met() ) {
			throw new RuntimeException( 'The Types plugin needs to be active in order to support custom post type creation.', 1 );
		}

		if ( $this->has( $post_type_slug ) ) {
			throw new RuntimeException( 'Post type already exists.', 2 );
		}

		// Asking again to make it unique because that goes beyond just conflicts with other post types.
		if( ! $this->naming_helper->is_post_type_slug_valid( $post_type_slug, true ) ) {
			throw new RuntimeException( 'The post type slug is not valid.', 3 );
		}

		$post_type = $this->post_type_factory->post_type_from_types( $post_type_slug, array() );

		$post_type->set_label( Toolset_Post_Type_Labels::NAME, $label_name );
		$post_type->set_label( Toolset_Post_Type_Labels::SINGULAR_NAME, $label_singular_name );
		$post_type->touch();

		$this->post_types[ $post_type_slug ] = $post_type;

		return $post_type;
	}

	/**
	 * Delete a post type
	 *
	 * @param IToolset_Post_Type_From_Types $post_type
	 */
	public function delete( IToolset_Post_Type_From_Types $post_type ) {

		$custom_types = get_option( self::POST_TYPES_OPTION_NAME, array() );
		unset( $custom_types[ $post_type->get_slug() ] );
		update_option( self::POST_TYPES_OPTION_NAME, $custom_types, true );

		unset( $this->post_types[ $post_type->get_slug() ] );
	}


	/**
	 * Change a slug of the post type.
	 *
	 * @param IToolset_Post_Type_From_Types $post_type
	 * @param $new_slug
	 *
	 * @throws RuntimeException
	 *
	 * @return IToolset_Post_Type_From_Types
	 */
	public function change_slug( IToolset_Post_Type_From_Types $post_type, $new_slug ) {

		if( ! $this->naming_helper->is_post_type_slug_valid( $new_slug, true ) ) {
			throw new RuntimeException( 'The new slug is not valid.' );
		}

		// old slug
		$old_slug = $post_type->get_slug();

		// get stored cpts
		$custom_types = get_option( self::POST_TYPES_OPTION_NAME, array() );

		if( ! isset( $custom_types[ $old_slug ] ) ) {
			throw new RuntimeException( 'The post type is not managed by Types.' );
		}

		// rename slug
		$post_type->set_slug( $new_slug );
		$post_type->touch();

		// delete post type with old slug
		unset( $custom_types[ $old_slug ] );

		// apply post type with new slug
		$custom_types[ $post_type->get_slug() ] = $post_type->get_definition();

		// persist data
		update_option( self::POST_TYPES_OPTION_NAME, $custom_types, true );

		// refresh $this->post_types
		$this->post_types = $this->load_all_post_types();

		// update cpt related posts
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . $wpdb->posts . "
				 SET post_type = '%s' 
				 WHERE post_type = '%s'",
				$new_slug,
				$old_slug
			)
		);

		// announce to Types that a post type has been renamed
		do_action( 'wpcf_post_type_renamed', $new_slug, $old_slug );

		if( $wpdb->last_error ) {
			throw new RuntimeException( 'The posts could not be updated: ' . $wpdb->last_error );
		}

		return $post_type;
	}
}
