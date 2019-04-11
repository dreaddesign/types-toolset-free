<?php

class Toolset_User_Editors_Manager implements  Toolset_User_Editors_Manager_Interface {

	/**
	 * The medium on which the editor should be used
	 * e.g. Views Content Template
	 * @var Toolset_User_Editors_Medium_Interface
	 */
	protected $medium;

	/**
	 * All available editors.
	 * @var Toolset_User_Editors_Editor_Interface[]
	 */
	protected $editors = array();

	/**
	 * Current active editor (chosen by user)
	 * @var Toolset_User_Editors_Editor_Interface
	 */
	protected $active_editor;

	/**
	 * Toolset_User_Editors_Provider constructor.
	 *
	 * @param Toolset_User_Editors_Medium_Interface $medium
	 */
	public function __construct( Toolset_User_Editors_Medium_Interface $medium ) {
		$this->medium = $medium;
		$this->medium->add_manager( $this );
	}

	/**
	 * @param Toolset_User_Editors_Editor_Interface $editor
	 *
	 * @return bool
	 */
	public function add_editor( Toolset_User_Editors_Editor_Interface $editor ) {
		if( ! $editor->required_plugin_active() ) {
			return false;
		}

		$this->editors[$editor->get_id()] = $editor;
		return true;
	}

	/**
	 * Return all editors
	 * @return Toolset_User_Editors_Editor_Interface[]
	 */
	public function get_editors() {
		return $this->editors;
	}

	/**
	 * Return current active editor
	 *
	 * @return false|Toolset_User_Editors_Editor_Interface
	 */
	public function get_active_editor() {
		if( $this->active_editor === null ) {
			$this->active_editor = $this->fetch_active_editor();
		}

		return $this->active_editor;
	}

	public function get_medium() {
		return $this->medium;
	}

	/**
	 * @return bool
	 */
	protected function fetch_active_editor() {

		$user_editor_choice = $this->medium->user_editor_choice();
		// check every screen of medium
		foreach( $this->medium->get_screens() as $id => $screen ) {

			// if screen is active
			if( $id_medium = $screen->is_active() ) {
				$screen->add_manager( $this );

				// check editors
				foreach( $this->get_editors() as $editor ) {

					// skip if we have a user editor choice and current editor not matching selection
					if( $user_editor_choice
					    && array_key_exists( $user_editor_choice, $this->editors )
					    && $user_editor_choice !=  $editor->get_id()
					)
						continue;

					// check editor screens
					if( $editor_screen = $editor->get_screen_by_id( $id ) ) {
						$this->medium->set_id( $id_medium );
						if( $editor_screen->is_active() ) {
							$screen->equivalent_editor_screen_is_active();
							
							return $editor;
						} else if( $screen->drop_if_not_active() ) {
							$this->medium->remove_screen( $id );
						}
					}
				}
			} else if( $screen->drop_if_not_active() ) {
				$this->medium->remove_screen( $id );
			}
		}

		// if we have no editor active here it still can be a frontend
		if ( $this->active_editor === null ) {
			add_action( 'wp', array( $this, 'run' ), -1000 );
		}

		return false;
	}

	public function run() {
		if( $this->active_editor == false ) {
			$this->active_editor = null;
		}

		if( $editor = $this->get_active_editor() ) {
			$editor->run();
		}
	}
}