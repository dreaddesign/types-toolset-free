<?php

/**
 * Factory for post field definitions.
 *
 * @since 2.0
 */
class Toolset_Field_Definition_Factory_Post extends Toolset_Field_Definition_Factory {

	
	/** Name of the option used to store field definitions. */
	const FIELD_DEFINITIONS_OPTION = 'wpcf-fields';


	protected function get_option_name() {
		return self::FIELD_DEFINITIONS_OPTION;
	}


	protected function get_class_name() {
		return 'Toolset_Field_Definition_Post';
	}

	
	/**
	 * @return string[] All existing meta keys within the domain (= post meta).
	 */
	protected function get_existing_meta_keys() {
		global $wpdb;

		$meta_keys = $wpdb->get_col(
			"SELECT meta_key FROM {$wpdb->postmeta} GROUP BY meta_key HAVING meta_key NOT LIKE '\_%' ORDER BY meta_key"
		);

		return $meta_keys;
	}


	/**
	 * @inheritdoc
	 * @return Toolset_Field_Group_Post_Factory
	 * @since 2.0
	 */
	public function get_group_factory() {
		return Toolset_Field_Group_Post_Factory::get_instance();
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_domain() {
		return Toolset_Field_Utils::DOMAIN_POSTS;
	}

}