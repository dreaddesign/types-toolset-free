<?php

/**
 * Static class for shortcut functions related to field types, groups, definitions and instances.
 * @since 1.9
 */
final class Toolset_Field_Utils {

	private function __construct() { }


	// Field domains supported by the page.

	/** @deprecated Use Toolset_Element_Domain::POSTS instead. */
	const DOMAIN_POSTS = 'posts';

	/** @deprecated Use Toolset_Element_Domain::USERS instead. */
	const DOMAIN_USERS = 'users';

	/** @deprecated Use Toolset_Element_Domain::TERMS instead. */
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
	 * @deprecated Use Toolset_Element_Domain::all() instead.
	 */
	public static function get_domains() {
		return array( self::DOMAIN_POSTS, self::DOMAIN_USERS, self::DOMAIN_TERMS );
	}


	/**
	 * @param $domain
	 *
	 * @return Toolset_Field_Definition_Factory
	 * @deprecated Use Toolset_Field_Definition_Factory::get_factory_by_domain() instead.
	 */
	public static function get_definition_factory_by_domain( $domain ) {
		switch( $domain ) {
			case self::DOMAIN_POSTS:
				return Toolset_Field_Definition_Factory_Post::get_instance();
			case self::DOMAIN_USERS:
				return Toolset_Field_Definition_Factory_User::get_instance();
			case self::DOMAIN_TERMS:
				return Toolset_Field_Definition_Factory_Term::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * For a given field domain, return the appropriate field group factory instance.
	 *
	 * @param string $domain
	 * 
	 * @return Toolset_Field_Group_Factory
	 * @since 2.0
	 * @deprecated Use Toolset_Field_Group_Factory::get_factory_by_domain() instead.
	 */
	public static function get_group_factory_by_domain( $domain ) {
		switch( $domain ) {
			case self::DOMAIN_POSTS:
				return Toolset_Field_Group_Post_Factory::get_instance();
			case self::DOMAIN_USERS:
				return Toolset_Field_Group_User_Factory::get_instance();
			case self::DOMAIN_TERMS:
				return Toolset_Field_Group_Term_Factory::get_instance();
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
	 * @return Toolset_Field_Group_Factory
	 * @throws InvalidArgumentException when the post type doesn't belong to any field group.
	 * @since 2.2.6
	 */
	public static function get_group_factory_by_post_type( $group_post_type ) {
		$domains = self::get_domains();
		foreach( $domains as $domain ) {
			$factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );
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
		return toolset_getarr( self::$domain_legacy_value_map, $domain );
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
		return toolset_getarr( $map, $meta_type );
	}

	
	/**
	 * Create a term field instance.
	 *
	 * @param string $field_slug Slug of existing field definition.
	 * @param int $term_id ID of the term where the field belongs.
	 *
	 * @return null|Toolset_Field_Instance Field instance or null if an error occurs.
	 * @since 1.9
	 */
	public static function create_term_field_instance( $field_slug, $term_id ) {
		try {
			return new Toolset_Field_Instance_Term( Toolset_Field_Definition_Factory_Term::get_instance()->load_field_definition( $field_slug ), $term_id );
		} catch( Exception $e ) {
			return null;
		}
	}


	/**
	 * Obtain toolset-forms "field configuration", which is an array of settings for specific field instance.
	 *
	 * @param Toolset_Field_Instance $field
	 * @refactoring This is a hard Toolset Common dependency, and we should move away from it.
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
	 * @param Toolset_Field_Group[] $field_groups
	 * @return Toolset_Field_Definition[]
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


	/**
	 * Aggregate field definitions from associated post field groups for a given post type.
	 *
	 * Note: Due to the mildly unfortunate way of storing field groups and their associated post types,
	 * this is a non-trivial operation. Be careful about performance here.
	 *
	 * @param string $post_type_slug
	 * @return Toolset_Field_Definition[]
	 * @since m2m
	 */
	public static function get_field_definitions_for_post_type( $post_type_slug ) {
		$group_factory = Toolset_Field_Group_Post_Factory::get_instance();
		$groups = $group_factory->get_groups_by_post_type( $post_type_slug );
		$field_definitions = self::get_field_definitions_from_groups( $groups );
		return $field_definitions;
	}


	/**
	 * Obtain a field slug from a variable input if possible. Otherwise throw an exception.
	 *
	 * @param string|Toolset_Field_Definition $field_source It can be either a field slug or a field definition.
	 * @return string Field slug
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public static function get_field_slug( $field_source ) {
		if( is_string( $field_source ) ) {
			return $field_source;
		} elseif( $field_source instanceof Toolset_Field_Definition ) {
			return $field_source->get_slug();
		}

		throw new InvalidArgumentException( 'Invalid field definition.' );
	}
}