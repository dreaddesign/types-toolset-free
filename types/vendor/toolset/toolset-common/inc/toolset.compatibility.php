<?php

/**
* Compatibility helpers for the Toolset family
*
* @sinze unknown
*/

/**
* ####################
*
* WordPress compatibility
*
* Mainly for backwards comaptibility issues
*
* ####################
*/

/**
* wp_normalize_path
*
* For WP < 3.5
*/

if ( ! function_exists( 'wp_normalize_path' ) ) {
    function wp_normalize_path( $path ) {
        $path = str_replace( '\\', '/', $path );
        $path = preg_replace( '|/+|','/', $path );
        return $path;
    }
}

/**
* ####################
*
* PHP compatibility
*
* Mainly for backwards comaptibility issues
*
* ####################
*/

/**
* array_replace_recursive
*
* For PHP < 5.3
*
* @since 1.9
*/

if ( ! function_exists( 'array_replace_recursive' ) ) {
	function wpcf_recurse( $array, $array1 ) {
        foreach ($array1 as $key => $value)
        {
            // create new key in $array, if it is empty or not an array
            if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
            {
                $array[$key] = array();
            }

            // overwrite the value in the base array
            if (is_array($value))
            {
                $value = array_replace_recursive($array[$key], $value);
            }
            $array[$key] = $value;
        }
        return $array;
    }

    function array_replace_recursive( $array, $array1 ) {
        // handle the arguments, merge one by one
        $args = func_get_args();
        $array = $args[0];
        if (!is_array($array))
        {
            return $array;
        }
        for ($i = 1; $i < count($args); $i++)
        {
            if (is_array($args[$i]))
            {
                $array = wpcf_recurse($array, $args[$i]);
            }
        }
        return $array;
    }
}