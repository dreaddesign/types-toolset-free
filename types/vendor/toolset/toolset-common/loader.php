<?php
/**
*
* This file is responsible for loading the latest version of the Toolset Common lbraries.
*
* To use it in a plugin or theme you should include this file early in the
* plugin loader and then call the toolset_common_initialize function.
* The toolset_common_initialize should be passed the file path to the directory
* where this file is located and also the url to this directory.
* Note that both the path and URL will be normalized with untrailingslashit
* so they do not pack any trailing slash.
*
* -----------------------------------------------------------------------
*
* This version number should always be incremented by 1 whenever a change
* is made to the toolset-common code.
* The version number will then be used to work out which plugin has the latest
* version of the code.
*
* The version number will have a format of XXXYYY
* where XXX is the future target Toolset Common version number, built upon the stable released one stated in changelog.txt plus 1
* and YYY is incremented by 1 on each change to the Toolset Common repo
* so we allow up to 1000 changes per dev cycle.
*/

/**
 * Now that we have a unique version for all plugins
 * we define the version here
 */

// Note: Since the 2.8 branch is meant just for the support of the legacy Types 2.3.* branch,
// we're relaxing the rule and incrementing the loader number just by one.
//
// This may keep going on for some time and we don't want to run out of numbers for point releases.
$toolset_common_version = 280001;


/* ---------------------------------------------------------------------- *\
		WARNING * WARNING * WARNING
\* ---------------------------------------------------------------------- */

/*
 * Don't modify or add to this code.
 *
 * This is only responsible for making sure the latest version of common is loaded.
 */

global $toolset_common_paths;

if ( ! isset( $toolset_common_paths ) ) {
    $toolset_common_paths = array();
}

if ( ! isset( $toolset_common_paths[ $toolset_common_version ] ) ) {
    // Save the path to this version.
    $toolset_common_paths[ $toolset_common_version ]['path'] = str_replace( '\\', '/', dirname( __FILE__ ) );
}

if ( ! function_exists( 'toolset_common_plugins_loaded' ) ) {
    function toolset_common_plugins_loaded() {
        global $toolset_common_paths;

        // find the latest version
        $latest = 0;
        foreach ( $toolset_common_paths as $key => $data ) {
            if ( $key > $latest ) {
                $latest = $key;
            }
        }
        if ( $latest > 0 ) {
            require_once $toolset_common_paths[ $latest ]['path'] . '/toolset-common-loader.php';
            toolset_common_set_constants_and_start( $toolset_common_paths[ $latest ]['url'], $latest );
        }
    }
    add_action( 'plugins_loaded', 'toolset_common_plugins_loaded', -1 );
}

if ( ! function_exists( 'toolset_common_initialize' ) ) {

    function toolset_common_initialize( $path, $url ) {
        global $toolset_common_paths;

        $path = str_replace( '\\', '/', $path );

        $path = untrailingslashit( $path );
		$url = untrailingslashit( $url );

        // Save the url in the matching path
        foreach ( $toolset_common_paths as $key => $data ) {
            if ( $toolset_common_paths[ $key ]['path'] == $path ) {
                $toolset_common_paths[ $key ]['url'] = $url;
                break;
            }
        }
    }
}
