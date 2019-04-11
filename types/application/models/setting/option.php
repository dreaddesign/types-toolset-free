<?php

/**
 * Class Types_Setting_Option
 *
 * @since 2.1
 */
class Types_Setting_Option implements Types_Setting_Option_Interface {

	protected $id;
	protected $description;
	protected $value;
	protected $stored_value;
	protected $default = false;

	public function __construct( $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function set_default( $default ) {
		$this->default = $default;
	}

	public function get_value() {
		if( $this->value === null )
			$this->value = 1;

		return $this->value;
	}

	public function get_stored_value( Types_Setting_Interface $setting ) {
		if( $this->stored_value === null ) {
			if( class_exists( 'Toolset_Settings' )
			    && method_exists( 'Toolset_Settings', 'get_instance' )
			) {
				$toolset_settings = Toolset_Settings::get_instance();
				$full_setting = $toolset_settings->get( $setting->get_id() );
			} else {
				$full_setting = get_option( $setting->get_id() );
			}

			$value = isset( $full_setting[$this->get_id()] )
				? $full_setting[$this->get_id()]
				: $this->default;

			$this->stored_value = $value;
		}

		return $this->stored_value;
	}

	public function set_description( $description ) {
		$this->description = $description;
	}

	public function get_description() {
		return $this->description;
	}
}