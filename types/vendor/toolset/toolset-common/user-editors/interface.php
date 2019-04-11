<?php


interface Toolset_User_Editors_Manager_Interface {
	public function get_editors();

	/**
	 * @return Toolset_User_Editors_Editor_Interface
	 */
	public function get_active_editor();
	public function run();
	public function add_editor( Toolset_User_Editors_Editor_Interface $editor );

	/**
	 * @return Toolset_User_Editors_Medium_Interface
	 */
	public function get_medium();
}