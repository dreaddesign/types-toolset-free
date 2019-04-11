<?php

/**
 * This controller extends all post edit pages
 *
 * @since 2.0
 */
final class Types_Page_Extension_Edit_Post {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$post = wpcf_admin_get_edited_post();
		$post_type = wpcf_admin_get_edited_post_type( $post );

		// if no post or no page
		if( $post_type != 'post' && $post_type != 'page' ) {
			$post_type_option = new Types_Utils_Post_Type_Option();
			$custom_types = $post_type_option->get_post_types();

			// abort if also no custom post type of types
			if( ! array_key_exists( $post_type, $custom_types ) )
				return false;
		}

		$this->prepare();
	}

	private function __clone() { }


	public function prepare() {
		// documentation urls
		Types_Helper_Url::load_documentation_urls();

		// set analytics medium
		Types_Helper_Url::set_medium( 'post_editor' );
		
		// add information
		$this->prepare_information();

		// @todo load scripts
		if( function_exists( 'wpcf_edit_post_screen_scripts' ) )
			wpcf_edit_post_screen_scripts();
	}

	private function prepare_information() {
		$setting = new Types_Setting_Preset_Information_Table();

		if( ! $setting->get_value( 'show-on-post' ) )
			return false;

		$information = new Types_Information_Controller;
		$information->prepare();
	}
}