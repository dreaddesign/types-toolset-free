<?php

/**
 * Factory for user field definitions.
 *
 * @since 2.0
 */
final class WPCF_Field_Definition_Factory_User extends WPCF_Field_Definition_Factory {


	/** Name of the option used to store field definitions. */
	const FIELD_DEFINITIONS_OPTION = 'wpcf-usermeta';


	protected function get_option_name() {
		return self::FIELD_DEFINITIONS_OPTION;
	}


	protected function get_class_name() {
		return 'WPCF_Field_Definition_User';
	}

	
	/**
	 * @return string[] All existing meta keys within the domain (= post meta).
	 */
	protected function get_existing_meta_keys() {
		global $wpdb;

		$meta_keys = $wpdb->get_col(
			"SELECT meta_key FROM {$wpdb->usermeta} GROUP BY meta_key HAVING meta_key NOT LIKE '\_%' ORDER BY meta_key"
		);

		return $meta_keys;
	}


	/**
	 * @inheritdoc
	 * @return Types_Field_Group_Post_Factory
	 * @since 2.0
	 */
	public function get_group_factory() {
		return Types_Field_Group_User_Factory::get_instance();
	}


	/**
	 * @inheritdoc
	 * @return string
	 * @since 2.0
	 */
	public function get_domain() {
		return Types_Field_Utils::DOMAIN_USERS;
	}
}