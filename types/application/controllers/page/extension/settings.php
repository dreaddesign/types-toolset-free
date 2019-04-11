<?php

/**
 * Class Types_Page_Extension_Settings
 *
 * @since 2.1
 */
class Types_Page_Extension_Settings {

	public function build() {
		// general tab
		add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'general' ), 10, 2 );

		// script
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_admin_scripts' ) );
	}

	/**
	 * General Tab
	 *
	 * @param $sections
	 * @param $toolset_options
	 *
	 * @return mixed
	 */
	public function general( $sections, $toolset_options ) {

		$view = new Types_Helper_Twig();

		// Information Table
		$setting = new Types_Setting_Preset_Information_Table();

		$sections[$setting->get_id()] = array(
			'slug' => $setting->get_id(),
			'title' => __( '"Front-end Display" table', 'wpcf' ),
			'content' =>  $view->render(
				'/setting/checkbox.twig',
				array(
					'description' => __( 'Show information about Template, Archive, Views and Forms on:', 'wpcf' ),
					'setting' => $setting,
				)
			)
		);

		return $sections;
	}

	/**
	 * Admin Scripts
	 */
	public function on_admin_enqueue_scripts() {
		// script
		wp_enqueue_script(
			'types-toolset-settings',
			TYPES_RELPATH . '/public/js/settings.js',
			array(),
			TYPES_VERSION,
			true
		);
	}

	public function print_admin_scripts() {
		echo '<script id="types_model_data" type="text/plain">'.base64_encode( wp_json_encode( $this->build_js_data() ) ).'</script>';
	}

	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 * @since 2.1
	 */
	private function build_js_data() {

		$types_settings_action = Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_SETTINGS_ACTION );

		return array(
			'ajaxInfo' => array(
				'fieldAction' => array(
					'name' => $types_settings_action,
					'nonce' => wp_create_nonce( $types_settings_action )
				)
			),
		);
	}
}