<?php

/**
 * Types_Helper_Condition_Layouts_Template_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Template_Exists extends Types_Helper_Condition_Template {

	public static $layout_id = array();
	public static $layout_name = array();

	public function valid() {
		if( ! defined( 'WPDDL_DEVELOPMENT' ) && ! defined( 'WPDDL_PRODUCTION' ) )
			return false;

		$type = self::get_type_name();

		if( isset( self::$layout_id[$type] ) && self::$layout_id[$type] !== null && self::$layout_id[$type] !== false )
			return true;

		global $wpdb;

		$layouts_per_post_type = $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta WHERE meta_key = '_ddl_post_types_was_batched'" );

		foreach( $layouts_per_post_type as $setting ) {

			$setting->meta_value = unserialize( $setting->meta_value );

			if( is_array( $setting->meta_value )
			    && in_array( $type, $setting->meta_value ) ) {

				if( get_post_status( $setting->post_id) == 'trash' )
					continue;

				$title = get_the_title( $setting->post_id );
				self::$layout_id[$type] = $setting->post_id;
				self::$layout_name[$type] = $title;
				return true;
			}
		}

		self::$layout_id[$type] = false;
		self::$layout_name[$type] = false;
		return false;

	}

	public static function get_layout_id() {
		$type = self::get_type_name();

		if( ! isset( self::$layout_id[$type] ) ) {
			$self = new Types_Helper_Condition_Layouts_Template_Exists();
			$self->valid();
		}

		return self::$layout_id[$type];
	}

	public static function get_layout_name() {
		$type = self::get_type_name();

		if( ! isset( self::$layout_name[$type] ) )
			self::$layout_name[$type] = get_the_title( self::get_layout_id() );

		return self::$layout_name[$type];
	}
}