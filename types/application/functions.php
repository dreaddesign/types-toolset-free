<?php
/**
 * This file is meant for very generic functions that should be allways available, PHP compatibility fixes and so on.
 *
 * Do not let it grow too much and make sure to wrap each function in !function_exists() condition.
 *
 * @since 2.0
 */

if( !function_exists( 'wpcf_getpost' ) ) {

	/**
	 * Safely retrieve a key from $_POST variable.
	 *
	 * This is a wrapper for wpcf_getarr(). See that for more information.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param null|array $valid
	 *
	 * @return mixed
	 * @since 1.9
	 */
	function wpcf_getpost( $key, $default = '', $valid = null ) {
		return wpcf_getarr( $_POST, $key, $default, $valid );
	}

}


if( !function_exists( 'wpcf_getget' ) ) {

	/**
	 * Safely retrieve a key from $_GET variable.
	 *
	 * This is a wrapper for wpcf_getarr(). See that for more information.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param null|array $valid
	 *
	 * @return mixed
	 * @since 1.9
	 */
	function wpcf_getget( $key, $default = '', $valid = null ) {
		return wpcf_getarr( $_GET, $key, $default, $valid );
	}

}


if( !function_exists( 'wpcf_getarr' ) ) {

	/**
	 * Safely retrieve a key from given array (meant for $_POST, $_GET, etc).
	 *
	 * Checks if the key is set in the source array. If not, default value is returned. Optionally validates against array
	 * of allowed values and returns default value if the validation fails.
	 *
	 * @param array $source The source array.
	 * @param string|int $key The key to be retrieved from the source array.
	 * @param mixed $default Default value to be returned if key is not set or the value is invalid. Optional.
	 *     Default is empty string.
	 * @param null|array $valid If an array is provided, the value will be validated against it's elements.
	 *
	 * @return mixed The value of the given key or $default.
	 *
	 * @since 1.9
	 */
	function wpcf_getarr( &$source, $key, $default = '', $valid = null ) {
		if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
			$val = $source[ $key ];
			if ( is_array( $valid ) && ! in_array( $val, $valid ) ) {
				return $default;
			}

			return $val;
		} else {
			return $default;
		}
	}

}


if( !function_exists( 'wpcf_ensarr' ) ) {

	/**
	 * Ensure that a variable is an array.
	 *
	 * @param mixed $array The original value.
	 * @param array $default Default value to use when no array is provided. This one should definitely be an array,
	 *     otherwise the function doesn't make much sense.
	 *
	 * @return array The original array or a default value if no array is provided.
	 *
	 * @since 1.9
	 */
	function wpcf_ensarr( $array, $default = array() ) {
		return ( is_array( $array ) ? $array : $default );
	}

}


if( !function_exists( 'wpcf_wraparr' ) ) {

	/**
	 * Wrap a variable value in an array if it's not array already.
	 *
	 * @param mixed $input
	 *
	 * @return array
	 * @since 1.9.1
	 */
	function wpcf_wraparr( $input ) {
		return ( is_array( $input ) ? $input : array( $input ) );
	}

}


if( !function_exists( 'wpcf_getnest' ) ) {

	/**
	 * Get a value from nested associative array.
	 *
	 * This function will try to traverse a nested associative array by the set of keys provided.
	 *
	 * E.g. if you have $source = array( 'a' => array( 'b' => array( 'c' => 'my_value' ) ) ) and want to reach 'my_value',
	 * you need to write: $my_value = wpcf_getnest( $source, array( 'a', 'b', 'c' ) );
	 *
	 * @param mixed|array $source The source array.
	 * @param string[] $keys Keys which will be used to access the final value.
	 * @param null|mixed $default Default value to return when the keys cannot be followed.
	 *
	 * @return mixed|null Value in the nested structure defined by keys or default value.
	 *
	 * @since 1.9
	 */
	function wpcf_getnest( &$source, $keys = array(), $default = null ) {

		$current_value = $source;

		// For detecting if a value is missing in a sub-array, we'll use this temporary object.
		// We cannot just use $default on every level of the nesting, because if $default is an
		// (possibly nested) array itself, it might mess with the value retrieval in an unexpected way.
		$missing_value = new stdClass();

		while( ! empty( $keys ) ) {
			$current_key = array_shift( $keys );
			$is_last_key = empty( $keys );

			$current_value = wpcf_getarr( $current_value, $current_key, $missing_value );

			if ( $is_last_key ) {
				// Apply given default value.
				if( $missing_value === $current_value ) {
					return $default;
				} else {
					return $current_value;
				}
			} elseif ( ! is_array( $current_value ) ) {
				return $default;
			}
		}

		return $default;
	}

}