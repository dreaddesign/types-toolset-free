<?php

/**
 * Class Types_Setting_Boolean
 *
 * @since 2.1
 */
class Types_Setting_Boolean extends Types_Setting {
	public function get_value( $option_id ) {
		$value = parent::get_value( $option_id );
		$value =
			$value === null
			|| $value === false
			|| $value === 0
			|| $value === '0'
			|| $value === 'off'
			|| $value === 'false'
			|| $value === 'disabled'
				? false
				: true;

		return $value;
	}
}