<?php

/**
 * Post type querying.
 *
 * Usage:
 *     $query = new Toolset_Post_Type_Query( $query_args );
 *     $post_types = $query->get_results();
 *
 * Accepted query arguments:
 *     from_types (bool): Is the post type registered by Types?
 *     is_intermediary (bool): Is the post type used as an intermediary one in a relationship?
 *     is_repeating_field_group (bool): Is the post type used as a repeating field group?
 *     has_special_purpose (bool|null): Has the post a special purpose (meaning it shouldn't be editable and allowed
 *         for arbitrary use like standard post types)? If there's no query for this, is_intermediary
 *         or is_repeating_field_group, this query argument defaults to false. Providing null will return
 *         both special and non-special post types.
 *     ...
 *
 * Feel free to extend this with new query arguments.
 *
 * @since m2m
 */
class Toolset_Post_Type_Query {

	// Accepted query arguments
	const FROM_TYPES = 'from_types';
	const IS_INTERMEDIARY = 'is_intermediary';
	const IS_REPEATING_FIELD_GROUP = 'is_repeating_field_group';
	const HAS_SPECIAL_PURPOSE = 'has_special_purpose';
	const IS_EXCLUDED = 'is_excluded';
	const IS_PUBLIC = 'is_public';
	const IS_REGISTERED = 'is_registered';
	const IS_INVOLVED_IN_RELATIONSHIP = 'is_involved_in_relationship';
	const IS_TRANSLATABLE = 'is_translatable';

	const RETURN_TYPE = 'return';

	/** @var array Arguments for the current query. */
	private $query_args;

	/**
	 * Definition of filters for post types. Each key represents one query argument, and a value
	 * is an associative array with following elements:
	 *     - filter_args: Arbitrary parameter for the callback
	 *     - callback: A callable that accepts three parameters: Array of post types to filter,
	 *       value of the query argument and the arbitrary paramerter from filter_args.
	 *
	 * @var array
	 */
	private $filters;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/** @var Toolset_Post_Type_Exclude_List|null */
	private $exclude_list;


	/**
	 * Toolset_Post_Type_Query constructor.
	 *
	 * @param array $query_args Query arguments.
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 * @param Toolset_Post_Type_Exclude_List|null $exclude_list_di
	 */
	public function __construct(
		$query_args,
		Toolset_Post_Type_Repository $post_type_repository_di = null,
		Toolset_Post_Type_Exclude_List $exclude_list_di = null
	) {
		$this->set_query_args( $query_args );

		$this->filters = array(
			self::FROM_TYPES => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_from_types'
			),
			self::IS_INTERMEDIARY => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_intermediary'
			),
			self::IS_REPEATING_FIELD_GROUP => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_repeating_field_group'
			),
			self::HAS_SPECIAL_PURPOSE => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'has_special_purpose'
			),
			self::IS_EXCLUDED => array(
				'callback' => array( $this, 'filter_by_exclusion' )
			),
			self::IS_PUBLIC => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_public'
			),
			self::IS_REGISTERED => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_registered'
			),
			self::IS_INVOLVED_IN_RELATIONSHIP => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_involved_in_relationship'
			),
			self::IS_TRANSLATABLE => array(
				'callback' => array( $this, 'filter_bool_property' ),
				'filter_args' => 'is_translatable'
			),
		);

		$this->post_type_repository = (
			null === $post_type_repository_di
				? Toolset_Post_Type_Repository::get_instance()
				: $post_type_repository_di
		);

		$this->exclude_list = ( null === $exclude_list_di ? new Toolset_Post_Type_Exclude_List() : $exclude_list_di );

	}


	private function set_query_args( $query_args ) {
		$query_args = toolset_ensarr( $query_args );

		// If not querying for a special-purpose post types explicitly, default to excluding them.
		if( ! array_key_exists( self::HAS_SPECIAL_PURPOSE, $query_args )
			&& ! array_key_exists( self::IS_REPEATING_FIELD_GROUP, $query_args )
			&& ! array_key_exists( self::IS_INTERMEDIARY, $query_args )
		) {
			$query_args[ self::HAS_SPECIAL_PURPOSE ] = false;
		}

		// If not querying for excluded post types explicitly, default to excluding them
		if( ! array_key_exists( self::IS_EXCLUDED, $query_args ) ) {
			$query_args[ self::IS_EXCLUDED ] = false;
		}

		$this->query_args = $query_args;
	}


	/**
	 * Return only those query arguments that are defined as filters.
	 *
	 * @return array
	 */
	private function get_filters() {
		return array_intersect_key( $this->query_args, $this->filters );
	}


	/**
	 * Query post types and return the result.
	 *
	 * @return string[]|IToolset_Post_Type[]
	 */
	public function get_results() {

		$post_types = $this->post_type_repository->get_all();

		foreach( $this->get_filters() as $arg => $argument_value ) {
			$filter = toolset_getarr( $this->filters, $arg, null );

			$filter_callback = toolset_getarr( $filter, 'callback' );

			if( ! is_callable( $filter_callback ) ) {
				throw new InvalidArgumentException( sprintf( 'Unrecognized query argument "%s".', $arg ) );
			}

			// PHP 5.5 compatibility.
			$post_types = call_user_func( $filter_callback, $post_types, $argument_value, toolset_getarr( $filter, 'filter_args', null ) );
		}


		return $this->transform_results( $post_types );
	}


	/**
	 * @param IToolset_Post_Type[] $post_types
	 *
	 * @return string[]|IToolset_Post_Type[]
	 * @throws InvalidArgumentException
	 */
	private function transform_results( $post_types ) {
		$return_type = toolset_getarr( $this->query_args, self::RETURN_TYPE, 'object', array( 'object', 'slug' ) );

		switch( $return_type ) {
			case 'object':
				return $post_types;
			case 'slug':
				$slugs = array();
				foreach( $post_types as $post_type ) {
					$slugs[] = $post_type->get_slug();
				}
				return $slugs;
			default:
				throw new InvalidArgumentException( 'Invalid return type.' );
		}

	}


	/**
	 * Filter post types by a boolean method on the post type.
	 *
	 * Name of the method is provided through the third argument.
	 *
	 * @param IToolset_Post_Type[] $post_types
	 * @param bool|null $query_value
	 * @param string $property_name
	 *
	 * @return IToolset_Post_Type[] Filtered array of post types.
	 */
	protected function filter_bool_property( $post_types, $query_value, $property_name ) {

		if( null === $query_value ) {
			return $post_types;
		}

		$results = array();

		foreach( $post_types as $post_type ) {
			if( $post_type->$property_name() === (bool) $query_value ) {
				$results[] = $post_type;
			}
		}

		return $results;
	}


	/**
	 * Filter post types by the fact whether they're on the list of excluded ones.
	 *
	 * @param IToolset_Post_Type[] $post_types
	 * @param bool|null $query_value
	 * @param $ignored
	 *
	 * @return IToolset_Post_Type[]
	 */
	protected function filter_by_exclusion( $post_types, $query_value, /** @noinspection PhpUnusedParameterInspection */ $ignored ) {

		if( null === $query_value ) {
			return $post_types;
		}

		$results = array();

		foreach( $post_types as $post_type ) {
			if( $this->exclude_list->is_excluded( $post_type->get_slug() ) === (bool) $query_value ) {
				$results[] = $post_type;
			}
		}

		return $results;
	}

}
