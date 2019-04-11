<?php

// the non-m2m part still needs to support PHP 5.2 for the time being.
//namespace OTGS\Toolset\Common\PublicAPI;

/**
 * Loads the public-facing API of Toolset Common.
 *
 * @since 2.6.1
 */
class Toolset_Public_API_Loader {


	public function initialize() {

		// Note: This could be used for a more generic solution.
		//$files = glob( '*.php' );
		//
		//if( ! is_array( $files ) ) {
		//	// Error when enumerating files, can't do anything about it at this point.
		//	return;
		//}
		//
		//foreach( $files as $file ) {
		//	require_once $file;
		//}

		if( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			require_once TOOLSET_COMMON_PATH . '/inc/public_api/m2m.php';
		} else {
			require_once TOOLSET_COMMON_PATH . '/inc/public_api/legacy_relationships.php';
		}
	}

}