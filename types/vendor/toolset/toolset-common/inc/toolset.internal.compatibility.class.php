<?php

/**
 * Common internal compatibility
 *
 * @since 2.3.0
 */

if ( ! class_exists( 'Toolset_Internal_Compatibility' ) ) {
	
	class Toolset_Internal_Compatibility {
		
		function __construct() {
			
			/**
			 * API hooks for plugins compatibility
			 */
			add_filter( 'wpv_filter_wpv_before_set_meta_query', array( $this, 'resolve_types_checkboxes_views_meta_query' ), 10, 2 );
		}
		
		/**
		 * Resolve Types checkboxes query filters in Views and WordPress Archives
		 *
		 * @param $meta_query	array	Meta query instances
		 * @param $data			array(
		 *							domain	string	'posts'|'terms'|'users' The kind of query being run. Optional. Defaults to `posts`.
		 * 						)
		 *
		 * @uses auxiliar_recursive_add_comma_values	Auxiliar method for adding recursive values from a comma-separated list.
		 * @uses types_filter_query_field_definitions	Native Types filter to get Types fields based on some conditions.
		 *
		 * @return array
		 *
		 * @since 2.3.0
		 */
		
		function resolve_types_checkboxes_views_meta_query( $meta_query, $data = array() ) {
			
			$defaults = array(
				'domain'	=> 'posts'
			);
			$data = wp_parse_args( $data, $defaults );
			
			/**
			 * Get Types checkboxes meta fields
			 *
			 * @uses types_filter_query_field_definitions
			 */
			$args = array(
				'domain'		=> $data['domain'],
				'field_type'	=> array( 'checkboxes' )
			);
			$fields_to_check		= apply_filters( 'types_filter_query_field_definitions', array(), $args );
			$fields_to_check_slugs	= wp_list_pluck( $fields_to_check, 'meta_key' );
			
			if ( empty( $fields_to_check ) ) {
				return $meta_query;
			}
			
			global $wp_version;
			
			foreach ( $meta_query as $meta_query_index => $meta_query_entry ) {
				if ( 
					is_array( $meta_query_entry ) 
					&& isset( $meta_query_entry['key'] ) 
					&& in_array( $meta_query_entry['key'], $fields_to_check_slugs )
				) {
					
					$original_meta_query_entry = $meta_query_entry;
					unset( $meta_query[ $meta_query_index ] );
					
					/**
					 * According to http://codex.wordpress.org/Class_Reference/WP_Meta_Query#Accepted_Arguments,
					 * $meta_query_entry['value'] can be an array or a string. In case of a string we additionally allow
					 * multiple comma-separated values.
					 */
					if ( is_array( $meta_query_entry['value'] ) ) {
						$meta_query_entry_values = $meta_query_entry['value'];
						// Add comma-separated combinations of meta values, since a legit value containing a comma might have been removed
						$meta_query_entry_values = $this->auxiliar_recursive_add_comma_values( $meta_query_entry_values );
					} elseif ( is_string( $meta_query_entry['value'] ) ) {
						$meta_query_entry_values = explode( ',', $meta_query_entry['value'] );
						if ( count( $meta_query_entry_values ) > 1 ) {
							// Add comma-separated combinations of meta values, since a legit value containing a comma might have been removed
							$meta_query_entry_values = $this->auxiliar_recursive_add_comma_values( $meta_query_entry_values );
							// Also add the original one, as it might be a legitimate value containing several commas instead of a comma-separated list
							$meta_query_entry_values[] = $meta_query_entry['value'];
						}
					} else {
						// This can happen if $meta_query_entry['value'] is a number, for example.
						$meta_query_entry_values = array( $meta_query_entry['value'] );
					}
					
					$field_definition_candidates	= wp_list_filter( $fields_to_check, array( 'meta_key' => $meta_query_entry['key'] ) );
					$field_definition				= reset( $field_definition_candidates );
					$field_options					= isset( $field_definition['data']['options'] ) ? $field_definition['data']['options'] : array();

					if ( version_compare( $wp_version, '4.1', '<' ) ) {
						// We can not use nested meta_query entries
						foreach ( $meta_query_entry_values as $value_to_filter_by ) {
							foreach ( $field_options as $field_options_key => $field_options_option ) {
								if ( 
									$field_options_option['title'] == $value_to_filter_by 
									|| (
										isset( $field_options_option['set_value'] ) 
										&& $field_options_option['set_value'] == $value_to_filter_by
									)
								) {
									$meta_query[] = array(
										'key' => $meta_query_entry['key'],
										'compare' => in_array( $original_meta_query_entry['compare'], array( '!=', 'NOT LIKE', 'NOT IN' ) ) ? 'NOT LIKE' : 'LIKE',
										'value' => $field_options_key,
										'type' => 'CHAR',
									);
									break;
								}
							}
						}
					} else {
						// We can use nested meta_query entries
						if ( count( $meta_query_entry_values ) < 2 ) {
							// Only one value to filter by, so no need to add nested meta_query entries
							foreach ( $meta_query_entry_values as $value_to_filter_by ) {
								foreach ( $field_options as $field_options_key => $field_options_option ) {
									if ( 
										$field_options_option['title'] == $value_to_filter_by 
										|| (
											isset( $field_options_option['set_value'] ) 
											&& $field_options_option['set_value'] == $value_to_filter_by
										)
									) {
										$meta_query[] = array(
											'key' => $meta_query_entry['key'],
											'compare' => in_array( $original_meta_query_entry['compare'], array( '!=', 'NOT LIKE', 'NOT IN' ) ) ? 'NOT LIKE' : 'LIKE',
											'value' => $field_options_key,
											'type' => 'CHAR',
										);
										break;
									}
								}
							}
						} else {
							// We will translate each value into a meta_query clause and add them all as a nested meta_query entry
							$inner_relation = in_array( $original_meta_query_entry['compare'], array( '!=', 'NOT LIKE', 'NOT IN' ) ) ? 'AND' : 'OR';
							$inner_compare = in_array( $original_meta_query_entry['compare'], array( '!=', 'NOT LIKE', 'NOT IN' ) ) ? 'NOT LIKE' : 'LIKE';
							$inner_meta_query = array(
								'relation' => $inner_relation
							);
							foreach ( $meta_query_entry_values as $value_to_filter_by ) {
								foreach ( $field_options as $field_options_key => $field_options_option ) {
									if ( 
										$field_options_option['title'] == $value_to_filter_by 
										|| (
											isset( $field_options_option['set_value'] ) 
											&& $field_options_option['set_value'] == $value_to_filter_by
										)
									) {
										$inner_meta_query[] = array(
											'key' => $meta_query_entry['key'],
											'compare' => $inner_compare,
											'value' => $field_options_key,
											'type' => 'CHAR',
										);
										break;
									}
								}
							}
							$meta_query[] = $inner_meta_query;
						}
					}
					
				}
			}
			
			return $meta_query;
			
		}
		
		/**
		 * auxiliar_recursive_add_comma_values
		 *
		 * Transform an array of values into an array with all possible ordered combinations of those values as comma-separated items.
		 *
		 * @param $values	array
		 *
		 * @return array
		 *
		 * @since 2.3.0
		 */
		
		function auxiliar_recursive_add_comma_values( $values ) {
			$values_orig = array_reverse( $values );
			$values_aux = array();
			$values_end = array();
			if ( count( $values ) > 1 ) {
				foreach ( $values_orig as $v_key => $v_val ) {
					if ( count( $values_aux ) > 0 ) {
						foreach ( $values_aux as &$v_aux ) {
							$values_end[] = $v_val . ',' . $v_aux;
							$v_aux = $v_val . ',' . $v_aux;
						}
					}
					$values_end[] = $v_val;
					$values_aux[] = $v_val;
				}
			} else {
				$values_end = $values;
			}
			return $values_end;
		}
		
	}
	
}