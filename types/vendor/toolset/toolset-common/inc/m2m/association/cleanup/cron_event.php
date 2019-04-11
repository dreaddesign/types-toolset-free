<?php

/**
 * A WP-Cron event definition.
 *
 * The event hook is added in Toolset_Relationship_Controller.
 *
 * This event should be scheduled when there are dangling intermediary posts that need to be removed.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Cron_Event extends Toolset_Cron_Event {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_unique_slug() {
		return 'cleanup_dangling_intermediary_posts';
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_parent_plugin() {
		return 'types';
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_interval() {
		return Toolset_Cron_Event::INTERVAL_HOURLY;
	}


}