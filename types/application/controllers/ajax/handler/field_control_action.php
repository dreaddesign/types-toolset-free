<?php

/**
 * Handle action with field definitions on the Field Control page.
 *
 * @since 2.1
 */
final class Types_Ajax_Handler_Field_Control_Action extends Types_Ajax_Handler_Abstract {


	/**
	 * @inheritdoc
	 *
	 * todo document
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {

		$am = $this->get_am();

		$am->ajax_begin( array( 'nonce' => $am->get_action_js_name( Types_Ajax::CALLBACK_FIELD_CONTROL_ACTION ) ) );

		// Read and validate input
		$field_action = sanitize_text_field( wpcf_getpost( 'field_action' ) );
		$fields = wpcf_getpost( 'fields' ); // array of values, will be sanitized when processed

		$current_domain = wpcf_getpost( 'domain', null, Types_Field_Utils::get_domains() );
		if( null == $current_domain ) {
			$am->ajax_finish( array( 'message' => __( 'Wrong field domain.', 'wpcf' ) ), false );
		}

		if( !is_array( $fields ) || empty( $fields ) ) {
			$am->ajax_finish( array( 'message' => __( 'No fields have been selected.', 'wpcf' ) ), false );
		}

		// will be sanitized when/if used by the action-specific method
		$action_specific_data = wpcf_getpost( 'action_specific', array() );

		// Process fields one by one
		$errors = array();
		$results = array();
		foreach( $fields as $field ) {

			$result = $this->single_field_control_action( $field_action, $field, $current_domain, $action_specific_data );

			if( is_array( $result ) ) {
				// Array of errors
				$errors = array_merge( $errors, $result );
			} else if( $result instanceof WP_Error ) {
				// Single error
				$errors[] = $result;
			} else if( false == $result ) {
				// This should not happen...!
				$errors[] = new WP_Error( 0, __( 'An unexpected error happened while processing the request.', 'wpcf' ) );
			} else  {
				// Success

				// Save the field definition model as a result if we got a whole definition
				if( $result instanceof WPCF_Field_Definition ) {
					$result = $result->to_json();
				}

				$results[ wpcf_getarr( $field, 'slug' ) ] = $result;
			}
		}

		$data = array( 'results' => $results );
		$is_success = empty( $errors );

		if( !$is_success ) {
			$error_messages = array();
			/** @var WP_Error $error */
			foreach( $errors as $error ) {
				$error_messages[] = $error->get_error_message();
			}
			$data['messages'] = $error_messages;
		}

		$am->ajax_finish( $data, $is_success );

	}


	/**
	 * @param string $action_name One of the allowed action names: 'manage_with_types'
	 * @param array $field Field definition model passed from JS.
	 * @param string $domain Field domain name.
	 * @param mixed $action_specific_data
	 * @return bool|mixed|null|WP_Error|WP_Error[]|WPCF_Field_Definition An error, array of errors, boolean indicating
	 *     success or a result value to be passed back to JS.
	 * @since 2.0
	 */
	private function single_field_control_action( $action_name, $field, $domain, $action_specific_data ) {

		$field_slug = sanitize_text_field( wpcf_getarr( $field, 'slug' ) );

		switch ( $action_name ) {

			case 'manage_with_types':
				return $this->start_managing_field( sanitize_text_field( wpcf_getarr( $field, 'metaKey' ) ), $domain );

			case 'stop_managing_with_types':
				return $this->stop_managing_field( $field_slug, $domain );

			case 'change_group_assignment':
				// $action_specific_data is a list of group slugs, will be sanitized by
				// trying to load a group model
				return $this->change_assignment_to_groups( $field_slug, $domain, $action_specific_data );

			case 'delete_field':
				return $this->delete_field( $field_slug, $domain );

			case 'change_field_type':
				return $this->change_field_type( $field_slug, $domain, $action_specific_data );

			case 'change_field_cardinality':
				return $this->change_field_cardinality( $field_slug, $domain, $action_specific_data );

			default:
				return new WP_Error( 42, __( 'Invalid action name.', 'wpcf' ) );
		}
	}


	/**
	 * Start managing a field with given meta_key with Types.
	 *
	 * Looks if there already exists a field definition that uses the meta_key. If yes, it's most probably a "disabled"
	 * one, that is stored only for the possibility of later "re-activation" (which is happening now). In that case,
	 * the field definition will be simply updated.
	 *
	 * If there is no matching field definition whatsoever, it will be created with in some default manner.
	 * Check WPCF_Field_Definition_Factory::create_field_definition_for_existing_fields() for details.
	 *
	 * AJAX callback helper only, do not use elsewhere.
	 *
	 * @param string $meta_key
	 * @param string $domain Field domain
	 * @return false|null|WPCF_Field_Definition The updated/newly created field definition or falsy value on failure.
	 * @since 2.0
	 */
	public function start_managing_field( $meta_key, $domain ) {
		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->meta_key_belongs_to_types_field( $meta_key, 'definition' );
		if( null == $definition ) {
			$result = $factory->create_field_definition_for_existing_fields( $meta_key );
			if( false != $result ) {
				return $factory->load_field_definition( $result );
			} else {
				return false;
			}
		} else {
			$is_success = $definition->set_types_management_status( true );
			return ( $is_success ? $definition : false );
		}
	}


	/**
	 * Stop managing a field with given field slug by Types.
	 *
	 * AJAX callback helper only, do not use elsewhere.
	 *
	 * @param string $field_slug
	 * @param string $domain Field domain.
	 * @return WP_Error|WPCF_Field_Definition Error with a user-friendly message on failure
	 *     or the updated definition on success.
	 * @since 2.0
	 */
	public static function stop_managing_field( $field_slug, $domain ) {

		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->load_field_definition( $field_slug );

		if( null == $definition ) {

			return new WP_Error( 42, sprintf( __( 'Field definition for field "%s" not found in options.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );

		} else {

			$is_success = $definition->set_types_management_status( false );

			if( $is_success ) {
				return $definition;
			} else {
				return new WP_Error(
					42,
					sprintf(
						__( 'Unable to set types management status for field definition "%s".', 'wpcf' ),
						sanitize_text_field( $field_slug )
					)
				);
			}
		}
	}


	/**
	 * Change which groups is a field definition associated with.
	 *
	 * AJAX callback helper only, do not use elsewhere.
	 *
	 * @param string $field_slug Field definition slug.
	 * @param string $domain Field domain
	 * @param string[][] $groups Action-specific data passed through AJAX. Array containing a single key 'group_slugs',
	 *     containing an array of field group slugs.
	 *
	 * @return WP_Error|WPCF_Field_Definition The updated field definition on success or an error object.
	 * @since 2.0
	 */
	public function change_assignment_to_groups( $field_slug, $domain, $groups ) {
		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->load_field_definition( $field_slug );
		if( null == $definition ) {
			return new WP_Error( 42, sprintf( __( 'Field definition for field "%s" not found in options.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		}
		$new_groups = wpcf_ensarr( wpcf_getarr( $groups, 'group_slugs' ) );
		$associated_groups = $definition->get_associated_groups();
		$is_success = true;
		foreach( $associated_groups as $group ) {
			if( !in_array( $group->get_slug(), $new_groups ) ) {
				$is_success = $is_success && $group->remove_field_definition( $definition );
			}
		}
		$group_factory = $factory->get_group_factory();
		foreach( $new_groups as $new_group_slug ) {
			$new_group = $group_factory->load_field_group( $new_group_slug );
			if( null != $new_group ) {
				$is_success = $is_success && $new_group->add_field_definition( $definition );
			} else {
				$is_success = false;
			}
		}

		if( $is_success ) {
			return $definition;
		} else {
			return new WP_Error();
		}
	}


	/**
	 * Delete a field definition and all values of the field within given domain.
	 *
	 * @param string $field_slug
	 * @param string $domain
	 * @return bool|WP_Error True for success, false or WP_Error on error.
	 * @since 2.0
	 */
	public function delete_field( $field_slug, $domain ) {

		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->load_field_definition( $field_slug );
		if( null == $definition ) {
			return new WP_Error( 42, sprintf( __( 'Field definition for field "%s" not found in options.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		} else if( ! $definition->is_managed_by_types() ) {
			return new WP_Error( 42, sprintf( __( 'Field "%s" will not be deleted because it is not managed by Types.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		}

		$response = $factory->delete_definition( $definition );

		return $response;
	}


	/**
	 * Change a field type for given field definition.
	 *
	 * Performs checks if the conversion is allowed, and if not, generate a proper error message.
	 *
	 * @param string $field_slug
	 * @param string $domain
	 * @param string[] $arguments Needs to contain the 'field_type' key with target type slug.
	 * @return false|WP_Error|WPCF_Field_Definition The updated definition on succes, error/false otherwise.
	 * @since 2.0
	 */
	public function change_field_type( $field_slug, $domain, $arguments ) {

		// Load all information we need, fail if it doesn't exist.
		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->load_field_definition( $field_slug );
		if( null == $definition ) {
			return new WP_Error( 42, sprintf( __( 'Field definition for field "%s" not found in options.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		} else if( ! $definition->is_managed_by_types() ) {
			return new WP_Error( 42, sprintf( __( 'Field "%s" will not be converted because it is not managed by Types.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		}

		$type_slug = sanitize_text_field( wpcf_getarr( $arguments, 'field_type' ) );
		$target_type = Types_Field_Type_Definition_Factory::get_instance()->load_field_type_definition( $type_slug );
		if( null == $target_type ) {
			return new WP_Error( 42, sprintf( __( 'Unknown field type "%s".', 'wpcf' ), $type_slug ) );
		}

		// Check if we can convert between types
		$is_conversion_possible = Types_Field_Type_Converter::get_instance()->is_conversion_possible( $definition->get_type(), $target_type );
		if( !$is_conversion_possible ) {
			return new WP_Error(
				42,
				sprintf(
					__( 'Conversion from type "%s" to "%s" is currently not allowed.', 'wpcf' ),
					$definition->get_type()->get_display_name(),
					$target_type->get_display_name()
				)
			);
		}

		// Check if we can do the conversion with current field's cardinality
		$is_cardinality_sustainable = ( ! $definition->get_is_repetitive() || $target_type->can_be_repetitive() );
		if( !$is_cardinality_sustainable ) {
			return new WP_Error(
				42,
				sprintf(
					__( 'Field "%s" cannot be converted from "%s" to "%s" because it is repetitive and the target type doesn\'t support that.', 'wpcf' ),
					$definition->get_display_name(),
					$definition->get_type()->get_display_name(),
					$target_type->get_display_name()
				)
			);
		}

		// All is fine, proceed.
		$result = $definition->change_type( $target_type );
		if( $result ) {
			return $definition;
		} else {
			// Something unexpected went wrong.
			return false;
		}
	}


	/**
	 * Change cardinality of given field, if it is permitted by its type.
	 *
	 * @param string $field_slug Field definition slug.
	 * @param string $domain Field domain.
	 * @param string[] $arguments Needs to contain the 'target_cardinality' key with 'single'|'repetitive' value.
	 * @return bool|WP_Error|WPCF_Field_Definition The updated definition on succes, error/false otherwise.
	 * @since 2.0
	 */
	public function change_field_cardinality( $field_slug, $domain, $arguments ) {
		$factory = WPCF_Field_Definition_Factory::get_factory_by_domain( $domain );
		$definition = $factory->load_field_definition( $field_slug );
		if( null == $definition ) {
			return new WP_Error( 42, sprintf( __( 'Field definition for field "%s" not found in options.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		} else if( ! $definition->is_managed_by_types() ) {
			return new WP_Error( 42, sprintf( __( 'Field "%s" will not be converted because it is not managed by Types.', 'wpcf' ), sanitize_text_field( $field_slug ) ) );
		}

		$target_cardinality = wpcf_getarr( $arguments, 'target_cardinality', null, array( 'single', 'repetitive' ) );
		if( null == $target_cardinality ) {
			return false;
		}
		$set_as_repetitive = ( 'repetitive' == $target_cardinality );

		$result = $definition->set_is_repetitive( $set_as_repetitive );

		if( $result ) {
			return $definition;
		} else {
			return false;
		}
	}

}