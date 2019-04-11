<?php

/**
 * Factory for objects handling cleaning up and removing m2m-related data.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Factory {


	/**
	 * @return Toolset_Association_Cleanup_Post
	 */
	public function post() {
		return new Toolset_Association_Cleanup_Post(
			null, null, null, null, null, $this
		);
	}


	/**
	 * @return Toolset_Association_Cleanup_Association
	 */
	public function association() {
		return new Toolset_Association_Cleanup_Association();
	}


	/**
	 * @return Toolset_Association_Cleanup_Cron_Handler
	 */
	public function cron_handler() {
		return new Toolset_Association_Cleanup_Cron_Handler( $this );
	}


	/**
	 * @return Toolset_Association_Cleanup_Dangling_Intermediary_Posts
	 */
	public function dangling_intermediary_posts() {
		return new Toolset_Association_Cleanup_Dangling_Intermediary_Posts();
	}


	/**
	 * @return Toolset_Association_Cleanup_Cron_Event
	 */
	public function cron_event() {
		return new Toolset_Association_Cleanup_Cron_Event();
	}


	/**
	 * @return Toolset_Association_Cleanup_Troubleshooting_Section
	 */
	public function troubeshooting_section() {
		return new Toolset_Association_Cleanup_Troubleshooting_Section( $this );
	}


}