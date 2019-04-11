<?php

/**
 * Factory for the Types_Field_Group_Post class.
 *
 * @since 2.0
 */
class Types_Field_Group_Post_Factory extends Types_Field_Group_Factory {


	/**
	 * @return Types_Field_Group_Post_Factory
	 */
	public static function get_instance() {
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
	 * @return null|Types_Field_Group_Term Field group or null if it can't be loaded.
	 */
	public static function load( $field_group ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Types_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Types_Field_Group_Post_Factory::get_instance()->load_field_group( $field_group );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 *
	 * @return null|Types_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '', $status = 'draft' ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Types_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Types_Field_Group_Post_Factory::get_instance()->create_field_group( $name, $title, $status );
	}


	public function get_post_type() {
		return Types_Field_Group_Post::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Types_Field_Group_Post';
	}


}
