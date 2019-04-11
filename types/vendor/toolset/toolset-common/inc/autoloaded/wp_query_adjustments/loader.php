<?php

/**
 * Apply WP_Query adjustments.
 *
 * Depending on the status of the m2m functionality, a proper adjustment class will be instantiated
 * to allow for querying by post relationship in a sustainable way.
 *
 * @since 2.6.1
 */
class Toolset_Wp_Query_Adjustments_Loader {


	public function initialize() {

		if( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$adjustments = new Toolset_Wp_Query_Adjustments_M2m();
		} else {
			$adjustments = new Toolset_Wp_Query_Adjustments_Legacy_Relationships();
		}

		$adjustments->initialize();
	}

}