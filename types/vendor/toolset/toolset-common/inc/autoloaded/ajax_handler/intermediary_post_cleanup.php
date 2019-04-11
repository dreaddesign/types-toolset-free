<?php

/**
 * Handles an AJAX call coming from the Troubleshooting page that is supposed to clean all the dangling intermediary
 * posts.
 *
 * Continues until there are no posts left to delete.
 *
 * See Toolset_Association_Cleanup_Dangling_Intermediary_Posts for details.
 *
 * @since 2.5.10
 */
class Toolset_Ajax_Handler_Intermediary_Post_Cleanup extends Toolset_Ajax_Handler_Abstract {


	/** @var Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;


	/** @var Toolset_Cron */
	private $cron;


	/**
	 * Toolset_Ajax_Handler_Intermediary_Post_Cleanup constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Toolset_Association_Cleanup_Factory|null $cleanup_factory_di
	 * @param Toolset_Cron|null $cron_di
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		Toolset_Association_Cleanup_Factory $cleanup_factory_di = null,
		Toolset_Cron $cron_di = null
	) {
		parent::__construct( $ajax_manager );

		$this->cleanup_factory = $cleanup_factory_di ?: new Toolset_Association_Cleanup_Factory();
		$this->cron = $cron_di ?: Toolset_Cron::get_instance();
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {
		$this->ajax_begin( array( 'nonce' => Toolset_Ajax::CALLBACK_INTERMEDIARY_POST_CLEANUP ) );

		$current_step = (int) toolset_getpost( 'current_step' );
		$cleanup = $this->cleanup_factory->dangling_intermediary_posts();
		$cleanup->do_batch();

		$number_of_deleted_posts = $cleanup->get_deleted_posts();

		// This will consequently hide the admin notice about dangling intermediary posts needing to be deleted.
		if( ! $cleanup->has_remaining_posts() ) {
			$event = $this->cleanup_factory->cron_event();
			$this->cron->unschedule_event( $event );
		}

		$message = sprintf(
			(
				$cleanup->has_remaining_posts()
				? __( 'Deleted %d dangling intermediary posts...', 'wpcf' )
				: __( 'Deleted %d dangling intermediary posts. Operation completed.', 'wpcf' )
			),
			$number_of_deleted_posts
		);

		$this->ajax_finish(
			array(
				'continue' => $cleanup->has_remaining_posts(),
				'message' => $message,
				'ajax_arguments' => array(
					'current_step' => $current_step + 1
				)
			)
		);
	}

}