<?php

/**
 * This controller extends all post edit pages
 *
 * @since 2.0
 */
final class Types_Page_Extension_Edit_Post_Fields {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if( ! isset( $_GET['group_id'] ) )
			return;
		
		$group_id = (int) $_GET['group_id'];

		$post_types = get_post_meta( $group_id, '_wp_types_group_post_types', 'string' );
		$post_types = explode( ',', $post_types );
		$post_types = array_values( array_filter( $post_types ) );

		if( count( $post_types ) != 1 || $post_types[0] == 'all' )
			return;

		Types_Helper_Placeholder::set_post_type( $post_types[0] );
		Types_Helper_Condition::set_post_type( $post_types[0] );

		$this->prepare();
	}

	private function __clone() { }


	public function prepare() {
		// documentation urls
		Types_Helper_Url::load_documentation_urls();

		// set analytics medium
		Types_Helper_Url::set_medium( 'field_group_editor' );

		// add informations
		$this->prepare_informations();

	}

	private function prepare_informations() {
		$setting = new Types_Setting_Preset_Information_Table();

		if( ! $setting->get_value( 'show-on-field-group' ) )
			return false;

		$information = new Types_Information_Controller;
		$information->prepare();
	}
}