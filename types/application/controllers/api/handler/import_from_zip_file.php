<?php

/**
 * Handler for the types_import_from_zip_file filter API.
 *
 * todo Handle results via Toolset_Result_Set.
 *
 * @since 2.2
 */
class Types_Api_Handler_Import_From_Zip_File implements Types_Api_Handler_Interface {


	public function __construct() { }


	/**
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return mixed
	 */
	function process_call( $arguments ) {

		$path = wpcf_getarr( $arguments, 1, null );
		$args = wpcf_getarr( $arguments, 2, null );

		if( ! is_string( $path ) || ! file_exists( $path ) ) {
			return new WP_Error( 42, __( 'Invalid path to the import file.', 'wpcf' ) );
		}

		ob_start();

		$import_data = $this->get_data_from_file( $path );
		if( $import_data instanceof WP_Error ) {
			return $import_data;
		} elseif( null == $import_data ) {
			return new WP_Error( 42, __( 'The import file could not be processed, it seems to be corrupted.', 'wpcf' ) );
		}

		$result = $this->hack_around_legacy_import_routine( $import_data, $args );

		$message = ob_get_contents();
		ob_end_clean();

		return $result;
	}



	private function hack_around_legacy_import_routine( $import_data, $import_args = null ) {

		add_filter('wpcf_admin_message_store', '__return_false');

        $_POST['overwrite-settings'] = isset( $import_args['overwrite-settings'] ) ? (bool) $import_args['overwrite-settings'] : false;

		$_POST['overwrite-groups'] = (
        	isset( $import_args['overwrite-groups'] ) && 1 == $import_args['overwrite-groups']
		        ? 1
		        : 0
        );

        $_POST['overwrite-fields'] = (
        	isset( $import_args['overwrite-fields'] ) && 1 == $import_args['overwrite-fields']
		        ? 1
		        : 0
        );

        $_POST['overwrite-types'] = (
        	isset( $import_args['overwrite-types'] ) && 1 == $import_args['overwrite-types']
		        ? 1
		        : 0
        );

        $_POST['overwrite-tax'] = (
        	isset( $import_args['overwrite-tax'] ) && 1 == $import_args['overwrite-tax']
		        ? 1
                : 0
        );

        $_POST['post_relationship'] = isset( $import_args['post_relationship'] ) ? (bool) $import_args['post_relationship'] : false;
        $_POST['delete-groups'] = isset( $import_args['delete-groups'] ) ? (bool) $import_args['delete-groups'] : false;
        $_POST['delete-fields'] = isset( $import_args['delete-fields'] ) ? (bool) $import_args['delete-fields'] : false;
        $_POST['delete-types']  = isset( $import_args['delete-types'] ) ? (bool) $import_args['delete-types'] : false;
        $_POST['delete-tax'] = isset( $import_args['delete-tax'] ) ? (bool) $import_args['delete-tax'] : false;

		/**
         * This can be emtpy string '' or 'wpvdemo', but this second option has a serious bug with xml parsing/looping
         */
        $context = isset( $import_args['context'] ) ? $import_args['context'] : '';
		// Not sure if this is needed
		require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
		require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';

		// Prepare legacy arguments for Types_Data_Installer
		$legacy_args = array();
		if ( isset($import_args['overwrite']) ){
			$legacy_args['force_import_post_name'] = wpcf_getarr( $import_args, 'overwrite', array() );
		}
		if ( isset($import_args['skip']) ){
			$legacy_args['force_skip_post_name'] = wpcf_getarr( $import_args, 'skip', array() );
		}
		if ( isset($import_args['duplicate']) ){
			$legacy_args['force_duplicate_post_name'] = wpcf_getarr( $import_args, 'duplicate', array() );
		}

		$result = wpcf_admin_import_data( $import_data, false, $context, $legacy_args );

		return true;
	}


	private function get_data_from_file( $path ) {

		$info = pathinfo( $path );

		$is_zip = $info['extension'] == 'zip' ? true : false;

		$data = null;

		if ( $is_zip ) {

			$zip = zip_open( $path );

			if ( is_resource( $zip ) ) {
				while( ( $zip_entry = zip_read( $zip ) ) !== false ) {
					if ( zip_entry_name( $zip_entry ) == 'settings.xml' ) {
						$data = @zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
					}
				}
			} else {
				return new WP_Error( 42, __( 'Unable to open zip file', 'wpcf' ) );
			}
		} else {
			// Not a zip file, we'll use it directly
			$data = @file_get_contents( $path );
		}

		return $data;
	}


}