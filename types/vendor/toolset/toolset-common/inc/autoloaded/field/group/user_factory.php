<?php

/**
 * Factory for the Toolset_Field_Group_User class.
 *
 * @since 2.0
 */
class Toolset_Field_Group_User_Factory extends Toolset_Field_Group_Factory {


	/**
	 * @return Toolset_Field_Group_User_Factory
	 */
	public static function get_instance() {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::get_instance();
	}

	protected function __construct() {
		parent::__construct();

		//add_action( 'wpcf_group_updated', array( $this, 'on_group_updated' ), 10, 2 );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @return null|Toolset_Field_Group_User Field group or null if it can't be loaded.
	 */
	public static function load( $field_group ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Toolset_Field_Group_User_Factory::get_instance()->load_field_group( $field_group );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '' ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Toolset_Field_Group_User_Factory::get_instance()->create_field_group( $name, $title );
	}


	public function get_post_type() {
		return Toolset_Field_Group_User::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Toolset_Field_Group_User';
	}


}
