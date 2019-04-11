<?php

/**
 * Editor class interface.
 *
 * @since 2.5.0
 */

interface Toolset_User_Editors_Editor_Interface {
	public function required_plugin_active();
	public function add_screen( $id, Toolset_User_Editors_Editor_Screen_Interface $screen );
	public function run();

	/**
	 * @return false|Toolset_User_Editors_Editor_Screen_Interface
	 */
	public function get_screen_by_id( $id );

	public function get_id();
	public function get_name();
	public function get_option_name();
	public function get_logo_class();
}

