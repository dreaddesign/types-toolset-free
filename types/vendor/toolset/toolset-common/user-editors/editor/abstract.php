<?php
/**
 * Abstract Editor class.
 *
 * @since 2.5.0
 */


abstract class Toolset_User_Editors_Editor_Abstract
	implements Toolset_User_Editors_Editor_Interface {

	protected $id;
	protected $name;
	protected $option_name = '_toolset_user_editors_editor_default';

	protected $logo_class;
	protected $logo_image_svg;

	/**
	 * All possible screens.
	 * @var Toolset_User_Editors_Editor_Screen_Interface[]
	 */
	protected $screens;

	/**
	 * @var Toolset_User_Editors_Medium_Interface
	 */
	protected $medium;

	/**
	 * Toolset_User_Editors_Editor_Abstract constructor.
	 *
	 * @param Toolset_User_Editors_Medium_Interface $medium
	 */
	public function __construct( Toolset_User_Editors_Medium_Interface $medium ) {
		$this->medium = $medium;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_logo_class() {
		return $this->logo_class;
	}

	public function get_logo_image_svg() {
		return $this->logo_image_svg;
	}

	public function set_name( $name ) {
		return $this->name = $name;
	}

	public function get_option_name() {
		return $this->option_name;
	}

	public function required_plugin_active() {
		return false;
	}

	public function add_screen( $id, Toolset_User_Editors_Editor_Screen_Interface $screen ) {
		$screen->add_editor( $this );
		$screen->add_medium( $this->medium );
		$this->screens[$id] = $screen;
	}

	/**
	 * @param $id
	 *
	 * @return false|Toolset_User_Editors_Editor_Screen_Interface
	 */
	public function get_screen_by_id( $id ) {
		if( $this->screens === null )
			return false;

		if( array_key_exists( $id, $this->screens ) )
			return $this->screens[$id];

		return false;
	}
}

