<?php

abstract class Toolset_User_Editors_Medium_Screen_Abstract
	implements Toolset_User_Editors_Medium_Screen_Interface {

	/**
	 * @var Toolset_User_Editors_Manager_Interface
	 */
	protected $manager;

	public function is_active() {
		return false;
	}

	public function drop_if_not_active() {
		return true;
	}

	public function equivalent_editor_screen_is_active() {}

	public function add_manager( Toolset_User_Editors_Manager_Interface $manager ) {
		$this->manager = $manager;
	}
}