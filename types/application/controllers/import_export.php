<?php

/**
 * Types import and export controller.
 *
 * Currently it only contains new code that is hooked into legacy methods plus a bunch of temporary workarounds, but
 * has the ambition to become the central point of handling all import and export-related activities.
 *
 * @since 2.1
 */
final class Types_Import_Export {

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __clone() {
	}

	private function __construct() {
	}


	/**
	 * Non-associative arrays which are to be exported to XML need to contain this key. Its value
	 * determines node names of all other items. For example,
	 *
	 * 'terms' => array( 'a', 'b', '__key' => 'term' )
	 *
	 * will translate into
	 *
	 * <terms>
	 *     <term>a</term>
	 *     <term>b</term>
	 * </terms>
	 *
	 * @since 2.1
	 */
	const XML_KEY = '__key';


	/**
	 * Hash and checksum is used to check if a certain post types, custom field groups inside a module is changed.
	 * It's using MD5hash. This will be used by module manager to check if the module is updated (meaning the types
	 * definition) has changed. And it will alert the user that he is importing a different module.
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/types-749#comment=102-105945
	 */
	const XML_KEY_CHECKSUM = 'checksum';
	const XML_KEY_HASH = 'hash';


	/**
	 * '__types_id' is used as a unique identifier for CPT,Types taxonomy, custom fields groups, etc. As for the
	 * allowed values, it's usually using a slug as an identifier since this will not change during import/export.
	 * IDs on the otherhand will change after import.
	 *
	 * Now '__types_title' is just the title/label name corresponding to the '__types_id'.
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/types-749#comment=102-105945
	 */
	const XML_TYPES_ID = '__types_id';
	const XML_TYPES_TITLE = '__types_title';


	/** Element name for a single field group. */
	const XML_KEY_GROUP = 'group';


	/** Element name for a single field definition. */
	const XML_KEY_FIELD = 'field';


	/**
	 * Completely handle retrieving export data for field groups of one domain.
	 *
	 * @param string $domain Valid field domain.
	 *
	 * @return array Exported field groups.
	 * @since 2.1
	 */
	public function export_field_groups_for_domain( $domain ) {

		$group_factory = Types_Field_Utils::get_group_factory_by_domain( $domain );
		$all_groups    = $group_factory->query_groups();

		// Each group will handle its own export.
		$results = array();
		foreach ( $all_groups as $field_group ) {
			$results[] = $field_group->get_export_object();
		}

		$results[ self::XML_KEY ] = self::XML_KEY_GROUP;

		return $results;
	}


	/**
	 * Completely handle retrieving export data for field definitions of one domain.
	 *
	 * @param string $domain Valid field domain.
	 *
	 * @return array Exported field definitions.
	 * @since 2.1
	 */
	public function export_field_definitions_for_domain( $domain ) {

		$definition_factory = Types_Field_Utils::get_definition_factory_by_domain( $domain );
		$all_definitions    = $definition_factory->query_definitions( array( 'filter' => 'types' ) );

		// Each field definition will handle its own export.
		$results = array();
		/** @var WPCF_Field_Definition $field_definition */
		foreach ( $all_definitions as $field_definition ) {
			$results[] = $field_definition->get_export_object();
		}

		$results[ self::XML_KEY ] = self::XML_KEY_FIELD;

		return $results;
	}


	/**
	 * Generate a checksum for an to-be-exported object and store it.
	 *
	 * @param array $data An associative array representing an object.
	 * @param null|string[] $keys_for_checksum Array keys that should be used when generating the checksum. If null,
	 *     the whole $data is used.
	 * @param null|string[] $keys_to_remove Array keys that should be _not_ used when generating the checksum.
	 *
	 * @return array The updated $data with XML_KEY_CHECKSUM and XML_KEY_HASH set.
	 * @since 2.1
	 */
	public function add_checksum_to_object( $data, $keys_for_checksum = null, $keys_to_remove = null ) {

		// pluck requested keys
		if ( null == $keys_for_checksum ) {
			$checksum_source = $data;
		} else {
			$checksum_source = array();
			foreach ( $data as $key => $value ) {
				if ( in_array( $key, $keys_for_checksum ) ) {
					$checksum_source[ $key ] = $value;
				}
			}
		}

		// unset undesired keys
		if ( is_array( $keys_to_remove ) ) {
			$checksum_source = $this->unset_recursive( $checksum_source, $keys_to_remove );
		}

		$checksum = $this->generate_checksum( $checksum_source );

		$data[ self::XML_KEY_CHECKSUM ] = $checksum;
		$data[ self::XML_KEY_HASH ]     = $checksum;

		return $data;
	}


	/**
	 * Unset keys from a multidimensional array.
	 *
	 * @param array $unset_from The source array.
	 * @param array $unset_what Definition of keys that need to be unset. Each element of this array can be either
	 *     a string, in which case it represents an key to be removed on the top level, or an 'key' => array( ... )
	 *     value, in which case the unsetting will happen in $unset_from['key'].
	 *
	 *     For example, unsetting array( 'c', 'd' => array( 'b' ) ) from
	 *
	 *     array( 'a' => 1, 'b' => 2, 'c' => array( 'a' => 1  ), 'd' => array( 'a' => 1, 'b' => 2 ) )
	 *
	 *     will return
	 *
	 *     array( 'a' => 1, 'b' => 2, 'd' => array( 'a' => 1 ) )
	 *
	 * @return array
	 * @since 2.1
	 */
	private function unset_recursive( $unset_from, $unset_what ) {
		if ( is_array( $unset_from ) ) {

			foreach ( $unset_what as $key => $value ) {

				if ( is_array( $value ) && isset( $unset_from[ $key ] ) ) {
					$unset_from[ $key ] = $this->unset_recursive( $unset_from[ $key ], $value );
				} else {
					unset( $unset_from[ $value ] );
				}
			}

		}

		return $unset_from;
	}


	/**
	 * Add standard object annotation XML_TYPES_ID and XML_TYPES_TITLE.
	 *
	 * @param array $data An associative array representing an object.
	 * @param string $title
	 * @param string $id
	 *
	 * @return array Updated $data.
	 * @since 2.1
	 */
	public function annotate_object( $data, $title, $id ) {
		$data[ self::XML_TYPES_ID ]    = $id;
		$data[ self::XML_TYPES_TITLE ] = $title;

		return $data;
	}


	/**
	 * Generate a checksum for given object.
	 *
	 * Note: Do not touch this.
	 *
	 * @param array $data
	 *
	 * @return string Checksum
	 * @since 2.1
	 */
	private function generate_checksum( $data ) {
		return md5( maybe_serialize( $this->ksort_as_string( $data ) ) );
	}


	/**
	 * Sort a multidimensional array by keys recursively.
	 *
	 * @param array|mixed $data
	 *
	 * @return array|mixed Sorted $data.
	 * @since 2.1
	 */
	private function ksort_as_string( $data ) {
		if ( is_array( $data ) ) {
			ksort( $data, SORT_STRING );
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->ksort_as_string( $value );
			}
		}

		return $data;
	}


	/**
	 * Import field definitions for given domain.
	 *
	 * Note: Currently only term fields are supported.
	 *
	 * @param string $domain Valid field domain.
	 * @param SimpleXMLElement $data Import data from XML.
	 * @param string $fields_key Node name where the field definitions can be found.
	 * @param bool $delete_other_fields If true, fields that are not being imported will be deleted from the site.
	 * @param array $field_settings Part of $_POST from the import form related to these fields.
	 * @param array $args Used for making skip/overwrite decisions per item (used for toolset-based themes import)
	 *
	 * @return array
	 */
	public function process_field_definition_import_per_domain(
		$domain,
		$data,
		$fields_key,
		$delete_other_fields,
		$field_settings,
		$args = array()
	) {

		$results = array();

		$fields_to_preserve = array();

		$fields_import_data = array();
		if ( isset( $data->$fields_key ) ) {
			/** @noinspection PhpParamsInspection */
			$fields_import_data = $this->simplexmlelement_to_object( $data->$fields_key, true );
			$fields_import_data = isset( $fields_import_data[ Types_Import_Export::XML_KEY_FIELD ] )
				? $fields_import_data[ Types_Import_Export::XML_KEY_FIELD ] : array();
		}

		foreach ( $fields_import_data as $field_import_data ) {
			$field_slug = $field_import_data['slug'];

			// check if via $_POST the field is allowed
			$import_field = isset( $field_settings[ $field_slug ] ) && isset( $field_settings[ $field_slug ]['add'] );

			// on toolset based themes we allow every field
			$import_field = array_key_exists( 'toolset-themes', $args ) ? true : $import_field;

			// User choices on import/update process (TBT toolset-based themes)
			$field_import_data = tbt_user_choice( $args, $field_import_data );
			if ( array_key_exists( 'add', $field_import_data ) ) {
				$import_field = $field_import_data['add'];
			}

			if ( $import_field ) {
				$result = $this->import_field_definition( $domain, $field_import_data );
				if ( $result['is_success'] ) {
					$fields_to_preserve[] = $field_slug;
				}
				$results[] = array(
					'type'    => ( $result['is_success'] ? 'success' : 'error' ),
					'content' => $result['display_message']
				);
			}
		}

		$delete_results = $this->maybe_delete_fields( $domain, $delete_other_fields, $fields_to_preserve );
		$results        = array_merge( $results, $delete_results );

		return $results;

	}


	/**
	 * @param string $domain
	 * @param bool $delete_other_fields
	 * @param string[] $fields_to_preserve Array of field slugs that should be preserved.
	 *
	 * @return array
	 */
	private function maybe_delete_fields( $domain, $delete_other_fields, $fields_to_preserve ) {

		$results = array();

		$definition_factory = Types_Field_Utils::get_definition_factory_by_domain( $domain );
		$option_name        = $definition_factory->get_option_name_workaround();

		if ( $delete_other_fields ) {

			$fields_existing = wpcf_admin_fields_get_fields( false, false, false, $option_name, false, true );

			foreach ( $fields_existing as $key => $existing_field_definition ) {

				if ( ! empty( $existing_field_definition['data']['controlled'] ) ) {
					continue;
				}

				$existing_field_slug = $existing_field_definition['slug'];

				if ( ! in_array( $existing_field_slug, $fields_to_preserve ) ) {
					$results[] = array(
						'type'    => 'success',
						'content' => sprintf(
							__( 'User field "%s" deleted', 'wpcf' ),
							$existing_field_definition['name']
						)
					);
					unset( $fields_existing[ $key ] );
				}
			}

			update_option( $option_name, $fields_existing );
		}


		return $results;
	}


	/**
	 * @param string $domain
	 * @param array $definition_array_import Field definition array (will contain some additional elements from the import file).
	 *
	 * @return array
	 */
	private function import_field_definition( $domain, $definition_array_import ) {

		$definition = array(
			'id'          => $definition_array_import['id'],
			'name'        => $definition_array_import['name'],
			'description' => isset( $definition_array_import['description'] ) ? $definition_array_import['description']
				: '',
			'type'        => $definition_array_import['type'],
			'slug'        => $definition_array_import['slug'],
			'data'        => ( isset( $definition_array_import['data'] ) && is_array( $definition_array_import['data'] ) )
				? $definition_array_import['data'] : array()
		);

		if ( isset( $definition_array_import['meta_key'] ) ) {
			$definition['meta_key'] = $definition_array_import['meta_key'];
		}

		// WPML
		global $iclTranslationManagement;
		if ( ! empty( $iclTranslationManagement ) && isset( $definition['wpml_action'] ) ) {
			$iclTranslationManagement->settings['custom_fields_translation'][ wpcf_types_get_meta_prefix( $definition ) . $definition['slug'] ] = $definition['wpml_action'];
			$iclTranslationManagement->save_settings();
		}

		$definition_factory = Types_Field_Utils::get_definition_factory_by_domain( $domain );
		$definition_factory->set_field_definition_workaround( $definition['slug'], $definition );

		return array(
			'is_success'      => true,
			'display_message' => sprintf( __( 'Term field "%s" added/updated', 'wpcf' ), $definition['name'] )
		);
	}


	/**
	 * Import field definitions for given domain.
	 *
	 * Note: Currently only term fields are supported.
	 *
	 * @param string $domain Valid field domain.
	 * @param SimpleXMLElement $data Import data from XML.
	 * @param string $groups_key Node name where the field groups can be found.
	 * @param bool $bulk_overwrite_groups If true, all (conflicting) groups will be overwritten by the ones from import.
	 * @param bool $delete_other_groups If true, groups that are not being imported will be deleted from the site.
	 * @param array $group_settings Part of $_POST from the import form related to these groups.
	 * @param array $args Used for making skip/overwrite decisions per item (used for toolset-based themes import)
	 *
	 * @return array
	 */
	public function process_field_group_import_per_domain(
		$domain,
		$data,
		$groups_key,
		$bulk_overwrite_groups,
		$delete_other_groups,
		$group_settings,
		$args = array()
	) {

		$results            = array();
		$groups_to_preserve = array();

		$groups_import_data = array();
		if ( isset( $data->$groups_key ) ) {
			/** @noinspection PhpParamsInspection */
			$groups_import_data = $this->simplexmlelement_to_object( $data->$groups_key, true );
			$groups_import_data = isset( $groups_import_data[ Types_Import_Export::XML_KEY_GROUP ] )
				? $groups_import_data[ Types_Import_Export::XML_KEY_GROUP ] : array();
		}

		foreach ( $groups_import_data as $group ) {

			// ID of group from the import file
			$import_group_id = wpcf_getarr( $group, Types_Field_Group::XML_ID );


			if ( array_key_exists( 'toolset-themes', $args ) ) {
				// Toolset Themes Import Way
				$group = tbt_user_choice( $args, $group );

				$group_should_be_imported = $this->tbt_group_should_be_imported( $group );

				if ( array_key_exists( 'add', $group ) ) {
					$group_actions['add'] = $group['add'];
				}
			} else {
				// Types Import Way
				$group_actions            = wpcf_getarr( $group_settings, $import_group_id, array( 'add' => true ) );
				$group_should_be_imported = isset( $group_actions['add'] );
			}

			if ( $group_should_be_imported ) {
				if ( $bulk_overwrite_groups                          // bulk overwrite (types import/export page)
				     || (                                            // OR...
					     array_key_exists( 'toolset-themes', $args )    //  import with toolset themes
					     && (                                        //  AND...
						     ! array_key_exists( 'update', $group )  //      ADD for group is not SET (new item)
						     || $group['update']                     //      OR... UPDATE is set for item (user choice)
					     )
				     )
				) {
					// OVERWRITE
					$group_action = 'update';
				} else {
					$group_action = wpcf_getarr( $group_actions, 'update', 'add', array( 'add', 'update' ) );
				}
			} else {
				$group_action = 'nothing';
			}

			$result = null;

			switch ( $group_action ) {
				case 'add':
					$result = $this->import_field_group( $domain, $group, 'create_new' );
					break;
				case 'update':
					$result = $this->import_field_group( $domain, $group, 'overwrite' );
					break;
			}

			if ( null != $result ) {
				$results[] = array(
					'type'    => ( $result['is_success'] ? 'success' : 'error' ),
					'content' => $result['display_message']
				);
				if ( $result['is_success'] ) {
					$groups_to_preserve[] = $result['new_group_id'];
				}
			}

		}

		$delete_results = $this->maybe_delete_groups( $domain, $delete_other_groups, $groups_to_preserve );
		$results        = array_merge( $results, $delete_results );

		return $results;
	}


	/**
	 * @param string $domain
	 * @param bool $delete_other_groups
	 * @param int[] $groups_to_preserve
	 *
	 * @return array
	 */
	private function maybe_delete_groups( $domain, $delete_other_groups, $groups_to_preserve ) {

		$results = array();
		if ( $delete_other_groups && ! empty( $groups_to_preserve ) ) {
			$group_factory = Types_Field_Utils::get_group_factory_by_domain( $domain );
			$all_groups    = $group_factory->query_groups();

			foreach ( $all_groups as $group_to_delete ) {
				if ( ! in_array( $group_to_delete->get_id(), $groups_to_preserve ) ) {

					$deleted_group_name = $group_to_delete->get_name();
					$deleted            = wp_delete_post( $group_to_delete->get_id(), true );
					if ( ! $deleted ) {
						$results[] = array(
							'type'    => 'error',
							'content' => sprintf( __( 'Term field group "%s" delete failed', 'wpcf' ),
								$deleted_group_name )
						);
					} else {
						$results[] = array(
							'type'    => 'success',
							'content' => sprintf( __( 'Term field group "%s" deleted', 'wpcf' ), $deleted_group_name )
						);
					}

				}
			}
		}

		return $results;

	}


	/**
	 * @param string $domain Valid field domain
	 * @param array $group Field group import data as associative array.
	 * @param string $conflict_resolution = 'overwrite'|'create_new' Defines how to handle a situation when a
	 *     field group already exists in the database.
	 *
	 * @return array Import results:
	 *     'is_success' bool
	 *     'display_message' string
	 *     'new_group_id' int
	 *
	 * @since 2.1
	 */
	public function import_field_group( $domain, $group, $conflict_resolution ) {

		$group_slug = wpcf_getarr( $group, Types_Field_Group::XML_SLUG );

		$group_factory = Types_Field_Utils::get_group_factory_by_domain( $domain );

		$existing_groups      = $group_factory->query_groups( array( 'name' => $group_slug ) );
		$group_already_exists = ( count( $existing_groups ) > 0 );

		$new_post = array(
			'post_status'  => $group['post_status'],
			'post_type'    => $group_factory->get_post_type(),
			'post_title'   => $group['post_title'],
			'post_content' => ! empty( $group['post_content'] ) ? $group['post_content'] : '',
		);

		$update_existing = ( $group_already_exists && 'overwrite' == $conflict_resolution );

		if ( $update_existing ) {
			$existing_group = $existing_groups[0];
			$new_post['ID'] = $existing_group->get_id();
			$new_group_id   = wp_update_post( $new_post );
		} else {
			$new_group_id = wp_insert_post( $new_post, true );
		}

		$is_success = ( ! is_wp_error( $new_group_id ) && 0 < $new_group_id );

		// Update group's postmeta
		if ( $is_success && ! empty( $group['meta'] ) ) {
			foreach ( $group['meta'] as $meta_key => $meta_value ) {
				if ( Types_Field_Group_Term::POSTMETA_ASSOCIATED_TAXONOMY == $meta_key ) {
					$meta_values = explode( ',', $meta_value );
					delete_post_meta( $new_group_id, $meta_key );
					foreach ( $meta_values as $single_meta_value ) {
						update_post_meta( $new_group_id, $meta_key, $single_meta_value );
					}
				} else {
					update_post_meta( $new_group_id, $meta_key, $meta_value );
				}
			}
		}

		// Create display message
		if ( $is_success ) {
			if ( $update_existing ) {
				$display_message = sprintf( __( 'Term field group "%s" updated', 'wpcf' ), $group['post_title'] );
			} else {
				$display_message = sprintf( __( 'Term field group "%s" added', 'wpcf' ), $group['post_title'] );
			}
		} else {
			if ( $update_existing ) {
				$display_message = sprintf( __( 'Term field group "%s" update failed', 'wpcf' ), $group['post_title'] );
			} else {
				$display_message = sprintf( __( 'Term field group "%s" insert failed', 'wpcf' ), $group['post_title'] );
			}
		}

		return array(
			'is_success'      => $is_success,
			'display_message' => $display_message,
			'new_group_id'    => $new_group_id
		);
	}


	/**
	 * @param SimpleXMLElement $element
	 * @param bool $allways_expand_top_level
	 *
	 * @return array|null
	 */
	public function simplexmlelement_to_object( $element, $allways_expand_top_level = false ) {
		$text_content = trim( (string) $element );
		if ( ! empty( $text_content ) ) {
			return $text_content;
		}

		if ( $element->count() > 0 ) {
			$results_by_node_name = array();

			/** @var SimpleXMLElement $child */
			foreach ( $element->children() as $child ) {
				$child_name = $child->getName();

				if ( ! isset( $results_by_node_name[ $child_name ] ) ) {
					$results_by_node_name[ $child_name ] = array();
				}

				$results_by_node_name[ $child_name ][] = $this->simplexmlelement_to_object( $child, false );
			}

			$results = array();
			foreach ( $results_by_node_name as $node_name => $children ) {
				$take_only_first_child = ( count( $children ) == 1 && ! $allways_expand_top_level );
				$results[ $node_name ] = ( $take_only_first_child ? $children[0] : $children );
			}

			return $results;
		}

		return null;
	}

	/**
	 * Determines if a group should be imported or not
	 * This is only relevant for the import process of Toolset Themes
	 *
	 * @param $group
	 *
	 * @return bool
	 */
	private function tbt_group_should_be_imported( $group ) {
		if ( array_key_exists( 'add', $group ) && $group['add'] === false ) {
			// the group already exists, but the user wants to keep his version ('add' === false)
			return false;
		}

		// import group (initial import / overwrite / duplicate)
		return true;
	}

}