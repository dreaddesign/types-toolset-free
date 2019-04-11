<?php

/**
 * Manage the troubleshooting section for manually deleting dangling intermediary posts.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Troubleshooting_Section {


	const TROUBLESHOOTING_SECTION_SLUG = 'cleanup_intermediary_posts';


	/**
	 * @var Toolset_Association_Cleanup_Factory
	 */
	private $cleanup_factory;


	/**
	 * @var Toolset_Cron
	 */
	private $cron;


	/**
	 * Toolset_Association_Cleanup_Troubleshooting_Section constructor.
	 *
	 * @param Toolset_Association_Cleanup_Factory $cleanup_factory
	 * @param Toolset_Cron|null $cron_di
	 */
	public function __construct(
		Toolset_Association_Cleanup_Factory $cleanup_factory,
		Toolset_Cron $cron_di = null
	) {
		$this->cleanup_factory = $cleanup_factory;
		$this->cron = $cron_di ?: Toolset_Cron::get_instance();
	}


	/**
	 * Find out whether there are posts to clean up.
	 *
	 * Instead of running the query, we just check whether the cleanup WP-Cron job is already scheduled.
	 *
	 * @return bool
	 */
	public function is_cleanup_needed() {
		$cron_event = $this->cleanup_factory->cron_event();

		// Note: This may give us false positives: There may be a WP-Cron event executed later during this request,
		// which will delete all remaining DIPs. Unfortunately, we have no way of predicting that reliably.
		//
		// This will be happening if there are exactly between ($x+1) and ($x*2) DIPs,
		// where $x = Toolset_Association_Cleanup_Post::DELETE_POSTS_PER_BATCH.
		return $this->cron->is_scheduled( $cron_event );
	}


	/**
	 * Register the troubleshooting section and a dismissable admin notice that will point towards it.
	 */
	public function register() {
		add_filter( 'toolset_get_troubleshooting_sections', array( $this, 'add_troubleshooting_section' ) );
		add_action( 'toolset_admin_notices_manager_show_notices', array( $this, 'add_notice' ) );
	}


	/**
	 * Add new troubleshooting section definition.
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function add_troubleshooting_section( $sections ) {
		$sections[ self::TROUBLESHOOTING_SECTION_SLUG ] = array(
			'title' => __( 'Delete leftover intermediary posts', 'wpcf' ),
			'description' => __( 'After deleting a large number of associations, there are some intermediary posts left. It was not possible to delete them at once. You can either delete them manually or ignore the issue, and they will be gradually removed automatically. This is only important if you use intermediary posts on the front-end directly, for example in a View.', 'wpcf'),
			'button_label' => __( 'Delete leftover posts', 'wpcf' ),
			'is_dangerous' => false,
			'action_name' => Toolset_Ajax::get_instance()->get_action_js_name( Toolset_Ajax::CALLBACK_INTERMEDIARY_POST_CLEANUP ),
			'nonce' => wp_create_nonce( Toolset_Ajax::CALLBACK_INTERMEDIARY_POST_CLEANUP ),
			'ajax_arguments' => array( 'current_step' => 1 )
		);
		return $sections;
	}


	/**
	 * Show an admin notice if there are dangling intermediary posts.
	 *
	 * @param array $notices
	 *
	 * @return array
	 * @throws Exception
	 */
	public function add_notice( $notices ) {
		if( $this->is_cleanup_needed() ) {
			$notices[] = new Toolset_Admin_Notice_Dismissible(
				self::TROUBLESHOOTING_SECTION_SLUG,
				sprintf(
					__( 'There may be some leftover intermediary posts that need to be deleted. You can do it manually on the %s or ignore this message and wait until they\'re deleted automatically.', 'wpcf' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_attr( add_query_arg( array( 'page' => Toolset_Menu::TROUBLESHOOTING_PAGE_SLUG ), admin_url( 'admin.php' ) ) ),
						__( 'Troubleshooting Page', 'wpcf' )
					)
				)
			);
		}
		return $notices;
	}

}