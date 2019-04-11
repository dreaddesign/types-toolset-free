<?php

/**
 * Types_Helper_Condition
 *
 * FIXME please document this!
 *
 * @since 2.0
 */
abstract class Types_Helper_Condition {

	public static $post_type;

	protected $condition;

	protected static function get_type_name() {
		// per post
		if( isset( $_GET['post'] ) ) {
			$get_type_name_id = (int) $_GET['post'];
			return get_post_type( $get_type_name_id );
		}

		return self::$post_type->name;
	}

	public function set_condition( $value ) {
		$this->condition = $value;
	}

	public function valid() {}

	public static function set_post_type( $posttype = false ) {
		if( ! $posttype ) {
			global $typenow;

			$posttype = isset( $typenow ) && ! empty( $typenow ) ? $typenow : false;
		}

		if( $posttype )
			self::$post_type = get_post_type_object( $posttype );
	}

	public static function get_post_type() {
		if( self::$post_type === null )
			self::set_post_type();

		return self::$post_type;
	}
}