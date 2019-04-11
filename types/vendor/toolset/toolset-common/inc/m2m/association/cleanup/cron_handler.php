<?php

/**
 * Handle the Toolset_Association_Cleanup_Cron_Event event.
 *
 * Perform the cleanup action and unschedule the event in case there are no dangling
 * intermediary posts left.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Cron_Handler {


	/** @var Toolset_Association_Cleanup_Factory */
	private $cleanup_factory;


	/** @var Toolset_Cron */
	private $cron;


	/**
	 * Toolset_Association_Cleanup_Cron_Handler constructor.
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
	 * Handle the WP-Cron event.
	 */
	public function handle_event() {
		$cleanup = $this->cleanup_factory->dangling_intermediary_posts();
		$cleanup->do_batch();

		if( ! $cleanup->has_remaining_posts() ) {
			$this->unschedule_event();
		}
	}


	private function unschedule_event() {
		$event = $this->cleanup_factory->cron_event();
		$this->cron->unschedule_event( $event );
	}
}