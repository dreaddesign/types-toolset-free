<?php

/**
 * Class Toolset_User_Editors_Medium_Abstract
 */
abstract class Toolset_User_Editors_Medium_Abstract
	implements Toolset_User_Editors_Medium_Interface {

	/**
	 * ID of the post the editor is related to
	 * e.g. Content Template ID
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Slug of Medium
	 * e.g. for Content Template it is 'view-template'
	 * @var string
	 */
	protected $slug;

	/**
	 * All possible screens.
	 * @var Toolset_User_Editors_Medium_Screen_Interface[]
	 */
	protected $screens;

	/**
	 * our defined slug of editor
	 * e.g. for Beaver Builder we use 'beaver'
	 * 
	 * @var string
	 */
	protected $user_editor_choice;


	/**
	 * @var Toolset_User_Editors_Manager_Interface
	 */
	protected $manager;


	protected $option_name_editor_choice;

	/**
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_option_name_editor_choice() {
		return $this->option_name_editor_choice;
	}

	/**
	 * @param $id
	 * @param Toolset_User_Editors_Medium_Screen_Interface $screen
	 */
	public function add_screen( $id, Toolset_User_Editors_Medium_Screen_Interface $screen ) {
		$this->screens[$id] = $screen;
	}

	/**
	 * @param $id
	 */
	public function remove_screen( $id ) {
		if( array_key_exists( $id, $this->screens ) ) {
			unset( $this->screens[$id] );
		}
	}

	/**
	 * @return Toolset_User_Editors_Medium_Screen_Interface[]
	 */
	public function get_screens() {
		return $this->screens;
	}

	public function add_manager( Toolset_User_Editors_Manager_Interface $manager ) {
		$this->manager = $manager;
	}
	
	public function get_manager() {
		return $this->manager;
	}

}