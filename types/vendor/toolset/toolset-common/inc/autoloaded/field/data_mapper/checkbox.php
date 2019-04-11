<?php

/**
 * Data mapper for a checkbox field.
 *
 * @since 1.9
 */
class Toolset_Field_Data_Mapper_Checkbox extends Toolset_Field_Data_Mapper_Abstract {


	/**
	 * If this is a checkbox field that is not set, we will set the value manually to false.
	 *
	 * @param mixed $post_value
	 * @param array $form_data
	 *
	 * @return bool|mixed
	 */
	public function post_to_intermediate( $post_value, $form_data ) {
		if( ! array_key_exists( $this->field_definition->get_slug(), $form_data ) ) {
			// save 0 when option "When unchecked, save 0 to the database" is selected
			$value = $this->field_definition->get_should_save_empty_value() ? 0 : false;
		} else {
			$value = $post_value;
		}
		return $value;
	}
}