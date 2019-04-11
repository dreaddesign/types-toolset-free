<?php

/**
 * Types_Helper_Condition_Views_Archive_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Views_Archive_Exists extends Types_Helper_Condition_Views_Views_Exist {

	private static $template_id = array();
	private static $template_name = array();

	public function valid() {
		// if views not active
		if( ! defined( 'WPV_VERSION' )
		    || !function_exists( 'wpv_has_wordpress_archive') )
			return false;

		// get current type name
		$type = self::get_type_name();

		// check stored validation
		if( isset( self::$template_id[$type] ) && self::$template_id[$type] !== null && self::$template_id[$type] !== false )
			return true;

		$archive = $type == 'post'
			? wpv_has_wordpress_archive()
			: wpv_has_wordpress_archive( 'post', $type );

		self::$template_id[$type] = $archive && $archive !== 0
			? $archive
			: false;

		return self::$template_id[$type];
	}

	public static function get_template_id() {
		$type = self::get_type_name();

		if( ! isset( self::$template_id[$type] ) ) {
			$self = new Types_Helper_Condition_Views_Archive_Exists();
			$self->valid();
		}

		return self::$template_id[$type];
	}

	public static function get_template_name() {
		$type = self::get_type_name();

		if( ! isset( self::$template_name[$type] ) )
			self::$template_name[$type] = get_the_title( self::get_template_id() );

		return self::$template_name[$type];
	}

}