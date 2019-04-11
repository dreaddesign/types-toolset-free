<?php

class Types_Helper_Create_View {

	/**
	 * Creates a View for a given post type
	 *
	 * @param $type
	 * @param bool|string $name Name for the View
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {
		// check if Views is active
		if( ! class_exists( 'WPV_View' )
		    || ! method_exists( 'WPV_View', 'create' ) )
			return false;

		// create name if not set
		if( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'View for %s', 'wpcf' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		$args = array(
			'view_settings' => array(
				'view-query-mode' => 'normal',
				'view_purpose'    => 'all',
				'post_type'       => array( $type )
			)
		);

		$view = WPV_View::create( $name, $args );
		return $view->id;
	}

	/**
	 * Will proof if given name is already in use.
	 * If so it adds an running number until name is available
	 *
	 * @param $name
	 * @param int $id | should not manually added
	 *
	 * @return string
	 * @since 2.0
	 */
	private function validate_name( $name, $id = 1 ) {
		$name_exists = get_page_by_title( html_entity_decode( $name ), OBJECT, 'view' );
		if( $name_exists !== null ) {
			$name = $id > 1 ? rtrim( rtrim( $name, $id - 1 ) ) : $name;
			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
