<?php
/**
 * Public-facing legacy post relationship API.
 *
 * Note: This file is included only when m2m is *not* active, so there's no point in checking that anymore.
 */

/**
 * Query related post if many-to-many relationship functionality is NOT enabled.
 *
 * @param int|\WP_Post $query_by_element Post to query by. All results will be posts connected to this one.
 * @param string[] $relationship Array containing parent post type and child post type.
 * @param string $query_by_role_name Name of the element role to query by. Accepted values: 'parent'|'child'
 * @param int $limit Maximum number of returned results ("posts per page").
 * @param int $offset Result offset ("page number")
 * @param array $args Additional query arguments. Accepted arguments:
 *      - meta_key, meta_value and meta_compare: Works exactly like in WP_Query. Only limited values are supported for meta_compare ('='|'LIKE').
 *      - s: Text search in the posts.
 * @param string $return Determines return type. 'post_id' for array of post IDs, 'post_object' for an array of \WP_Post objects.
 * @param string $role_name_to_return Which posts from the relationship should be returned. Accepted values
 *     are 'parent'|'child', but the value must be different from $query_by_role_name.
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
 * @throws InvalidArgumentException In case of error.
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
	// Input validation
	//
	//
	if( ! is_array( $relationship ) || count( $relationship ) !== 2 ) {
		throw new InvalidArgumentException( 'The relationship must be an array containing parent post type and child post type.' );
	}
	if ( ! get_post_type_object( $relationship[0] ) ||  ! get_post_type_object( $relationship[1] ) ) {
		throw new InvalidArgumentException( 'Invalid relationship post type, please make sure they are valid post types.' );
	}
	if ( ! in_array( $query_by_role_name, array( 'parent', 'child', 'intermediary' ) ) ) {
		throw new InvalidArgumentException( 'The role name to query by is not valid. Allowed values are: "parent", "child"' );
	}
	if( ! Toolset_Utils::is_natural_numeric( $query_by_element ) && ! $query_by_element instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The provided argument for a related element must be either an ID or a WP_Post object.' );
	}
	if(
		! in_array( $role_name_to_return, array( 'parent', 'child', 'intermediary' ) )
		&& ( 'other' !== $role_name_to_return || 'intermediary' === $query_by_role_name )
	) {
		throw new InvalidArgumentException(
			'The role name to return is not valid. Allowed values are: "parent", "child" or "other" if $query_by_role_name is parent or child.'
		);
	}

	if( ! \Toolset_Utils::is_natural_numeric( $limit ) || ! \Toolset_Utils::is_nonnegative_numeric( $offset ) ) {
		throw new InvalidArgumentException( 'Limit and offset must be non-negative integers.' );
	}

	if( ! in_array( $return , array( 'post_id', 'post_object' ) ) ) {
		throw new InvalidArgumentException( 'The provided argument for a return type must be either "post_id" or "post_object".' );
	}

	if( 'meta_key' === $orderby && ! array_key_exists( 'meta_key', $args ) ) {
		throw new InvalidArgumentException( 'Cannot use ordering by a meta_key if no meta_key argument is provided.' );
	}

	if( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
		throw new InvalidArgumentException( 'Allowed order values are only ASC and DESC.' );
	}

	$legacy_relationships = toolset_ensarr( get_option( 'wpcf_post_relationship', array() ) );

	if(
		! array_key_exists( $relationship[0], $legacy_relationships )
		|| ! is_array( $legacy_relationships[ $relationship[0] ] )
		|| ! array_key_exists( $relationship[1], $legacy_relationships[ $relationship[0] ] )
		|| ! is_array( $legacy_relationships[ $relationship[0] ][ $relationship[1] ] )
	) {
		return array();
	}

	// For now legacy relationships migration doesn't allow intermediary types and many-to-many, so this is droped from the code
	/*
	// Checks if it queries by intermediary post type and it is really an intermediary post type.
	if ( 'intermediary' == $query_by_element ) {
		// Number of ocurrences.
		$ocurrences = array();
		foreach ( $legacy_relationships as $_post_type => $_children ) {
			if ( $query_by_element === $_post_type ) {
				throw new InvalidArgumentException( 'Wrong intermediary post type, it should only be used as a child.' );
			}
			foreach ( array_keys( $_children) as $_child ) {
				if ( ! isset( $ocurrences[ $_child ] ) ) {
					$ocurrences[ $_child ] = 0;
				}
				$ocurrences[ $_child ]++;
			}
		}
		if ( 2 !== $ocurrences[ $query_by_element ] ) {
			throw new InvalidArgumentException( 'Wrong intermediary post type, it should be connected only to two post types.' );
		}
	}
	*/

	// Order by
	$order_by = '';
	if ( $orderby ) {
		switch ( $orderby ) {
			case 'title':
				$order_by = " ORDER BY posts.post_title {$order} ";
				break;
			case 'meta_value':
				$order_by = " ORDER BY mt.meta_value {$order} ";
				break;
			case 'meta_value_num':
				$order_by = " ORDER BY CAST(mt.meta_value AS SIGNED) {$order} ";
				break;
		}
	}

//	$is_many_to_many = isset( $legacy_relationships[ $relationship[0] ] )
//		&& isset( $legacy_relationships[ $relationship[1] ] )
//		&& ! empty ( array_intersect_key( $legacy_relationships[ $relationship[0] ], $legacy_relationships[ $relationship[1] ] ) );

	global $wpdb;

	$where = array( '1=1');
	$joins = array();
	$from = " FROM {$wpdb->posts} posts ";

	$query_by_post_id = $query_by_element instanceof WP_Post
		? $query_by_element->ID
		: $query_by_element;

	if ( 'other' === $role_name_to_return ) {
		$post_type = 'parent' === $query_by_role_name
			? $relationship[1]
			: $relationship[0];
	} else {
		$post_type = 'parent' === $role_name_to_return
			? $relationship[0]
			: $relationship[1];
	}

	// Joins. get_posts can't be used because some times wp_postmeta.post_id is needed and in other cases meta_value,
	// or joining to wp_postmeta records is required.

	// If querying by intermediary, it always return its parent.
	// For now legacy relationships migration doesn't allow intermediary types and many-to-many, so this is droped from the code
	/*
	if ( 'intermediary' === $query_by_role_name ) {
		$joins[] = $wpdb->prepare( "JOIN {$wpdb->postmeta} belongs ON
			belongs.meta_key = %s AND
			belongs.post_id = %d AND
			belongs.meta_value = posts.ID", "_wpcf_belongs_{$post_type}_id", $query_by_post_id );
	} else {
		if ( $is_many_to_many ) {
			$other_post_type = $relationship[0] === $post_type
				? $relationship[1]
				: $relationship[0];
			$joins[] = $wpdb->prepare( "JOIN wp_postmeta belongs_intermediary ON
					belongs_intermediary.meta_key = %s AND
					belongs_intermediary.meta_value = %d
				JOIN wp_postmeta belongs_parent ON
					belongs_parent.meta_key = %s AND
					belongs_parent.post_id = belongs_intermediary.post_id AND
					belongs_parent.meta_value = posts.ID", "_wpcf_belongs_{$other_post_type}_id", $query_by_post_id, "_wpcf_belongs_{$post_type}_id" );
		} else {
	*/
			$actual_role_name = $query_by_role_name;
			if ( 'other' !== $query_by_role_name ) {
				$actual_role_name =  $relationship[0] === $post_type
					? 'child'
					: 'parent';
			}
			if ( 'parent' === $actual_role_name ) {
				$joins[] = $wpdb->prepare( "JOIN {$wpdb->postmeta} belongs ON
					belongs.meta_key = %s AND
					belongs.meta_value = %d AND
					belongs.post_id = posts.ID", "_wpcf_belongs_{$relationship[0]}_id", $query_by_post_id );
			} else if ( 'child' === $actual_role_name ) {
				$joins[] = $wpdb->prepare( "JOIN {$wpdb->postmeta} belongs ON
					belongs.meta_key = %s AND
					belongs.post_id = %d AND
					belongs.meta_value = posts.ID", "_wpcf_belongs_{$relationship[0]}_id", $query_by_post_id );
			}
	/* Eventually dropped
		}
	}
	*/

	// Args (meta_key, meta_value and meta_compare) or searching
	if ( ! empty( $args['meta_key'] ) && ! empty( $args['meta_value'] ) ) {
		$meta_query_args = array(
			array(
				'key'     => $args['meta_key'],
				'value'   => $args['meta_value'],
				'compare' => $args['meta_compare'] ? $args['meta_compare'] : '=',
			),
		);
		$meta_query = new WP_Meta_Query( $meta_query_args );
		$mq_sql = $meta_query->get_sql(
			'post',
			'posts',
			'ID'
		);
		if ( isset( $mq_sql['join'] ) ) {
			$joins[] = str_replace(
				array( $wpdb->postmeta . '.', $wpdb->postmeta ),
				array( 'mt.', $wpdb->postmeta . ' mt' ),
				$mq_sql['join']
			);
		}
		if ( isset( $mq_sql['where'] ) ) {
			$where[] = str_replace( $wpdb->postmeta . '.', 'mt.', $mq_sql['where'] );
		}
	}

	// Search
	if ( ! empty( $args['s'] ) ) {
		$like = '%' . $args['s'] . '%';
		$where[] = $wpdb->prepare( " AND ( posts.post_content like %s OR posts.post_title like %s ) ", $like, $like );
	}

	$joins = implode( "\n", $joins );
	$where = implode( "\n", $where );
	$query_fields = 'posts.ID';
	$found_rows = $need_found_rows ? ' SQL_CALC_FOUND_ROWS ' : '';

	$sql = "SELECT {$found_rows} {$query_fields} {$from}
		{$joins}
		WHERE {$where}
		{$order_by}
		LIMIT {$offset}, {$limit}";

	$posts = $wpdb->get_results( $sql );

	if ( $need_found_rows ) {
		$found_rows = $wpdb->num_rows;
	}

	$result = array();
	foreach ( $posts as $post ) {
		$result[] = 'post_id' === $return
			? (int) $post->ID
			: get_post( $post->ID );
	}

	return $result;
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
	// Input validation
	//
	//
	if( ! Toolset_Utils::is_natural_numeric( $post ) && ! $post instanceof WP_Post ) {
		throw new InvalidArgumentException( 'The provided argument for a related element must be either an ID or a WP_Post object.' );
	}
	if( ! is_array( $relationship ) || count( $relationship ) !== 2 ) {
		throw new InvalidArgumentException( 'The relationship must be an array containing parent post type and child post type.' );
	}
	if ( ! get_post_type_object( $relationship[0] ) ||  ! get_post_type_object( $relationship[1] ) ) {
		throw new InvalidArgumentException( 'Invalid relationship post type, please make sure they are valid post types.' );
	}
	if( ! in_array( $role_name_to_return, array( 'parent', 'child' ) ) ) {
		throw new InvalidArgumentException( 'The role name to return is not valid. Allowed values are: "parent", "child".' );
	}
	$query_by_role_name = 'parent' === $role_name_to_return
		? 'child'
		: 'parent';
	$result = toolset_get_related_posts(
		$post,
		$relationship,
		$query_by_role_name,
		1,
		0,
		array(),
		'post_id',
		$role_name_to_return
	);
	return isset ( $result[0] ) ? $result[0] : 0;
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
 * Please refer to toolset_get_relationship() documentation in inc/public_api/m2m.php.
 *
 * @param string|string[] $identification
 * @return array|null
 * @since 2.6.4
 */
function toolset_get_relationship( $identification ) {

	if( ! is_array( $identification ) || count( $identification ) !== 2 ) {
		throw new InvalidArgumentException( 'The relationship must be an array containing parent post type and child post type.' );
	}

	// Just check if the relationship is defined.
	$legacy_relationships = toolset_ensarr( get_option( 'wpcf_post_relationship', array() ) );
	if(
		! array_key_exists( $identification[0], $legacy_relationships )
		|| ! is_array( $legacy_relationships[ $identification[0] ] )
		|| ! array_key_exists( $identification[1], $legacy_relationships[ $identification[0] ] )
		|| ! is_array( $legacy_relationships[ $identification[0] ][ $identification[1] ] )
	) {
		return null;
	}

	// The only varying part are the two post types.
	$relationship = array(
		'roles' => array(
			'parent' => array(
				'domain' => 'posts',
				'types' => array( $identification[0] )
			),
			'child' => array(
				'domain' => 'posts',
				'types' => array( $identification[1] )
			)
		),
		'cardinality' => array(
			'type' => 'one-to-many',
			'limits' => array(
				'parent' => array(
					'max' => 1,
					'min' => 0
				),
				'child' => array(
					'max' => -1,
					'min' => 0
				)
			)
		),
		'origin' => 'standard'
	);

	return $relationship;
}


/**
 * Please refer to toolset_get_relationships() documentation in inc/public_api/m2m.php.
 *
 * @param array $args
 * @return array
 * @since 2.6.4
 */
function toolset_get_relationships( $args ) {
	if ( ! is_array( $args ) ) {
		throw new InvalidArgumentException( 'Invalid input, expected an array of query arguments.' );
	}

	// Transform the legacy relationship data into pairs of post types.
	//
	//
	$legacy_relationships_option = toolset_ensarr( get_option( 'wpcf_post_relationship', array() ) );
	$legacy_relationships = array();
	foreach( $legacy_relationships_option as $parent_type => $child_types ) {
		foreach( array_keys( $child_types ) as $child_type ) {
			$legacy_relationships[] = array(
				'parent' => $parent_type,
				'child' => $child_type
			);
		}
	}


	// Filter out relationships that have an inactive post type.
	//
	//
	if( false === (bool) toolset_getarr( $args, 'include_inactive' ) ) {
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();
		$is_post_type_active = function( $post_type_slug ) use( $post_type_repository ) {
			$post_type = $post_type_repository->get( $post_type_slug );
			if( null === $post_type ) {
				return false;
			}
			return $post_type->is_registered();
		};

		$legacy_relationships = array_filter(
			$legacy_relationships,
			function( $relationship ) use ( $is_post_type_active ) {
				return ( $is_post_type_active( $relationship['parent'] ) && $is_post_type_active( $relationship['child'] ) );
			} );
	}


	// Filter out arguments that have only a single supported value in the legacy implementation.
	//
	//
	if( ! in_array( toolset_getarr( $args, 'origin', 'standard' ), array( 'standard', 'any' ) ) ) {
		return array();
	}
	if( ! in_array( toolset_getarr( $args, 'cardinality', 'one-to-many' ), array( 'one-to-many', 'one-to-something', '0..1:0..*' ) ) ) {
		return array();
	}


	// Filter relationships by involved post types.
	//
	$type_constraints = toolset_ensarr( toolset_getarr( $args, 'type_constraints' ) );
	foreach( $type_constraints as $role => $type_constraint ) {
		if( ! in_array( $role, array( 'parent', 'child', 'any' ) ) ) {
			return array();
		}

		$domain = toolset_getarr( $type_constraint, 'domain', Toolset_Element_Domain::POSTS );
		if( Toolset_Element_Domain::POSTS !== $domain ) {
			return array();
		}

		if( array_key_exists( 'type', $type_constraint ) ) {
			$types = array( toolset_getarr( $type_constraint, 'type' ) );
		} else {
			$types = toolset_ensarr( toolset_getarr( $type_constraint, 'types' ) );
		}
		if( empty( $types ) ) {
			continue;
		}

		$legacy_relationships = array_filter( $legacy_relationships, function( $relationship ) use( $role, $types ) {
			switch( $role ) {
				case 'any':
					return ( in_array( $relationship['parent'], $types ) || in_array( $relationship['child'], $types ) );
				default:
					return in_array( $relationship[ $role ], $types );
			}
		});
	}

	// Format results.
	return array_map( function( $relationship_identification ) {
		return toolset_get_relationship( array( $relationship_identification['parent'], $relationship_identification['child'] ) );
	}, $legacy_relationships );
}


/**
 * Please refer to toolset_get_related_post_types() documentation in inc/public_api/m2m.php.
 *
 * @param string $return_role
 * @param string $for_post_type
 * @return array
 * @since 2.6.4
 */
function toolset_get_related_post_types( $return_role, $for_post_type ) {

	if( ! in_array( $return_role, array( 'parent', 'child' ) ) ) {
		throw new InvalidArgumentException( 'Invalid role value. Accepted values are "parent" and "child".' );
	}

	$other_role = ( $return_role === 'parent' ? 'child' : 'parent' );

	$relationships = toolset_get_relationships(
		array(
			'type_constraints' => array(
				$other_role => array(
					'type' => $for_post_type
				)
			)
		)
	);

	$results = array();
	foreach( $relationships as $relationship ) {
		$result_post_type = $relationship['roles'][ $return_role ]['types'][0];
		if( ! array_key_exists( $result_post_type, $results ) ) {
			$results[ $result_post_type ] = array();
		}
		$results[ $result_post_type ][] = array(
			$relationship['roles']['parent']['types'][0],
			$relationship['roles']['child']['types'][0]
		);
	}

	return $results;
}