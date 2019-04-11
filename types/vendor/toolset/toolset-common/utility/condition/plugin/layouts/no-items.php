<?php

/**
 * Toolset_Condition_Plugin_Layouts_No_Items
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Layouts_No_Items implements Toolset_Condition_Interface {

	private static $is_met_result;

	public function is_met() {
		if( self::$is_met_result !== null  ) {
			// we have a cached result
			return self::$is_met_result;
		}

		$existing_layouts = get_posts( array( 'post_type' => 'dd_layouts', 'numberposts' => 1 ) );

		if( ! empty( $existing_layouts ) ) {
			self::$is_met_result = false;
			return false;
		}

		self::$is_met_result = true;
		return true;
	}
}