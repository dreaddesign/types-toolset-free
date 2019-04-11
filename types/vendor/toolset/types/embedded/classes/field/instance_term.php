<?php

/**
 * Term field instance.
 *
 * This class exists to ensure that term field-specific operations will be performed consistently.
 *
 * @since 1.9
 */
class WPCF_Field_Instance_Term extends WPCF_Field_Instance {

	
	/**
	 * Add a single field value to the database.
	 *
	 * The value will be passed through filters as needed and stored, based on field configuration.
	 *
	 * @param mixed $value Value in the "intermediate" format (which is not well defined yet), which MUST be validated already.
	 * @return bool True on success, false otherwise.
	 */
	public function add_value( $value ) {

		$result = $this->add_single_value( $value );

		return ( true == $result || 0 < $result );

	}


	/**
	 * Add a single field value to the database.
	 *
	 * The value will be passed through filters as needed and stored, based on field configuration.
	 *
	 * @param mixed $value Value in the "intermediate" format (which is not well defined yet), which MUST be validated already.
	 * @return bool|int New meta ID or true if the value was added only virtually (no adding is needed).
	 */
	private function add_single_value( $value ) {

		// Shortcuts
		$accessor = $this->get_accessor();
		$definition = $this->get_definition();

		// Trim strings
		if ( is_string( $value ) ) {
			$value = trim( $value );
		}

		// If the field has defined value to store to database, use that one (e.g. checkbox).
		if( $value && $definition->has_forced_value() ) {
			$value = $definition->get_forced_value();
		}

		// Apply all filters on saving
		$value_unfiltered = $value;
		$value = $this->filter_single_value_before_saving( $value_unfiltered );

		$is_value_empty = ( is_null( $value ) || $value === false || $value === '' );
		$should_save_empty_value = $definition->get_should_save_empty_value();
		$is_forced_value_empty = ( $definition->has_forced_value() && preg_match( '/^0$/', $value ) && preg_match( '/^0$/', $definition->get_forced_value() ) );

		if( ( !$is_value_empty || $should_save_empty_value) || $is_forced_value_empty ) {

			$value = $definition->get_data_mapper()->intermediate_to_database( $value );

			$meta_id = $accessor->add_raw_value( $value );

			if( $meta_id > 0 ) {
				$this->actions_after_single_value_saved( $value, $value_unfiltered, $meta_id );
			}

			return $meta_id;

		} else {
			// We're not storing anything to database.
			return true;
		}
	}


	/**
	 * Push single field value through set of filters before it is saved to database.
	 *
	 * @param mixed $original_value
	 * @return mixed
	 */
	private function filter_single_value_before_saving( $original_value ) {

		$definition = $this->get_definition();
		$field_definition_array = $definition->get_definition_array();

		// See wiki for filter description.
		$value = apply_filters( 'wpcf_fields_termmeta_value_save', $original_value, $field_definition_array['type'], $definition, $field_definition_array, null );
		$value = apply_filters( 'wpcf_fields_value_save', $value, $field_definition_array['type'], $definition->get_slug(), null );
		$value = apply_filters( 'wpcf_fields_slug_' . $definition->get_slug() . '_value_save', $value, $field_definition_array, null );
		$value = apply_filters( 'wpcf_fields_type_' . $field_definition_array['type'] . '_value_save', $value, $field_definition_array, null );

		return $value;
	}


	/**
	 * Execute actions after saving a single field value to database.
	 *
	 * @param mixed $value Value that was saved.
	 * @param mixed $value_unfiltered Value before passing it through pre-save filters.
	 * @param int $meta_id Meta ID of the value.
	 */
	private function actions_after_single_value_saved( $value, $value_unfiltered, $meta_id ) {

		$definition = $this->get_definition();
		$field_definition_array = $definition->get_definition_array();

		/**
		 * Executed after each field value is saved (that means multiple times for repetitive fields).
		 *
		 * @param mixed $value The value that was stored in the database
		 * @param array $field_definition_array Field definition array.
		 * @param mixed $ignored Sometimes it may be WPCF_Field and sometimes null. Don't rely in this at all.
		 * @param int $meta_id ID of the meta record in the database.
		 * @param mixed $value_unfiltered The (more or less) original value before it was pushed through filters
		 *     wpcf_fields_value_save & co.
		 * @since unknown
		 */
		do_action( 'wpcf_fields_save', $value, $field_definition_array, null, $meta_id, $value_unfiltered );
		do_action( 'wpcf_fields_slug_' . $definition->get_slug() . '_save', $value, $field_definition_array, null, $meta_id, $value_unfiltered );
		do_action( 'wpcf_fields_type_' . $field_definition_array['type'] . '_save', $value, $field_definition_array, null, $meta_id, $value_unfiltered );

	}


	/**
	 * Overwrite current field values with new ones.
	 *
	 * @param array $values Array of values. For non-repetitive field there must be exactly one value. Order of values
	 *     in this array will be stored as sort order.
	 * @return bool True on success, false if some error has occured.
	 */
	public function update_all_values( $values ) {

		// Quoting from original code:
		// Since Types 1.2 we completely rewrite meta. It has no impact on frontend and covers a lot of cases
		// (e.g. user change mode from single to repetitive).
		$this->delete_all_values();

		if( ! $this->get_definition()->get_is_repetitive() ) {

			// Single field here. We expect exactly one value.
			if( count( $values ) != 1 ) {
				return false;
			}

			return $this->add_value( $values[0] );

		} else {

			// Saving repetitive field is a bit more complex, also because we need to execute these actions (mainly
			// for WPML compatibility).
			do_action(
				'wpcf_termmeta_before_adding_repetitive_field_values',
				$this->get_object_id(),
				$this->get_definition()->get_definition_array(),
				$this
			);

			$is_success = true;

			$meta_ids = array();

			// Because we need to fire a special action before the last value is stored
			$total_value_count = count( $values );
			$current_value_number = 1;

			foreach( $values as $value ) {

				// They say it's needed only for Conditional case:
				// https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/160422568/comments#comment_226301926
				// $value = apply_filters( 'types_field_get_submitted_data', $value, $this );

				if ( $total_value_count == $current_value_number ) {
					// After this action we will store the last value.
					do_action(
						'wpcf_termmeta_before_adding_last_repetitive_field_value',
						$this->get_object_id(),
						$this->get_definition()->get_definition_array(),
						$this
					);
				}
				$current_value_number++;

				$meta_id = $this->add_single_value( $value );
				$is_success = $is_success && ( 0 < $meta_id );
				$meta_ids[] = $meta_id;
			}

			do_action(
				'wpcf_termmeta_after_adding_repetitive_field_values',
				$this->get_object_id(),
				$this->get_definition()->get_definition_array(),
				$this
			);

			$this->set_sort_order( $meta_ids );

			return $is_success;
		}
	}


	/**
	 * @return WPCF_Field_Accessor_Abstract An accessor to get the sort order for repetitive fields.
	 */
	protected function get_order_accessor() {
		return new WPCF_Field_Accessor_Termmeta( $this->get_object_id(), $this->get_order_meta_name(), false );
	}

}