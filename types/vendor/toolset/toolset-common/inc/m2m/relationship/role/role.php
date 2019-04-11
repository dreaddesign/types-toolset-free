<?php

/**
 * Enum class. Defines names of roles that elements can take in a relationship.
 *
 * Note that this enum also supports the strongly-typed approach with objects implementing
 * the IToolset_Relationship_Role interface. It is recommended to use this instead of
 * encouraging more stringly-typed code.
 *
 * @since m2m
 */
abstract class Toolset_Relationship_Role {

	// Don't change these values. They're used also in the database context.
	const PARENT = 'parent';
	const CHILD = 'child';
	const INTERMEDIARY = 'intermediary';


	/**
	 * Get the array of parent and child roles for easy looping.
	 *
	 * @return IToolset_Relationship_Role_Parent_Child[]
	 */
	public static function parent_child() {
		return array(
			new Toolset_Relationship_Role_Parent(),
			new Toolset_Relationship_Role_Child()
		);
	}


	/**
	 * Get the array of parent and child role names for easy looping.
	 *
	 * @return string[]
	 */
	public static function parent_child_role_names() {
		return array( self::PARENT, self::CHILD );
	}


	/**
	 * Get the array of parent, child and intermediary roles for easy looping.
	 *
	 * @return IToolset_Relationship_Role[]
	 */
	public static function all() {
		return array(
			new Toolset_Relationship_Role_Parent(),
			new Toolset_Relationship_Role_Child(),
			new Toolset_Relationship_Role_Intermediary()
		);
	}


	/**
	 * Get the array of parent, child and intermediary role names for easy looping.
	 *
	 * @return string[]
	 */
	public static function all_role_names() {
		return array( self::PARENT, self::CHILD, self::INTERMEDIARY );
	}


	public static function is_valid( $role_name ) {
		return in_array( $role_name, self::all_role_names() );
	}


	/**
	 * Throw an exception if a given role name isn't valid.
	 *
	 * @param string|IToolset_Relationship_Role $role
	 * @param null|string[] $valid_roles Array of roles to accept, defaults to all() roles.
	 * @since m2m
	 */
	public static function validate( $role, $valid_roles = null ) {

		if( $role instanceof IToolset_Relationship_Role ) {
			$role_name = $role->get_name();
		} else {
			$role_name = $role;
		}

		if( null === $valid_roles ) {
			$valid_roles = Toolset_Relationship_Role::all_role_names();
		}

		if( !in_array( $role_name, $valid_roles ) ) {
			throw new InvalidArgumentException( 'Invalid element role name.' );
		}

	}


	/**
	 * Get the other role name.
	 *
	 * @param string $role Parent or child role name.
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function other( $role ) {
		switch( $role ) {
			case self::PARENT:
				return self::CHILD;
			case self::CHILD:
				return self::PARENT;
			default:
				throw new InvalidArgumentException( 'Invalid role name. Parent or child expected.' );
		}
	}

	/**
	 * Organize two elements into an array of parent and child.
	 *
	 * @param $first_element
	 * @param $second_element
	 * @param string|IToolset_Relationship_Role_Parent_Child $first_role Role of the first element (parent or child expected)
	 *
	 * @return array Two provided elements orderd as parent and child.
	 */
	public static function sort_elements( $first_element, $second_element, $first_role ) {

		if( $first_role instanceof IToolset_Relationship_Role_Parent_Child ) {
			$first_role = $first_role->get_name();
		}

		if( self::PARENT === $first_role ) {
			return array( $first_element, $second_element );
		} elseif( self::CHILD === $first_role ) {
			return array( $second_element, $first_element );
		}

		throw new InvalidArgumentException( 'Invalid role name. Parent or child expected.' );
	}


	/**
	 * @param string $role_name
	 *
	 * @return IToolset_Relationship_Role
	 */
	public static function role_from_name( $role_name ) {
		if( $role_name instanceof IToolset_Relationship_Role ) {
			return $role_name;
		}

		switch( $role_name ) {
			case self::PARENT:
				return new Toolset_Relationship_Role_Parent();
			case self::CHILD:
				return new Toolset_Relationship_Role_Child();
			case self::INTERMEDIARY:
				return new Toolset_Relationship_Role_Intermediary();
			default:
				throw new InvalidArgumentException( 'Invalid element role name.' );
		}
	}


	/**
	 * @param string $role_name
	 *
	 * @return IToolset_Relationship_Role_Parent_Child
	 * @since 2.5.10
	 */
	public static function parent_or_child_from_name( $role_name ) {
		$role = self::role_from_name( $role_name );

		if( $role instanceof Toolset_Relationship_Role_Intermediary ) {
			throw new InvalidArgumentException( 'Invalid element role name.' );
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $role;
	}


	/**
	 * @param string|IToolset_Relationship_Role $role_or_name
	 *
	 * @return string
	 */
	public static function name_from_role( $role_or_name ) {
		if( is_string( $role_or_name ) ) {
			if( ! in_array( $role_or_name, self::all_role_names(), true ) ) {
				throw new InvalidArgumentException( 'Invalid element role name.' );
			}

			return $role_or_name;
		}

		if( ! $role_or_name instanceof IToolset_Relationship_Role ) {
			throw new InvalidArgumentException();
		}

		return $role_or_name->get_name();
	}

}
