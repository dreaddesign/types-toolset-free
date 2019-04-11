<?php

namespace OTGS\Toolset\Common\Condition\Installer;


/**
 * Check whether the client has a valid subscription for Toolset plugins.
 *
 * This will always return false if Installer is not available.
 *
 * @package OTGS\Toolset\Common\Condition\Installer
 */
class IsToolsetSubscriptionValid implements \Toolset_Condition_Interface {


	/** @var IsAvailable */
	private $is_installer_available;


	/**
	 * IsToolsetSubscriptionValid constructor.
	 *
	 * @param IsAvailable|null $is_available_di
	 */
	public function __construct( IsAvailable $is_available_di = null ) {
		$this->is_installer_available = $is_available_di ?: new IsAvailable();
	}


	/**
	 * @return bool
	 */
	public function is_met() {
		if( ! $this->is_installer_available->is_met() ) {
			return false;
		}

		$subscription_status = apply_filters( 'otgs_installer_repository_subscription_status', null, 'toolset' );
		$is_registered = ( 'valid' === $subscription_status );

		return $is_registered;
	}
}