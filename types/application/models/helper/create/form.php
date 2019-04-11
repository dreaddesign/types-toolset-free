<?php

/**
 * Types_Helper_Create_Form
 *
 * @since 2.0
 */
class Types_Helper_Create_Form {

	/**
	 * Creates a form for a given post type
	 *
	 * @param $type
	 * @param bool|string $name Name for the Form
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {

		// abort if CRED is not installed
		if( ! defined( 'CRED_CLASSES_PATH' ) )
			return false;

		// abort if FormCreator does not exists
		if( ! file_exists( CRED_CLASSES_PATH . '/CredFormCreator.php' ) )
			return false;

		// load form creator
		require_once( CRED_CLASSES_PATH . '/CredFormCreator.php' );

		// abort if cred_create_form is not available
		if( ! class_exists( 'CredFormCreator' )
		    || ! method_exists( 'CredFormCreator', 'cred_create_form' ) )
			return false;

		// create name if not given
		if( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'Form for %s', 'wpcf' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		$id = CredFormCreator::cred_create_form( $name, 'new', $type );
		return $id;
	}

	/**
	 * Will proof if given name is already in use.
	 * If so it adds an running number until name is available
	 *
	 * @param $name
	 * @param int $id
	 *
	 * @return string
	 * @since 2.0
	 */
	private function validate_name( $name, $id = 1 ) {
		$name_exists = get_page_by_title( html_entity_decode( $name ), OBJECT, CRED_FORMS_CUSTOM_POST_NAME );

		if( $name_exists !== null ) {
			$name = $id > 1 ? rtrim( rtrim( $name, $id - 1 ) ) : $name;
			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
