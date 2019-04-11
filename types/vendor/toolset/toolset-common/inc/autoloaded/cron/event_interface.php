<?php

/**
 * An interface that represents a WP-Cron event, to be used with Toolset_Cron.
 *
 * @since 2.5.10
 */
interface IToolset_Cron_Event {

	/**
	 * Event slug.
	 *
	 * Needs to be unique throughout Toolset.
	 *
	 * @return string
	 */
	public function get_unique_slug();


	/**
	 * A valid WP-Cron interval. Check Toolset_Cron_Event constants for values that are available
	 * at all times.
	 *
	 * @return string
	 */
	public function get_interval();


	/**
	 * Arguments that will be passed to the event hook when executed.
	 *
	 * @return array
	 */
	public function get_args();


	/**
	 * Slug of the plugin that owns this event. If the plugin uses Toolset_Cron properly,
	 * the right events will be unscheduled if the plugin is deactivated.
	 *
	 * @return string
	 */
	public function get_parent_plugin();

}