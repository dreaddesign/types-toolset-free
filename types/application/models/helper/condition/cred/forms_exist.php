<?php

/**
 * Types_Helper_Condition_Cred_Forms_Exist
 *
 * @since 2.0
 */
class Types_Helper_Condition_Cred_Forms_Exist extends Types_Helper_Condition_Cred_Active {

	public static $forms_per_post_type;

	public function valid() {
		// false if cred not active
		if( ! parent::valid() )
			return false;

		global $wpdb;

		$cpt = Types_Helper_Condition::get_post_type();

		if( isset( self::$forms_per_post_type[$cpt->name] ) )
			return true;

		// @todo check with Francesco if CRED has a get_forms_of_post_type() function
		$forms_settings = $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta WHERE meta_key = '_cred_form_settings'" );

		foreach( $forms_settings as $setting ) {
			$post_type = false;
			$setting->meta_value = unserialize( $setting->meta_value );

			// post type
			if( isset( $setting->meta_value->post['post_type'] ) )
				$post_type = $setting->meta_value->post['post_type'];

			// different structure created by CredFormCreator
			// (surely old style, but compatible with new and restructured after first form save)
			if( ! $post_type && is_array( $setting->meta_value ) && isset( $setting->meta_value['post_type'] ) )
				$post_type = $setting->meta_value['post_type'];

			// another structure...
			if( ! $post_type && is_object( $setting->meta_value ) && isset( $setting->meta_value->post_type ) )
				$post_type = $setting->meta_value->post_type;

			if( $post_type && $cpt->name == $post_type ) {
				$title = get_the_title( $setting->post_id );

				self::$forms_per_post_type[$cpt->name][] = array(
					'id'    => $setting->post_id,
					'name'  => $title
				);
			}
		}

		if( isset( self::$forms_per_post_type[$cpt->name] ) )
			return true;

		return false;
	}

	public static function get_forms_of_post_type() {
		$cpt = Types_Helper_Condition::get_post_type();

		if( isset( self::$forms_per_post_type[$cpt->name] ) )
			return self::$forms_per_post_type[$cpt->name];

		return false;
	}
}