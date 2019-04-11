<?php

/**
 * Types_Helper_Condition_Views_Template_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Views_Template_Exists extends Types_Helper_Condition_Views_Views_Exist {

	private static $template_id = array();
	private static $template_name = array();

	public function valid() {
		// if views not active
		if( ! defined( 'WPV_VERSION' ) )
			return false;

		$type = self::get_type_name();

		if( isset( self::$template_id[$type] )
		    && self::$template_id[$type] !== null
		    && self::$template_id[$type] !== false )
			return true;

		$wpv_options = get_option( 'wpv_options', array() );

		if( empty( $wpv_options )
		    || ! isset( $wpv_options['views_template_for_'.$type] )
		    || ! get_post_type( $wpv_options['views_template_for_'.$type] )
		) {
			self::$template_id[$type] = false;
			self::$template_name[$type] = false;
			return false;
		}

		$title = get_the_title( $wpv_options['views_template_for_'.$type] );
		self::$template_id[$type] = $wpv_options['views_template_for_'.$type];
		self::$template_name[$type] = $title;

		return true;
	}

	public static function get_template_id() {
		$type = self::get_type_name();

		if( isset( self::$template_id[$type] ) )
			return self::$template_id[$type];

		// not set yet
		$self = new Types_Helper_Condition_Views_Template_Exists();

		if( $self->valid() )
			return self::get_template_id();
	}

	public static function get_template_name() {
		$type = self::get_type_name();

		if( ! isset( self::$template_name[$type] ) )
			self::$template_name[$type] = get_the_title( self::get_template_id() );

		return self::$template_name[$type];
	}

}