<?php

/**
 * Wrapper for a mockable access to constants.
 *
 * Motivation: http://www.theaveragedev.com/mocking-constants-in-tests/
 *
 * Note: Use this *only* if you need it in unit tests!
 *
 * @since 2.5.1
 */
class Toolset_Constants {

	public function define( $key, $value ) {
		if ( defined( $key ) ) {
			throw new RuntimeException( "Constant $key is already defined." );
		}

		define( $key, $value );
	}

	public function defined( $key ) {
		return defined( $key );
	}

	public function constant( $key ) {
		return constant( $key );
	}

}