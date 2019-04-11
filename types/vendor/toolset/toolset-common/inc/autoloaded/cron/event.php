<?php

/**
 * Basic IToolset_Cron_Event implementation.
 *
 * @since 2.5.10
 */
abstract class Toolset_Cron_Event implements IToolset_Cron_Event {


	// These are guaranteed WP-Cron intervals. Anything else is site-specific.
	const INTERVAL_HOURLY = 'hourly';
	const INTERVAL_TWICE_DAILY = 'twicedaily';
	const INTERVAL_DAILY = 'daily';


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_interval() {
		return self::INTERVAL_DAILY;
	}


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function get_args() {
		return array();
	}


}