<?php

/**
 * Types_Helper_Condition_Views_Views_Exist
 *
 * @since 2.0
 */
class Types_Helper_Condition_Views_Views_Exist extends Types_Helper_Condition_Views_Active {

	public static $views_per_post_type;

	public function valid() {
		// false if views not active
		if( ! parent::valid() )
			return false;

		global $wpdb;

		$cpt = Types_Helper_Condition::get_post_type();

		if( isset( self::$views_per_post_type[$cpt->name] ) )
			return true;

		// @todo check with Juan if views has a get_views_of_post_type() function
		$views_settings = $wpdb->get_results( "SELECT meta_value, post_id FROM $wpdb->postmeta WHERE meta_key = '_wpv_settings'" );

		foreach( $views_settings as $setting ) {
			$setting->meta_value = unserialize( $setting->meta_value );
			if( ! isset( $setting->meta_value['view-query-mode'] )
			    || $setting->meta_value['view-query-mode'] != 'normal' ) {
				// no "View"
				continue;
			}

			if( isset( $setting->meta_value['post_type'] )
			    && in_array( $cpt->name, $setting->meta_value['post_type'] ) ) {

				if( get_post_status( $setting->post_id) == 'trash' )
					continue;

				$title = get_the_title( $setting->post_id );
				self::$views_per_post_type[$cpt->name][] = array(
					'id'    => $setting->post_id,
					'name'  => $title
				);
			}
		}

		if( isset( self::$views_per_post_type[$cpt->name] ) )
			return true;

		return false;
	}

	public static function get_views_of_post_type() {
		$cpt = Types_Helper_Condition::get_post_type();

		if( isset( self::$views_per_post_type[$cpt->name] ) )
			return self::$views_per_post_type[$cpt->name];

		return false;
	}
}