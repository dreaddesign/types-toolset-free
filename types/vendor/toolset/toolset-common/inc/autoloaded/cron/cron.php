<?php

/**
 * Helper class for interaction with WP-Cron.
 *
 * Allows scheduling and unscheduling events easier with by allowing events to be defined
 * as objects implementing the IToolset_Cront_Event interface. Additionally, it tracks
 * scheduled events by plugin they come from and allows then batch unscheduling when given plugin
 * is being deactivated.
 *
 * If a plugin is using WP-Cron, it should also include a deactivation hook that will handle this
 * unscheduling.
 *
 * For m2m WP-Cron events originating from Toolset Common, the "types" plugin slug should be used,
 * since without Types, there is no m2m functionality to speak of.
 *
 * Note that events of IToolset_Cront_Event are supposed to have a slug. This slug must
 * be unique throughout Toolset. The event hook (action which will be called by WP-Cron) is built
 * from the HOOK_PREFIX and this unique slug (see get_hook_name()).
 *
 * If two events have different arguments, they are *not* considered equal by WP-Cron.
 * Be sure to thoroughly test such a scenario with this API.
 *
 * @link https://developer.wordpress.org/plugins/cron/
 * @since 2.5.10
 */
class Toolset_Cron {


	/** Prefix of each event hook. Must not be changed, otherwise existing events will break. */
	const HOOK_PREFIX = 'toolset_cron_';


	/** Option where the event information is stored (slug + parent plugin) */
	const SCHEDULED_EVENTS_OPTION = 'toolset_cron_events';


	/** @var Toolset_Cron */
	private static $instance;


	/**
	 * @return Toolset_Cron
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Schedule a new event if it's not scheduled already.
	 *
	 * @param IToolset_Cron_Event $event
	 *
	 * @return Toolset_Result Will succeed if an event is scheduled (now or before).
	 */
	public function schedule_event( IToolset_Cron_Event $event ) {
		if( $this->is_scheduled( $event ) ) {
			return new Toolset_Result( true );
		}

		$result = wp_schedule_event(
			time(), $event->get_interval(), $this->get_hook_name( $event ), $event->get_args()
		);

		$is_success = ( false !== $result );

		if( $is_success ) {
			$this->save_event( $event );
		}

		return new Toolset_Result( $is_success );
	}


	/**
	 * True if an event is already scheduled.
	 *
	 * @param IToolset_Cron_Event $event
	 *
	 * @return bool
	 */
	public function is_scheduled( IToolset_Cron_Event $event ) {
		$timestamp = wp_next_scheduled( $this->get_hook_name( $event ), $event->get_args() );
		return ( false !== $timestamp );
	}


	/**
	 * Get a full name of the hook that will be called when WP-Cron executes the event.
	 *
	 * @param IToolset_Cron_Event $event
	 *
	 * @return string
	 */
	public function get_hook_name( IToolset_Cron_Event $event ) {
		return $this->get_hook_from_slug( $event->get_unique_slug() );
	}


	/**
	 * @param string $event_slug
	 *
	 * @return string
	 */
	private function get_hook_from_slug( $event_slug ) {
		return self::HOOK_PREFIX . $event_slug;
	}


	/**
	 * Save the newly scheduled event into the option, so that we know what to deactivate
	 * when a Toolset plugin is being deactivated.
	 *
	 * @param IToolset_Cron_Event $event
	 */
	private function save_event( IToolset_Cron_Event $event ) {
		$events = toolset_ensarr( get_option( self::SCHEDULED_EVENTS_OPTION ) );
		$events[ $event->get_unique_slug() ] = array(
			'slug' => $event->get_unique_slug(),
			'plugin' => $event->get_parent_plugin()
		);
		update_option( self::SCHEDULED_EVENTS_OPTION, $events, false );
	}


	/**
	 * Remove an event from the stored option when it was unscheduled.
	 *
	 * @param string $event_unique_slug
	 */
	private function remove_event( $event_unique_slug ) {
		$events = toolset_ensarr( get_option( self::SCHEDULED_EVENTS_OPTION ) );
		unset( $events[ $event_unique_slug ] );
		update_option( self::SCHEDULED_EVENTS_OPTION, $events, false );
	}


	/**
	 * Unschedule an event.
	 *
	 * @param IToolset_Cron_Event $event
	 *
	 * @return Toolset_Result Will succeed if the event was unscheduled or if it there was
	 *     nothing to unschedule.
	 */
	public function unschedule_event( IToolset_Cron_Event $event ) {
		if( ! $this->is_scheduled( $event ) ) {
			return new Toolset_Result( true );
		}

		$timestamp = wp_next_scheduled( $this->get_hook_name( $event ), $event->get_args() );
		$result = wp_unschedule_event( $timestamp, $this->get_hook_name( $event ) );

		$was_unscheduled = ( false !== $result );

		$this->remove_event( $event->get_unique_slug() );

		return new Toolset_Result( $was_unscheduled );
	}


	/**
	 * This should be called when a plugin that uses Toolset_Cron is being deactivated.
	 *
	 * @param string $plugin_slug
	 */
	public function on_plugin_deactivation( $plugin_slug ) {
		$events = toolset_ensarr( get_option( self::SCHEDULED_EVENTS_OPTION ) );
		foreach( $events as $event_unique_slug => $event ) {
			$event_parent_plugin = toolset_getarr( $event, 'plugin' );
			if( $event_parent_plugin === $plugin_slug ) {
				wp_unschedule_hook( $this->get_hook_from_slug( $event_unique_slug ) );
				$this->remove_event( $event_unique_slug );
			}
		}
	}

}