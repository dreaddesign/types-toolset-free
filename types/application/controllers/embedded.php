<?php

/**
 * Embedded controller.
 *
 * @since 2.0
 */
final class Types_Embedded {

	private function __construct() {
		// disable pages
		add_filter( 'types_register_pages', '__return_false' );

		// disable information table
		add_filter( 'types_information_table', '__return_false' );
	}


	private function __clone() { }


	public static function initialize() {
		if( file_exists( TYPES_ABSPATH . '/embedded.lock' )
		    || ( defined( 'TYPES_EMBEDDED') && TYPES_EMBEDDED === true )
		) {
			new self();
		}
	}

}