<?php

/**
 * Toolset_Condition_Theme_Avada_Not_Active_Or_Greater_Equal_5_0
 *
 * Special rule for Layouts Avada Integration
 *
 * @since 2.3.1
 */
class Toolset_Condition_Theme_Avada_Not_Active_Or_Greater_Equal_5_0 implements Toolset_Condition_Interface {

	private static $is_met_result;

	public function is_met() {
		if ( self::$is_met_result !== null ) {
			// we have a cached result
			return self::$is_met_result;
		}

		if ( ! class_exists( 'Avada' ) || ! property_exists( 'Avada', 'version' ) ) {
			// Avada not active
			self::$is_met_result = true;
			return true;
		}

		if ( version_compare( Avada::$version, '5.0.0' ) >= 0 ) {
			// Avada active and version >= 5.0
			self::$is_met_result = true;
			return true;
		}

		// Avada is active but lower than 5.0
		self::$is_met_result = false;
		return false;
	}

}