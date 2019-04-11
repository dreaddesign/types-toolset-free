<?php

/**
 * Holds a relationship cardinality information.
 *
 * This is an immutable class which holds the minimal and maximal limits of elements for both sides of the relationship.
 * See the constructor for further details.
 *
 * @since m2m
 */
class Toolset_Relationship_Cardinality {

	// Constants for better code readability.
	const ONE_ELEMENT = 1;
	const ZERO_ELEMENTS = 0;

	// These values should never be used directly, and never changed.
	const INFINITY = -1;
	const INVALID_VALUE = -2;

	// Internal keys for identifying limits. Never use directly.
	const MIN = 'min';
	const MAX = 'max';


	/** @var int[][] Limits by element keys and by limit keys. */
	private $limits = array();


	/**
	 * Toolset_Relationship_Cardinality constructor.
	 *
	 * There are several ways to provide the limit values.
	 *
	 * (a) Parent and child maximum item limits as integers via both arguments:
	 *          new Toolset_Relationship_Cardinality( self::ONE, self::INFINITY )
	 *
	 * (b) Parent and child maximum item limits as one array:
	 *          $max_limits = array(
	 *              Toolset_Association_Base::PARENT => self::ONE,
	 *              Toolset_Association_Base::CHILD => self::INFINITY
	 *          );
	 *          new Toolset_Relationship_Cardinality( $max_limits );
	 *
	 * (c) Both minimum and maximum limits in one array:
	 *          $limits = array(
	 *              Toolset_Association_Base::PARENT => array(
	 *                  self::ZERO,
	 *                  self::ONE
	 *              ),
	 *              Toolset_Association_Base::CHILD => array(
	 *                  self::ONE,
	 *                  self::INFINITY
	 *              )
	 *          );
	 *          new Toolset_Relationship_Cardinality( $limits );
	 *
	 * In the case of (a) and (b), the minimum limits will default to zero.
	 *
	 * Obviously, the maximum limit must be equal or lower than the minimum one and it must not be a zero.
	 * If an array is provided as a first argument, the second one is ignored.
	 *
	 * @param int|int[]|int[][] $parent_limit_or_limits
	 * @param null|int $child_limit
	 * @since m2m
	 * @throws InvalidArgumentException
	 */
	public function __construct( $parent_limit_or_limits, $child_limit = null ) {

		// If we get an array as first argument, we assume it's the whole input.
		//
		// Otherwise, we're getting only parent and child max limits as the two arguments.
		if( is_array( $parent_limit_or_limits ) ) {
			$parent_limit = toolset_getarr( $parent_limit_or_limits, Toolset_Relationship_Role::PARENT, self::INVALID_VALUE );
			$child_limit = toolset_getarr( $parent_limit_or_limits, Toolset_Relationship_Role::CHILD, self::INVALID_VALUE );
		} else {
			$parent_limit = $parent_limit_or_limits;
		}

		$limits = array(
			Toolset_Relationship_Role::PARENT => $parent_limit,
			Toolset_Relationship_Role::CHILD => $child_limit,
		);

		// Parse limits per individual elements.
		foreach( $limits as $element_role => $limit ) {

			if( ! is_array( $limit ) ) {
				$limit = array(
					self::MIN => self::ZERO_ELEMENTS,
					self::MAX => (int) $limit
				);
			}

			$limit = array(
				self::MIN => (int) $limit[ self::MIN ],
				self::MAX => (int) $limit[ self::MAX ]
			);

			$this->limits[ $element_role ] = $limit;
		}

		$this->validiate_limits();
	}


	/**
	 * Check that stored limit values fulfill all constraints.
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	private function validiate_limits() {

		foreach( $this->limits as $element_role => $element_limits ) {
			Toolset_Association::validate_element_role( $element_role );

			$min = toolset_getarr( $element_limits, self::MIN, self::INVALID_VALUE );
			$max = toolset_getarr( $element_limits, self::MAX, self::INVALID_VALUE );

			if( $min <= self::INVALID_VALUE || $max <= self::INVALID_VALUE ) {
				throw new InvalidArgumentException( 'Invalid cardinality value.' );
			}

			if( self::INFINITY != $max && $min > $max ) {
				throw new InvalidArgumentException( 'Minimum element limit is higher than the maximum one.' );
			}

			if( self::ZERO_ELEMENTS == $max ) {
				throw new InvalidArgumentException( 'Maximum element limit is zero.' );
			}
		}

	}


	/**
	 * Check that the limit key is one of the allowed values.
	 *
	 * @param mixed $limit_key
	 * @throws InvalidArgumentException
	 */
	private static function validate_limit_name( $limit_key ) {
		if( ! in_array( $limit_key, array( self::MIN, self::MAX ) ) ) {
			throw new InvalidArgumentException( 'Invalid limit key.' );
		}
	}


	/**
	 * Get a limit value per element and limit key.
	 *
	 * @param string|IToolset_Relationship_Role_Parent_Child $element_role
	 * @param string $limit_key
	 *
	 * @return int Limit value.
	 * @since m2m
	 */
	public function get_limit( $element_role, $limit_key = self::MAX ) {
		self::validate_limit_name( $limit_key );
		$role_name = Toolset_Relationship_Role::name_from_role( $element_role );

		return $this->limits[ $role_name ][ $limit_key ];
	}


	/**
	 * Convenience method.
	 *
	 * @param string $limit_key
	 * @return int
	 */
	public function get_parent( $limit_key = self::MAX ) {
		return $this->get_limit( Toolset_Relationship_Role::PARENT, $limit_key );
	}


	/**
	 * Convenience method.
	 *
	 * @param string $limit_key
	 * @return int
	 */
	public function get_child( $limit_key = self::MAX ) {
		return $this->get_limit( Toolset_Relationship_Role::CHILD, $limit_key );
	}


	public function has_numeric_limits( $limit_key = self::MAX ) {

		$default_limit_value = ( self::MAX == $limit_key ? self::ONE_ELEMENT : self::ZERO_ELEMENTS );

		// relying on the value of INFINITY
		return (
			$default_limit_value < $this->get_limit( Toolset_Relationship_Role::PARENT, $limit_key )
			|| $default_limit_value < $this->get_limit( Toolset_Relationship_Role::CHILD, $limit_key )
		);
	}


	public function has_limit_to_one() {
		return (
			self::ONE_ELEMENT === $this->get_parent( self::MAX )
			|| self::ONE_ELEMENT === $this->get_child( self::MAX )
		);
	}


	public function is_one_to_many() {
		return (
			self::ONE_ELEMENT === $this->get_parent( self::MAX )
			&& self::ONE_ELEMENT !== $this->get_child( self::MAX )
		);
	}


	public function is_many_to_one() {
		return (
			self::ONE_ELEMENT === $this->get_child( self::MAX )
			&& self::ONE_ELEMENT !== $this->get_parent( self::MAX )
		);
	}


	public function is_many_to_many() {
		return (
			self::ONE_ELEMENT !== $this->get_parent( self::MAX )
			&& self::ONE_ELEMENT !== $this->get_child( self::MAX )
		);
	}


	public function is_one_to_one() {
		return (
			self::ONE_ELEMENT === $this->get_child( self::MAX )
			&& self::ONE_ELEMENT === $this->get_parent( self::MAX )
		);
	}


	public function get_definition_array() {
		return $this->limits;
	}


	public static function get_one_to_many() {
		return new self( self::ONE_ELEMENT, self::INFINITY );
	}


	/**
	 * An opposite of to_string(), create a ccardinality instance from its string representation.
	 *
	 * @param string $value
	 *
	 * @return Toolset_Relationship_Cardinality
	 * @throws InvalidArgumentException
	 */
	public static function from_string( $value ) {
		$values = explode( ':', $value );

		if( 2 === count( $values ) ) {

			$parent_values = explode( '..', $values[0] );
			$child_values = explode( '..', $values[1] );

			if( 2 === count( $parent_values ) && 2 === count( $child_values ) ) {

				return new self(
					array(
						Toolset_Relationship_Role::PARENT => array(
							self::MIN => self::string_to_limit( $parent_values[0] ),
							self::MAX => self::string_to_limit( $parent_values[1] ),
						),
						Toolset_Relationship_Role::CHILD => array(
							self::MIN => self::string_to_limit( $child_values[0] ),
							self::MAX => self::string_to_limit( $child_values[1] ),
						)
					)
				);

			}
		}

		throw new InvalidArgumentException(
			'Invalid cardinality string. Expected format is "{$parent_min}..{$parent_max}:{$child_min}..{$child_max}. Each of these values must be an integer or "*" for infinity.'
		);
	}


	private static function string_to_limit( $value ) {
		if( '*' == $value ) {
			return self::INFINITY;
		} else {
			return $value;
		}
	}


	private function limit_to_string( $value ) {
		return ( self::INFINITY == $value ? '*' : $value );
	}


	/**
	 * Return a non-ambiguous string representation of the cardinality.
	 *
	 * @return string
	 */
	public function to_string() {
		$result = sprintf(
			'%s..%s:%s..%s',
			$this->limit_to_string( $this->get_limit( Toolset_Relationship_Role::PARENT, self::MIN ) ),
			$this->limit_to_string( $this->get_limit( Toolset_Relationship_Role::PARENT, self::MAX ) ),
			$this->limit_to_string( $this->get_limit( Toolset_Relationship_Role::CHILD, self::MIN ) ),
			$this->limit_to_string( $this->get_limit( Toolset_Relationship_Role::CHILD, self::MAX ) )
		);

		return $result;
	}


	/**
	 * Return the cardinality in a two-dimensional associative array.
	 *
	 * First key is 'parent'|'child' and the second one is 'min'|'max'. Value -1 stands for infinity (no limit).
	 * @return int[][]
	 */
	public function to_array() {
		return $this->limits;
	}


	/**
	 * Get a string representation of the cardinality type.
	 *
	 * @return string 'many-to-many'|'one-to-many'|'one-to-one'
	 */
	public function get_type() {
		if( $this->is_many_to_many() ) {
			return 'many-to-many';
		} elseif( $this->is_one_to_many() ) {
			return 'one-to-many';
		} else {
			return 'one-to-one';
		}
	}

}