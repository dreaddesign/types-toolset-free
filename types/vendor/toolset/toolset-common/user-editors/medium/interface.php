<?php

interface Toolset_User_Editors_Medium_Interface {

	/**
	 * Add a screen with an id. The id should be the same as used for the equivalent editor screen id.
	 * e.g. 'backend' for Medium_Screen_Backend && also 'backend' for Editor_Screen_Backend
	 *
	 * @param $id
	 * @param Toolset_User_Editors_Medium_Screen_Interface $screen
	 */
	public function add_screen( $id, Toolset_User_Editors_Medium_Screen_Interface $screen );

	/**
	 * Return all registered screens
	 *
	 * @return Toolset_User_Editors_Medium_Screen_Interface[]
	 */
	public function get_screens();

	/**
	 * If a screen is not active it should get removed, because sometimes
	 * a needs another round of check.
	 * e.g. ct frontend display needs to run on 'wp' hook, because global $post must be set
	 *
	 * @param $id
	 */
	public function remove_screen( $id );

	/**
	 * This function is used by screen objects.
	 * e.g. Medium_Screen_Content_Template_Backend set it if $_REQUEST['ct_id'] is available
	 * @param $id
	 */
	public function set_id( $id );

	/**
	 * Id of the current medium element
	 * e.g. Id of a content template
	 *
	 * @return int
	 */
	public function get_id();

	/**
	 * Slug of medium type
	 * e.g. Content template has the slug 'view-template'
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * The id of the editor the user has chosen
	 * e.g. for Beaver Builder it would return 'beaver'
	 *
	 * @return string id of the editor
	 */
	public function user_editor_choice();

	/**
	 * List of allowed templates for using a Frontend-Editor
	 * e.g. Content Templates provides the templates of the 'Usage' assignments
	 * @return mixed
	 */
	public function get_frontend_templates();

	/**
	 * Used by the editor, to give the medium the editor backend output
	 * The medium than decides where the output is placed
	 * e.g. Beaver generates "Select template and start..." and gives
	 *      it to Content Template which decides where it is placed
	 *
	 * @param $content callable
	 */
	public function set_html_editor_backend( $content );

	/**
	 * Used by setup, to give the medium the user selection for editors
	 * e.g. Setup generates editor selection 'Default | WPBakery Page Builder (former Visual Composer) | Beaver'
	 *      and gives it to Content Template which decides where to place
	 *
	 * @param $selection callable
	 */
	// public function setHtmlEditorSelection( $selection );

	/**
	 * Some editors needs a page reload after changes are done to the medium
	 * with this function the medium will do a reload, even if by default it's stored via ajax
	 * e.g. this function is called by Beaver when used on Content Templates
	 */
	public function page_reload_after_backend_save();


	/**
	 * This manager class uses this function to make itself available for the medium
	 * @param Toolset_User_Editors_Manager_Interface $manager
	 */
	public function add_manager( Toolset_User_Editors_Manager_Interface $manager );


	/**
	 * Returns the stored editor id for the current medium
	 * e.g. user selected Beaver on CT, this will return 'beaver'
	 * @return string
	 */
	public function get_option_name_editor_choice();
}