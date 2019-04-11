<?php

/**
 * Hijack xxx_postmeta calls.
 *
 * Depending on the status of the m2m functionality, a proper adjustment class will be instantiated
 * to allow for setting or getting legacy relationship postmeta-based entries.
 *
 * @since m2m
 */
class Toolset_Postmeta_Access_Loader {


	public function initialize() {

		if( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$adjustments = new Toolset_Postmeta_Access_M2m();
			$adjustments->initialize();
		}
		
	}

}