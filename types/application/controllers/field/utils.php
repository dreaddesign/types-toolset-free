<?php

/**
 * Static class for shortcut functions related to field types, groups, definitions and instances.
 * @since 1.9
 */
final class Types_Field_Utils {

	private function __construct() { }


	// Field domains supported by the page.
	const DOMAIN_POSTS = 'posts';
	const DOMAIN_USERS = 'users';
	const DOMAIN_TERMS = 'terms';

	// Since PHP 5.6, noooo!
	// const DOMAINS = array( self::DOMAIN_POSTS, self::DOMAIN_USERS, self::DOMAIN_TERMS );

	
	/**
	 * Array of valid field domains.
	 *
	 * Replacement for self::DOMAINS because damn you old PHP versions.
	 *
	 * @return array
	 * @since 2.0
	 */
	public static function get_domains() {
		return array( self::DOMAIN_POSTS, self::DOMAIN_USERS, self::DOMAIN_TERMS );
	}


	/**
	 * @param $domain
	 *
	 * @return WPCF_Field_Definition_Factory
	 * @deprecated Use WPCF_Field_Definition_Factory::get_factory_by_domain() instead.
	 */
	public static function get_definition_factory_by_domain( $domain ) {
		switch( $domain ) {
			case self::DOMAIN_POSTS:
				return WPCF_Field_Definition_Factory_Post::get_instance();
			case self::DOMAIN_USERS:
				return WPCF_Field_Definition_Factory_User::get_instance();
			case self::DOMAIN_TERMS:
				return WPCF_Field_Definition_Factory_Term::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * For a given field domain, return the appropriate field group factory instance.
	 *
	 * @param string $domain
	 * 
	 * @return Types_Field_Group_Factory
	 * @since 2.0
	 * @deprecated Use Types_Field_Group_Factory::get_factory_by_domain() instead.
	 */
	public static function get_group_factory_by_domain( $domain ) {
		switch( $domain ) {
			case self::DOMAIN_POSTS:
				return Types_Field_Group_Post_Factory::get_instance();
			case self::DOMAIN_USERS:
				return Types_Field_Group_User_Factory::get_instance();
			case self::DOMAIN_TERMS:
				return Types_Field_Group_Term_Factory::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * Get the correct field group factory for provided underlying post type of the field group.
	 *
	 * This should not be needed from outside the legacy code.
	 *
	 * @param string $group_post_type
	 * @return Types_Field_Group_Factory
	 * @throws InvalidArgumentException when the post type doesn't belong to any field group.
	 * @since 2.2.4
	 */
	public static function get_group_factory_by_post_type( $group_post_type ) {
		$domains = self::get_domains();
		foreach( $domains as $domain ) {
			$factory = Types_Field_Group_Factory::get_factory_by_domain( $domain );
			if( $factory->get_post_type() == $group_post_type ) {
				return $factory;
			}
		}
		throw new InvalidArgumentException( 'Invalid field group post type.' );
	}


	private static $domain_legacy_value_map = array(
		self::DOMAIN_POSTS => 'postmeta',
		self::DOMAIN_USERS => 'usermeta',
		self::DOMAIN_TERMS => 'termmeta'
	);


	/**
	 * Translate a field domain into a "meta_type" value, which is used in field definition arrays.
	 *
	 * @param string $domain
	 * @return string
	 * @since 2.0
	 */
	public static function domain_to_legacy_meta_type( $domain ) {
		return wpcf_getarr( self::$domain_legacy_value_map, $domain );
	}


	/**
	 * Translate a "meta_type" value into a field domain name.
	 *
	 * @param $meta_type
	 * @return string
	 * @since 2.1
	 */
	public static function legacy_meta_type_to_domain( $meta_type ) {
		$map = array_flip( self::$domain_legacy_value_map );
		return wpcf_getarr( $map, $meta_type );
	}

	
	/**
	 * Create a term field instance.
	 *
	 * @param string $field_slug Slug of existing field definition.
	 * @param int $term_id ID of the term where the field belongs.
	 *
	 * @return null|WPCF_Field_Instance Field instance or null if an error occurs.
	 * @since 1.9
	 */
	public static function create_term_field_instance( $field_slug, $term_id ) {
		try {
			return new WPCF_Field_Instance_Term( WPCF_Field_Definition_Factory_Term::get_instance()->load_field_definition( $field_slug ), $term_id );
		} catch( Exception $e ) {
			return null;
		}
	}


	/**
	 * Obtain toolset-forms "field configuration", which is an array of settings for specific field instance.
	 *
	 * @param WPCF_Field_Instance $field
	 *
	 * @since 1.9
	 * @return array
	 */
	public static function get_toolset_forms_field_config( $field ) {
		return wptoolset_form_filter_types_field(
			$field->get_definition()->get_definition_array(),
			$field->get_object_id()
		);
	}


	/**
	 * Gather an unique array of field definitions from given groups.
	 *
	 * The groups are expected to belong to the same domain (term/post/user), otherwise problems may occur when
	 * field slugs conflict.
	 *
	 * @param Types_Field_Group[] $field_groups
	 * @return WPCF_Field_Definition[]
	 * @since 1.9
	 */
	public static function get_field_definitions_from_groups( $field_groups ) {
		$field_definitions = array();
		foreach( $field_groups as $group ) {
			$group_field_definitions = $group->get_field_definitions();

			foreach( $group_field_definitions as $field_definition ) {
				$field_definitions[ $field_definition->get_slug() ] = $field_definition;
			}
		}
		return $field_definitions;
	}
	
}