<?php

/**
 * Class Toolset_Shortcode_Attr_Field
 *
 * @since 2.3
 */
class Toolset_Shortcode_Attr_Field implements Toolset_Shortcode_Attr_Interface {
	/**
	 * @param array $data
	 *
	 * @return bool|mixed
	 */
	public function get( array $data ) {
		if ( isset( $data['field'] ) && ! empty( $data['field'] ) ) {
			// post field
			return $data['field'];
		}

		if( isset( $data['usermeta'] ) && ! empty( $data['usermeta'] ) ) {
			// user field
			return $data['usermeta'];
		}

		if( isset( $data['termmeta'] ) && ! empty( $data['termmeta'] ) ) {
			// term field
			return $data['termmeta'];
		}

		return false;
	}
}