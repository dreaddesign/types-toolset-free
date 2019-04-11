<?php

/**
 * Types_Helper_Condition_Layouts_Archive_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Archive_Exists extends Types_Helper_Condition_Template {

	private static $layout_id = array();
	private static $layout_name = array();

	public function valid() {
		if( ! defined( 'WPDDL_DEVELOPMENT' ) && ! defined( 'WPDDL_PRODUCTION' ) )
			return false;

		$type = self::get_type_name();

		$layouts = get_option( WPDDL_GENERAL_OPTIONS, array() );

		// for type 'post'
		if( $type == 'post' ) {
			self::$layout_id[$type] = array_key_exists( 'layouts_home-blog-page', $layouts )
				? $layouts['layouts_home-blog-page']
				: false;

			return self::$layout_id[$type];
		}

		// all cpts
		self::$layout_id[$type] = array_key_exists( 'layouts_cpt_' . $type, $layouts )
			? self::$layout_id[$type] = $layouts['layouts_cpt_' . $type]
			: false;

		return self::$layout_id[$type];
	}

	public static function get_layout_id() {
		$type = self::get_type_name();

		if( ! isset( self::$layout_id[$type] ) ) {
			$self = new Types_Helper_Condition_Layouts_Archive_Exists();
			$self->valid();
		}

		return self::$layout_id[$type];
	}

	public static function get_layout_name() {
		$type = self::get_type_name();

		if( !isset( self::$layout_name[$type] ) )
			self::$layout_name[$type] = get_the_title( self::get_layout_id() );

		return self::$layout_name[$type];
	}
}