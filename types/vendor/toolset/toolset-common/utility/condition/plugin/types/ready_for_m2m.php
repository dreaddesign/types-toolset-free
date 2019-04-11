<?php

/**
 * Check that there's a Types plugin active that will work with the m2m API.
 *
 * @since m2m
 */
class Toolset_Condition_Plugin_Types_Ready_For_M2M extends Toolset_Condition_Plugin_Types_Active {

	public function is_met() {
		return ( parent::is_met() && true === apply_filters( 'toolset_is_m2m_ready', false ) );
	}

}