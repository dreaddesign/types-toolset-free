<?php

/**
 * Main controller class for object relationships in Toolset.
 * 
 * initialize_core() needs to be called during init on every request so it can handle relevant core actions.
 * Before using any relationship functionality, initialize_full() must be called.
 *
 * Always use this as a singleton in the production code.
 *
 * @since m2m
 */
class Toolset_Relationship_Controller {


	/** @var Toolset_Post_Type_Query_Factory|null */
	private $_post_type_query_factory;


	/** @var Toolset_Relationship_Database_Operations|null */
	private $_database_operations;


	/** @var null|Toolset_Association_Cleanup_Factory */
	private $_cleanup_factory;


	/** @var Toolset_Relationship_Controller|null */
	private static $instance;


	/**
	 * @return Toolset_Relationship_Controller
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Toolset_Relationship_Controller constructor.
	 *
	 * @param Toolset_Post_Type_Query_Factory|null $post_type_query_factory_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Association_Cleanup_Factory|null $cleanup_factory_di
	 */
	public function __construct(
		Toolset_Post_Type_Query_Factory $post_type_query_factory_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Association_Cleanup_Factory $cleanup_factory_di = null
	) {
		$this->_post_type_query_factory = $post_type_query_factory_di;
		$this->_database_operations = $database_operations_di;
		$this->_cleanup_factory = $cleanup_factory_di;
	}


	const IS_M2M_ENABLED_OPTION = 'toolset_is_m2m_enabled';
	const IS_M2M_ENABLED_YES_VALUE = 'yes';

	// This is not a typo. Initially, we had 'no', but then we changed the algorithm to determine the initial
	// m2m state, and we have force re-checking.
	const IS_M2M_ENABLED_NO_VALUE = 'noo';


	/**
	 * We need WPML to fire certain actions when it updates its icl_translations table.
	 */
	const MINIMAL_WPML_VERSION = '3.9.3';


    private $is_autoloader_initialized = false;


    /** @var null|bool Cache for is_m2m_enabled() */
    private $is_m2m_enabled_cache = null;  


	/**
	 * Returns the value of the m2m feature toggle.
	 *
	 * Default value depends on the presence of legacy post relationships on the site.
	 *
	 * The result is cached.
	 *
	 * @return bool
	 */
	public function is_m2m_enabled() {

		if( null !== $this->is_m2m_enabled_cache ) {
			return $this->is_m2m_enabled_cache;
		}

		$is_enabled = get_option( self::IS_M2M_ENABLED_OPTION, null );

		// We'll force the check again if 'no' is stored, because the algorithm for determining
		// the initial state has changed since (now a different value for a negative result is used).
		if( null === $is_enabled || 'no' === $is_enabled ) {
			$is_enabled = $this->set_initial_m2m_state();
		} else {
			$is_enabled = ( self::IS_M2M_ENABLED_YES_VALUE === $is_enabled );
		}

		/**
		 * Allows for overriding the m2m feature toggle (both ways).
		 *
		 * This filter is dangerous and should never be used in production. Also, it may disappear at any given
		 * moment. For only determining whether m2m is enabled or not, use the toolset_is_m2m_enabled filter.
		 *
		 * @since m2m
		 */
		$is_enabled = (bool) apply_filters( 'toolset_enable_m2m_manually', (bool) $is_enabled );

		$this->is_m2m_enabled_cache = $is_enabled;

		return $is_enabled;
	}


	private $is_core_initialized = false;


	/**
	 * Initialize only the very core of the controller.
	 *
	 * That means mainly hooks to various WordPress events.
	 *
	 * @since m2m
	 */
	public function initialize_core() {

		if( $this->is_core_initialized ) {
			return;
		}

		$this->add_hooks();
		$this->is_core_initialized = true;
	}


	private $is_everything_initialized = false;


	/**
	 * Full initialization that is needed before any relationships-related action takes place.
	 *
	 * @since m2m
	 */
	public function initialize_full() {

		if( $this->is_everything_initialized ) {
			return;
		}

		if( ! $this->is_m2m_enabled() ) {
			return;
		}

        $this->initialize_autoloader();
		$this->initialize_core();

		// fixme: This is for the purpose of alpha and beta versions: If there's a database problem,
		// at least make it fail on every request. Otherwise, we'll just waste a little performance
		// on checking that the tables already exist.
		// @refactoring
		$migration = new Toolset_Relationship_Migration_Controller();
		$migration->do_native_dbdelta();

        $this->is_everything_initialized = true;
	}


	public function is_fully_initialized() {
		return $this->is_everything_initialized;
	}


	/**
	 * Add hooks to relevant actions and filters.
	 *
	 * All callback functions need to do initialize_full() before anything else.
	 *
	 * @since m2m
	 */
	private function add_hooks() {

		/**
		 * toolset_is_m2m_enabled
		 *
		 * @param false $default_value
		 * @return bool Is the m2m functionality enabled? If true, all legacy post relationship functionality should be
		 *     replaced by the m2m one.
		 * @since m2m
		 */
		add_filter( 'toolset_is_m2m_enabled', array( $this, 'is_m2m_enabled' ) );

		/**
		 * toolset_do_m2m_full_init
		 *
		 * Shortcut action to easily fully initialize the m2m API.
		 *
		 * @since m2m
		 */
		add_action( 'toolset_do_m2m_full_init', array( $this, 'initialize_full' ) );


		// If the m2m feature is not enabled, nothing else should happen now.
		if( ! $this->is_m2m_enabled() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'on_admin_init' ) );

		add_filter( 'before_delete_post', array( $this, 'on_before_delete_post' ) );

		/**
		 * toolset_relationship_query
		 *
		 * Query Toolset relationships without dependencies and the need for initializing the relationship controller
		 * manually.
		 *
		 * For possible query argument values, refer to the Toolset_Relationship_Query description.
		 *
		 * @since m2m
		 */
		add_filter( 'toolset_relationship_query', array( $this, 'on_toolset_relationship_query' ), 10, 2 );

		/**
		 * On change of cpt slug.
		 *
		 * @since 2.5.6
		 */
		add_action( 'wpcf_post_type_renamed', array( $this, 'on_types_cpt_rename_slug' ), 10, 2 );

		/**
		 * toolset_report_m2m_integrity_issue
		 *
		 * Allow for reporting that there is some sort of data corruption in the database.
		 *
		 * @param IToolset_Relationship_Database_Issue $issue
		 * @since 2.5.6
		 */
		add_action( 'toolset_report_m2m_integrity_issue', array( $this, 'report_integrity_issue' ) );


		/**
		 * toolset_cron_cleanup_dangling_intermediary_posts
		 *
		 * A WP-Cron event hook defined as Toolset_Association_Cleanup_Cron_Event.
		 *
		 * @since 2.5.10
		 */
		add_action( 'toolset_cron_cleanup_dangling_intermediary_posts', array( $this, 'cleanup_dangling_intermediary_posts' ) );
	}


	/**
	 * Hooked into the toolset_relationship_query filter.
	 *
	 * @param $ignored
	 * @param $query_args
	 *
	 * @return int[]|Toolset_Association[]|Toolset_Element[]
	 */
	public function on_toolset_relationship_query( /** @noinspection PhpUnusedParameterInspection */ $ignored, $query_args ) {
		$this->initialize_full();
		$query = new Toolset_Association_Query( $query_args );
		return $query->get_results();
	}


	/**
	 * Hooked into the wpcf_post_type_renamed action.
	 * To update the slug in the relationship definition when the cpt slug is changed on the cpt edit page.
	 *
	 * @param $new_slug
	 * @param $old_slug
	 * @since 2.5.6
	 */
	public function on_types_cpt_rename_slug( $new_slug, $old_slug ) {
		if( $new_slug === $old_slug ) {
			// no change
			return;
		}

		$this->initialize_full();

		$result = $this->get_database_operations()->update_type_on_type_sets( $new_slug, $old_slug );

		if( $result->is_error() ) {
			error_log( $result->get_message() );
		}
	}


	/**
	 * Register all Toolset_Relationship_* classes in the Toolset autoloader.
	 *
	 * @since m2m
	 */
	private function initialize_autoloader() {

	    if( $this->is_autoloader_initialized ) {
	        return;
        }

		$autoloader = Toolset_Common_Autoloader::get_instance();

		$autoload_classmap_file = TOOLSET_COMMON_PATH . '/inc/m2m/autoload_classmap.php';

		if( ! is_file( $autoload_classmap_file ) ) {
			// abort if file does not exist
			return;
		}

		$classmap = include( $autoload_classmap_file );

		$autoloader->register_classmap( $classmap );

        $this->is_autoloader_initialized = true;
	}


	/**
	 * Handle events on post deletion (triggered by wp_delete_post()).
	 *
	 * Basically, that means checking if there are any associations with this post and delete them.
	 * Note that that will also trigger deleting the intermediary post and possibly some owned elements.
	 *
	 * @param int $post_id
	 * @since m2m
	 */
	public function on_before_delete_post( $post_id ) {

		$this->initialize_full();

		try {
			$cleanup = $this->get_cleanup_factory()->post();
			$cleanup->cleanup( $post_id );
		} catch( Exception $e ) {
			// Silently do nothing and avoid disrupting the current process, whatever it is.
			// In the worst case, any potential dangling db stuff can be sorted out
			// later on the Troubleshooting page.
		}
	}


	/**
	 * Determine whether m2m should be enabled by default.
	 *
	 * We do that only if there are no legacy post relationships defined. Otherwise, the user needs to
	 * manually trigger the migration.
	 *
	 * When activating m2m on a fresh site, this will also create the relationship tables.
	 *
	 * Finally, this method updates the toggle option so we don't need to run this check on each request.
	 *
	 * @return bool
	 * @since m2m
	 */
	private function set_initial_m2m_state() {
		$has_legacy_relationships = new Toolset_Condition_Plugin_Types_Has_Legacy_Relationships();
		$is_ready_for_m2m = new Toolset_Condition_Plugin_Types_Ready_For_M2M();

		$enable_m2m = ( $is_ready_for_m2m->is_met() && ! $has_legacy_relationships->is_met() );

		// If there are no relationships but Toolset is not ready for m2m yet (too old Types), we don't
		// update the option, but keep trying until the update finally comes.
		$store_m2m_state = $is_ready_for_m2m->is_met();

		if( $enable_m2m ) {
			$this->force_autoloader_initialization();
			$migration = new Toolset_Relationship_Migration_Controller();
			$migration->do_native_dbdelta();
		}

		if( $store_m2m_state ) {
			update_option(
				self::IS_M2M_ENABLED_OPTION,
				( $enable_m2m ? self::IS_M2M_ENABLED_YES_VALUE : self::IS_M2M_ENABLED_NO_VALUE ),
				true
			);
		}

		return $enable_m2m;
	}


    /**
     * Force the autoloader classmap registration when usage of m2m API classes is necessary even
     * with m2m not enabled.
     *
     * @since m2m
     */
    public function force_autoloader_initialization() {
        $this->initialize_autoloader();
	}


	/**
	 * Handle the toolset_report_m2m_integrity_issue action by passing it over to a dedicated class.
	 *
	 * @param IToolset_Relationship_Database_Issue $issue
	 *
	 * @since 2.5.6
	 */
	public function report_integrity_issue( $issue ) {
    	$this->initialize_full();

    	if( $issue instanceof IToolset_Relationship_Database_Issue ) {
    		$issue->handle();
	    }
	}


	/**
	 * @return Toolset_Relationship_Database_Operations
	 */
	private function get_database_operations() {
		if( null === $this->_database_operations ) {
			$this->initialize_full();
			$this->_database_operations = new Toolset_Relationship_Database_Operations();
		}

		return $this->_database_operations;
	}


	/**
	 * @return Toolset_Association_Cleanup_Factory
	 */
	private function get_cleanup_factory() {
		if( null === $this->_cleanup_factory ) {
			$this->initialize_full();
			$this->_cleanup_factory = new Toolset_Association_Cleanup_Factory();
		}

		return $this->_cleanup_factory;
	}


	/**
	 * Callback for the WP-Cron event defined as Toolset_Association_Cleanup_Cron_Event.
	 *
	 * @since 2.5.10
	 */
	public function cleanup_dangling_intermediary_posts() {
		$this->initialize_full();
		$cron_handler = $this->get_cleanup_factory()->cron_handler();
		$cron_handler->handle_event();
	}


	/**
	 * This will run on admin_init:10 if m2m is enabled.
	 *
	 * @since 2.5.10
	 */
	public function on_admin_init() {
		$this->initialize_full();
		$this->get_cleanup_factory()->troubeshooting_section()->register();
	}
}