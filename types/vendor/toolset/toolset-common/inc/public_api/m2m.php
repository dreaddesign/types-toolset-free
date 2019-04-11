<?php

/**
 * Public-facing m2m API.
 *
 * Note: This file is included only when m2m is active, so there's no point in checking that anymore.
 */

use \OTGS\Toolset\Common\M2M as m2m;

/**
 * Query related post if many-to-many relationship functionality is enabled.
 *
 * @param int|\WP_Post $query_by_element Post to query by. All results will be posts connected to this one.
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param string $query_by_role_name Name of the element role to query by. Accepted values: 'parent'|'child'|'intermediary'
 * @param int $limit Maximum number of returned results ("posts per page").
 * @param int $offset Result offset ("page number")
 * @param array $args Additional query arguments. Accepted arguments:
 *      - meta_key, meta_value and meta_compare: Works exactly like in WP_Query. Only limited values are supported for meta_compare ('='|'LIKE').
 *      - s: Text search in the posts.
 * @param string $return Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of \WP_Post objects.
 * @param string $role_name_to_return Which posts from the relationship should be returned. Accepted values
 *     are 'parent'|'child'|'intermediary', but the value must be different from $query_by_role_name.
 *     If $query_by_role_name is 'parent' or 'child', it is also possible to pass 'other' here.
 * @param null|string $orderby Determine how the results will be ordered. Accepted values: null, 'title', 'meta_value',
 *     'meta_value_num'. If the latter two are used, there also needs to be a 'meta_key' argument in $args.
 *     Passing null means no ordering.
 * @param string $order Accepted values: 'ASC' or 'DESC'.
 * @param bool $need_found_rows Signal if the query should also determine the total number of results (disregarding pagination).
 * @param null|&int $found_rows If $need_found_rows is set to true, the total number of results will be set
 *     into the variable passed to this parameter.
 *
 * @return int[]|\WP_Post[]
 */
function toolset_get_related_posts(
	$query_by_element,
	$relationship,
	$query_by_role_name,
	$limit = 100,
	$offset = 0,
	$args = array(),
	$return = 'post_id',
	$role_name_to_return = 'other',
	$orderby = null,
	$order = 'ASC',
	$need_found_rows = false,
	&$found_rows = null
) {
	do_action( 'toolset_do_m2m_full_init' );

	// Input validation
	//
	//
	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new \InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	if( ! in_array( $query_by_role_name, \Toolset_Relationship_Role::all_role_names() ) ) {
		throw new \InvalidArgumentException( 'The role name to query by is not valid. Allowed values are: "' . implode( '", "', \Toolset_Relationship_Role::all_role_names() ) . '".' );
	}

	if(
		! in_array( $role_name_to_return, \Toolset_Relationship_Role::all_role_names() )
		&& ( 'other' !== $role_name_to_return || \Toolset_Relationship_Role::INTERMEDIARY === $query_by_role_name )
	) {
		throw new \InvalidArgumentException(
			'The role name to return is not valid. Allowed values are: "' .
			implode( '", "', \Toolset_Relationship_Role::all_role_names() ) .
			'" or "other" if $query_by_role_name is parent or child.'
		);
	}

	if( ! \Toolset_Utils::is_natural_numeric( $query_by_element ) && ! $query_by_element instanceof \WP_Post ) {
		throw new \InvalidArgumentException( 'The provided argument for a related element must be either an ID or a WP_Post object.' );
	}

	if( ! \Toolset_Utils::is_natural_numeric( $limit ) || ! \Toolset_Utils::is_nonnegative_numeric( $offset ) ) {
		throw new \InvalidArgumentException( 'Limit and offset must be non-negative integers.' );
	}

	if( ! in_array( $return , array( 'post_id', 'post_object' ) ) ) {
		throw new \InvalidArgumentException( 'The provided argument for a return type must be either "post_id" or "post_object".' );
	}

	if( 'meta_key' === $orderby && ! array_key_exists( 'meta_key', $args ) ) {
		throw new \InvalidArgumentException( 'Cannot use ordering by a meta_key if no meta_key argument is provided.' );
	}

	if( ! in_array( strtoupper( $order ), array( 'ASC', 'DESC' ) ) ) {
		throw new \InvalidArgumentException( 'Allowed order values are only ASC and DESC.' );
	}

	// Input post-processing
	//
	//
	$element_id = (int) ( $query_by_element instanceof \WP_Post ? $query_by_element->ID : $query_by_element );
	$limit = (int) $limit;
	$offset = (int) $offset;
	$query_by_role = \Toolset_Relationship_Role::role_from_name( $query_by_role_name );
	$need_found_rows = (bool) $need_found_rows;
	$search = toolset_getarr( $args, 's' );
	$has_meta_condition = ( array_key_exists( 'meta_key', $args ) && array_key_exists( 'meta_value', $args ) );

	if( 'other' === $role_name_to_return ) {
		// This will happen only if the $query_by_role not intermediary.
		/** @var \IToolset_Relationship_Role_Parent_Child $query_by_role */
		$role_to_return = $query_by_role->other();
	} else {
		$role_to_return = \Toolset_Relationship_Role::role_from_name( $role_name_to_return );
	}

	if( is_array( $relationship ) ) {
		$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $definition_repository->get_legacy_definition( $relationship[0], $relationship[1] );
		if( null === $relationship_definition ) {
			//throw new \InvalidArgumentException( 'There is no relationship between the two provided post types (no migrated one from the legacy implementation).' );
			return array();
		}
		$relationship = $relationship_definition->get_slug();
	}

	// Build the query
	//
	//
	try {
		$query = new \Toolset_Association_Query_V2();

		$query->add( $query->relationship_slug( $relationship ) )
			->add(
				$query->element_id_and_domain(
					$element_id,
					\Toolset_Element_Domain::POSTS,
					$query_by_role
				)
			)
			->limit( $limit )
			->offset( $offset )
			->order( $order )
			->need_found_rows( $need_found_rows );

		if ( ! empty( $search ) ) {
			$query->add( $query->search( $search, $role_to_return ) );
		}

		if ( $has_meta_condition ) {
			$query->add(
				$query->meta(
					toolset_getarr( $args, 'meta_key' ),
					toolset_getarr( $args, 'meta_value' ),
					\Toolset_Element_Domain::POSTS,
					$role_to_return,
					toolset_getarr( $args, 'meta_compare', \Toolset_Query_Comparison_Operator::EQUALS )
				)
			);
		}

		if ( 'post_id' === $return ) {
			$query->return_element_ids( $role_to_return );
		} else {
			$query->return_element_instances( $role_to_return );
		}

		switch ( $orderby ) {
			case 'title':
				$query->order_by_title( $role_to_return );
				break;
			case 'meta_value':
				$query->order_by_meta( toolset_getarr( $args, 'meta_key' ), \Toolset_Element_Domain::POSTS, $role_to_return );
				break;
			case 'meta_value_num':
				$query->order_by_meta( toolset_getarr( $args, 'meta_key' ), \Toolset_Element_Domain::POSTS, $role_to_return, true );
				break;
			default:
				$query->dont_order();
				break;
		}

		// Get results and post-process them
		//
		//
		$results = $query->get_results();

		if ( $need_found_rows ) {
			$found_rows = $query->get_found_rows();
		}

		if ( 'post_id' === $return ) {
			return $results;
		} else {
			$results = array_map(
				function ( $result ) {
					/** @var \IToolset_Post $result */
					return $result->get_underlying_object();
				}, $results
			);

			return $results;
		}
	} catch ( Exception $e ) {
		// This is most probably caused by an element not existing, an exception raised from the depth of
		// the association query - otherwise, there are no reasons for it to fail, all the inputs should be valid.
		return array();
	}
}


/**
 * Retrieve an ID of a single related post.
 *
 * Note: For more complex cases, use toolset_get_related_posts().
 *
 * @param WP_Post|int $post Post whose related post should be returned.
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param string $role_name_to_return Which posts from the relationship should be returned. Accepted values
 *     are 'parent' and 'child'. The relationship needs to have only one possible result in this role,
 *     otherwise an exception will be thrown.
 *
 * @return int Post ID or zero if no related post was found.
 */
function toolset_get_related_post( $post, $relationship, $role_name_to_return = 'parent' ) {

	do_action( 'toolset_do_m2m_full_init' );

	// Input validation and pre-processing
	//
	//
	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new \InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	$post = get_post( $post );

	if( ! $post instanceof WP_Post ) {
		return 0;
	}

	$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();

	if( is_array( $relationship ) ) {
		$relationship_definition = $definition_repository->get_legacy_definition( $relationship[0], $relationship[1] );
	} else {
		$relationship_definition = $definition_repository->get_definition( $relationship );
	}

	if( null === $relationship_definition ) {
		return 0;
	}

	if( $relationship_definition->get_cardinality()->get_limit( $role_name_to_return ) > Toolset_Relationship_Cardinality::ONE_ELEMENT ) {
		return 0;
	}

	if( ! in_array( $role_name_to_return, \Toolset_Relationship_Role::parent_child_role_names() ) ) {
		throw new \InvalidArgumentException(
			'The role name to return is not valid. Allowed values are: "' .
			implode( '", "', \Toolset_Relationship_Role::parent_child_role_names() ) .
			'".'
		);
	}

	/** @var IToolset_Relationship_Role_Parent_Child $role_to_return */
	$role_to_return = \Toolset_Relationship_Role::role_from_name( $role_name_to_return );

	// Query the single result
	//
	//

	try {
		$query = new Toolset_Association_Query_V2();

		$results = $query->add( $query->relationship( $relationship_definition ) )
			->add(
				$query->element_id_and_domain(
					$post->ID,
					Toolset_Element_Domain::POSTS,
					$role_to_return->other()
				)
			)
			->limit( 1 )
			->return_element_ids( $role_to_return )
			->get_results();

	} catch ( Exception $e ) {
		// This is most probably caused by an element not existing, an exception raised from the depth of
		// the association query - otherwise, there are no reasons for it to fail, all the inputs should be valid.
		return 0;
	}

	if( empty( $results ) ) {
		return 0; // No result.
	}

	$result = (int) array_pop( $results );

	return $result;
}


/**
 * Retrieve an ID of the parent post, using a legacy post relationship (migrated from the legacy implementation).
 *
 * For this to work, there needs to be a relationship between $target_type and the provided post's type.
 *
 * Note: For more complex cases, use toolset_get_related_post() or toolset_get_related_posts().
 *
 * @param WP_Post|int $post Post whose parent should be returned.
 * @param string $target_type Parent post type.
 *
 * @return int Post ID or zero if no related post was found.
 */
function toolset_get_parent_post_by_type( $post, $target_type ) {

	$post = get_post( $post );

	if( ! $post instanceof WP_Post ) {
		return 0;
	}

	return toolset_get_related_post( $post, array( $target_type, $post->post_type ) );
}


/**
 * Get extended information about a single relationship definition.
 *
 * The relationship array contains following elements:
 * array(
 *     'slug' (only if m2m is enabled) => Unique slug identifying the relationship.
 *     'labels' (only if m2m is enabled) => array(
 *         'plural' => Plural display name of the relationship.
 *         'singular' => Singular display name of the relationship.
 *     ),
 *     'roles' => array(
 *         'parent' => array(
 *             'domain' => Domain of parent elements. Currently, only 'posts' is supported.
 *             'types' => Array of (post) types involved in a single relationship. Currently, there's always
 *                 only a single post type, but that may change in the future.
 *         ),
 *         'child' => Analogic to the parent role information.
 *         'intermediary' (present only if m2m is enabled and if the relationship is of the many-to-many type)
 *             => Analogic to the parent role information. The domain is always 'posts' and there is always a single post type.
 *     ),
 *     'cardinality' => array(
 *         'type' => 'many-to-many'|'one-to-many'|'one-to-one',
 *         'limits' => array(
 *             'parent' => array(
 *                 'min' => The minimal amount of connected parent ("left side") posts for each child ("right side") post.
 *                     Currently, this is always 0, but it may change in the future.
 *                 'max' => The maximal amount of connected parent posts for each child post.
 *                     If there is no limit, it's represented by the value -1.
 *             ),
 *             'child' => Analogic to the parent role information.
 *         )
 *     ),
 *     'origin' => 'post_reference_field'|'repeatable_group'|'standard' How was the relationship created. "standard" is the standard one.
 * )
 *
 * @param string|string[] $identification Relationship slug or a pair of post type slugs identifying a legacy relationship.
 *
 * @return array|null Relationship information or null if it doesn't exist.
 */
function toolset_get_relationship( $identification ) {
	do_action( 'toolset_do_m2m_full_init' );
	$service = new m2m\PublicApiService();
	$definition = null;

	// gently handle invalid argument error and log it
	try{
		$definition = $service->get_relationship_definition( $identification );
	} catch( InvalidArgumentException $exception ){
		error_log( $exception->getMessage() );
		return null;
	}

	// gently handle case if definition is null without breaking execution
	if( ! $definition instanceof IToolset_Relationship_Definition ) {
		return null;
	}

	return $service->format_relationship_definition( $definition );
}


/**
 * Query relationships by provided arguments.
 *
 * @param array $args Query arguments. Accepted values are:
 *     - 'include_inactive': If this is true, also relationships which are deactivate or have unregistered post types will appear.
 *     - 'type_constraints': Array of constraints where each item has a role as index. Role can be 'parent', 'child', 'intermediary'
 *           or 'any' to match relationships where any of its roles fulfills the constrants.
 *           Value of the constraint is an array which may contain following elements:
 *           - 'domain': Name of the domain. Currently, only 'posts' are supported.
 *           - 'type': A single (post) type.
 *           - 'types': An array of (post) types. The constraint will be fulfilled if the relationship
 *                 has one of the provided types in the given role.
 *                 This is ignored if 'type' is provided.
 *     - 'origin': 'post_reference_field'|'repeatable_group'|'standard'|'any' How was the relationship created ("standard" is the standard one).
 *     - 'cardinality': Accepted values are 'one-to-one', 'one-to-many', 'one-to-something', 'many-to-many'
 *           or a string defining a specific cardinality: "{$parent_min}..{$parent_max}:{$child_min}..{$child_max}.
 *           Each of these values must be an integer or "*" for infinity.
 *
 * @return array Array of matching relationship definitions in the same format as in toolset_get_relationship().
 * @since 2.6.4
 */
function toolset_get_relationships( $args ) {
	if( ! is_array( $args ) ) {
		throw new InvalidArgumentException( 'Invalid input, expected an array of query arguments.' );
	}

	do_action( 'toolset_do_m2m_full_init' );

	$query = new Toolset_Relationship_Query_V2();

	if( true === (bool) toolset_getarr( $args, 'include_inactive' ) ) {
		$query->do_not_add_default_conditions();
	}

	if( array_key_exists( 'type_constraints', $args ) ) {
		$type_constraints = toolset_ensarr( toolset_getarr( $args, 'type_constraints' ) );
		foreach( $type_constraints as $role_name => $type_query ) {

			$role = ( 'any' === $role_name ? null : Toolset_Relationship_Role::role_from_name( $role_name ) );

			$domain = toolset_getarr( $type_query, 'domain', Toolset_Element_Domain::POSTS );
			if( $domain !== Toolset_Element_Domain::POSTS ) {
				throw new InvalidArgumentException( 'Invalid element domain. Only "posts" are allowed at the moment.' );
			}

			if( array_key_exists( 'type', $type_query ) ) {
				$types = array( toolset_getarr( $type_query, 'type' ) );
			} else {
				$types = toolset_ensarr( toolset_getarr( $type_query, 'types' ) );
			}
			if( empty( $types ) ) {
				continue;
			}

			if( count( $types ) === 1 ) {
				$query->add( $query->has_domain_and_type( array_pop( $types ), $domain, $role ) );
			} else {
				$query->add( $query->do_or( array_map( function( $type ) use( $domain, $role, $query ) {
					return $query->has_domain_and_type( $type, $domain, $role );
				}, $types ) ) );
			}
		}
	}

	$origin = toolset_getarr( $args, 'origin', 'standard' );
	if( 'standard' === $origin ) {
		$origin = 'wizard';
	} elseif ( 'any' === $origin ) {
		$origin = null;
	}
	$query->add( $query->origin( $origin ) );

	if( array_key_exists( 'cardinality', $args ) ) {
		$cardinality_query = toolset_getarr( $args, 'cardinality' );
		switch( $cardinality_query ) {
			case 'one-to-one':
				$cardinality = $query->cardinality()->one_to_one();
				break;
			case 'one-to-many':
				$cardinality = $query->cardinality()->one_to_many();
				break;
			case 'one-to-something':
				$cardinality = $query->cardinality()->one_to_something();
				break;
			case 'many-to-many':
				$cardinality = $query->cardinality()->many_to_many();
				break;
			default:
				$cardinality = $query->cardinality()->by_cardinality(
					Toolset_Relationship_Cardinality::from_string( $cardinality_query )
				);
				break;
		}

		$query->add( $query->has_cardinality( $cardinality ) );
	}

	$definitions = $query->get_results();

	$service = new m2m\PublicApiService();
	return array_map( function( $relationship_definition ) use( $service ) {
		return $service->format_relationship_definition( $relationship_definition );
	}, $definitions );
}


/**
 * Get post types related to the provided one.
 *
 * @param string $return_role Role that the results have in a relationship.
 * @param string $for_post_type Post type slug in the opposite role.
 *
 * @return string[][] An associative array where each post type has one key, and its value
 *     is an array of relationship slugs (in m2m) /post type pairs (in legacy implementation)
 *     that have matched the query.
 *
 * For example, if there is a relationship "appointment" between "doctor" and "patient" post types,
 * toolset_get_related_post_types( 'parent', 'patient' ) will return:
 * array( 'doctor' => array( 'appointment' ) )
 *
 * @since 2.6.4
 */
function toolset_get_related_post_types( $return_role, $for_post_type ) {
	do_action( 'toolset_do_m2m_full_init' );

	$role = Toolset_Relationship_Role::role_from_name( $return_role );
	if( ! $role instanceof IToolset_Relationship_Role_Parent_Child ) {
		throw new InvalidArgumentException( 'Invalid role value. Accepted values are "parent" and "child".' );
	}

	$query = new Toolset_Relationship_Query_V2();
	$relationships = $query->add(
		$query->has_domain_and_type( $for_post_type, Toolset_Element_Domain::POSTS, $role->other() )
	)->get_results();

	$results = array();

	foreach( $relationships as $relationship ) {
		$post_types = $relationship->get_element_type( $return_role )->get_types();
		foreach( $post_types as $post_type ) {
			if( ! array_key_exists( $post_type, $results ) ) {
				$results[ $post_type ] = array();
			}
			$results[ $post_type ][] = $relationship->get_slug();
		}
	}

	return $results;
}

/**
 * Will collect all associations of the $child_id and return as a array of relationships and associations
 * These list can be used to export associations as postmeta.
 *
 * @param $child_id
 *
 * @return false|array
 *      false if child_id could not be found
 *      array empty if no associations there
 *
 *      example of response with associations (meta_key => meta_value)
 *      '_toolset_associations_%relationship_1_slug%' => "%association_1_parent_guid% + %association_1_intermediary_guid%, %association_2_parent_guid% + %association_2_intermediary_guid%, ..."
 *      '_toolset_associations_%relationship_2_slug%' => "%association_1_parent_guid%, %association_2_parent_guid%, ..."
 *      '_toolset_associations_%relationship_3_slug%' => "%association_1_parent_guid%, %association_2_parent_guid%, ..."
 *
 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
 */
function toolset_export_associations_of_child( $child_id ) {
	if ( ! $child_post = get_post( $child_id ) ) {
		return false;
	}

	do_action( 'toolset_do_m2m_full_init' );

	/** @var Toolset_Element_Factory $toolset_element_factory */
	$toolset_element_factory = Toolset_Singleton_Factory::get( 'Toolset_Element_Factory' );

	try {
		$child_element = $toolset_element_factory->get_post( $child_post );
	} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
		// element could not be found
		return false;
	}

	/** @var \OTGS\Toolset\Common\M2M\Association\Repository $association_repository */
	$association_repository = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Common\M2M\Association\Repository',
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Query_Factory' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Parent' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Child' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Intermediary' ),
		Toolset_Singleton_Factory::get( 'Toolset_Element_Domain' )
	);

	$association_repository->addAssociationsByChild( $child_element );

	/** @var \OTGS\Toolset\Types\Post\Export\Associations $export_associations */
	$export_associations = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Export\Associations',
		$association_repository,
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Meta\Associations' )
	);

	return $export_associations->getExportArray( $child_element );
}


/**
 * Will search for associations in meta of $child_id and import them.
 * To make sure the data is correctly formated use toolset_export_associations_of_child to export data.
 *
 * @param $child_id
 *
 * @return array|false
 * 		false if child_id could not be found
 *      'success' => array of succesfully imported associations
 *      'error' => array of associations which could not be imported
 *
 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
 */
function toolset_import_associations_of_child( $child_id ) {
	if ( ! $child_post = get_post( $child_id ) ) {
		return false;
	}

	do_action( 'toolset_do_m2m_full_init' );
	global $wpdb;

	$association_repository = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Common\M2M\Association\Repository',
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Query_Factory' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Parent' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Child' ),
		Toolset_Singleton_Factory::get( 'Toolset_Relationship_Role_Intermediary' ),
		Toolset_Singleton_Factory::get( 'Toolset_Element_Domain' )
	);

	/** @var \OTGS\Toolset\Types\Post\Import\Associations $import_associations */
	$import_associations = Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Import\Associations',
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Meta\Associations' ),
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Wordpress\Post\Storage', $wpdb ),
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Wordpress\Postmeta\Storage', $wpdb ),
		Toolset_Relationship_Definition_Repository::get_instance(),
		$association_repository,
		Toolset_Singleton_Factory::get( '\OTGS\Toolset\Types\Post\Import\Association\Factory' )
	);

	// as we use singeleton factory, make sure we start with a clean set of associations
	$import_associations->resetAssociations();

	// load all associations by child
	$import_associations->loadAssociationsByChildPost( $child_post );

	return $import_associations->importAssociations( true, false );
}


/**
 * Connect two posts in a given relationship.
 *
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param int|WP_Post $parent Parent post to connect.
 * @param int|WP_Post $child Child post to connect.
 *
 * @return array
 *		- (bool) 'success': Always present.
 *      - (string) 'message': A message describing the result. May not be always present.
 *      - (int) 'intermediary_post': Present only if the operation has succeeded. ID of the newly created intermediary post
 *          or zero if there is none.
 *
 * @since 2.7
 */
function toolset_connect_posts( $relationship, $parent, $child ) {

	do_action( 'toolset_do_m2m_full_init' );

	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $parent ) && ! $parent instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The parent must be a post ID or a WP_Post instance.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $child ) && ! $child instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The child must be a post ID or a WP_Post instance.' );
	}

	if( is_array( $relationship ) && count( $relationship ) === 2 ) {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_legacy_definition( $relationship[0], $relationship[1] );
	} else {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_definition( $relationship );
	}

	if( null === $relationship_definition ) {
		return array(
			'success' => false,
			'message' => "The relationship doesn't exist."
		);
	}

	$result = $relationship_definition->create_association( $parent, $child );
	if( $result instanceof Toolset_Result ) {
		return array(
			'success' => false,
			'message' => $result->get_message()
		);
	}

	return array(
		'success' => true,
		'intermediary_post' => $result->get_intermediary_id()
	);
}


/**
 * Disconnect two posts in a given relationship.
 *
 * Note: When we introduce non-distinct relationships in the future, the behaviour of this function might change for them.
 * Keep that in mind.
 *
 * @param string|string[] $relationship Slug of the relationship to query by or an array with the parent and the child post type.
 *     The array variant can be used only to identify relationships that have been migrated from the legacy implementation.
 * @param int|WP_Post $parent Parent post to connect.
 * @param int|WP_Post $child Child post to connect.
 *
 * @return array
 *		- (bool) 'success': Always present.
 *      - (string) 'message': A message describing the result. May not be always present.
 *
 * @since 2.7
 */
function toolset_disconnect_posts( $relationship, $parent, $child ) {

	do_action( 'toolset_do_m2m_full_init' );

	if( ! is_string( $relationship ) && ! ( is_array( $relationship ) && count( $relationship ) === 2 ) ) {
		throw new InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $parent ) && ! $parent instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The parent must be a post ID or a WP_Post instance.' );
	}

	if( ! Toolset_Utils::is_natural_numeric( $child ) && ! $child instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The child must be a post ID or a WP_Post instance.' );
	}

	if( is_array( $relationship ) && count( $relationship ) === 2 ) {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_legacy_definition( $relationship[0], $relationship[1] );
	} else {
		$relationship_definition = Toolset_Relationship_Definition_Repository::get_instance()->get_definition( $relationship );
	}

	if( null === $relationship_definition ) {
		return array(
			'success' => false,
			'message' => "The relationship doesn't exist."
		);
	}

	$query = new Toolset_Association_Query_V2();
	$results = $query->add( $query->relationship( $relationship_definition ) )
		->add( $query->parent_id( $parent ) )
		->add( $query->child_id( $child ) )
		->limit( 1 )
		->do_not_add_default_conditions()
		->return_association_instances()
		->get_results();

	if( empty( $results ) ) {
		return array(
			'success' => false,
			'message' => __( 'There is no association between the two given posts that can be deleted', 'wpcf' )
		);
	}

	$association = array_pop( $results );

	$association_persistence = new Toolset_Association_Persistence();
	$result = $association_persistence->delete_association( $association );

	if( $result->is_error() ) {
		return array(
			'success' => false,
			'message' => $result->get_message(),
		);
	}

	return array(
		'success' => true
	);
}