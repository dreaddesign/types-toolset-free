<?php

/**
 * Relationship query class with a more OOP/functional approach.
 *
 * Replaces Toolset_Relationship_Query.
 *
 * Allows for chaining query conditions and avoiding passing query arguments as associative arrays.
 * It makes it also possible to build queries with nested AND & OR statements in an arbitrary way.
 * The object model may be complex but all the complexity is hidden from the user, they need to know
 * only the methods on this class.
 *
 * Example usage:
 *
 * $query = new Toolset_Relationship_Query_V2();
 *
 * $results = $query
 *     ->add(
 *         $query->has_domain( 'posts' )
 *     )
 *     ->add(
 *         $query->do_or(
 *             $query->has_type( 'attachment', new Toolset_Relationship_Role_Parent() ),
 *             $query->do_and(
 *                 $query->has_type( 'page', new Toolset_Relationship_Role_Parent() ),
 *                 $query->is_legacy( false )
 *             )
 *         )
 *     )
 *     ->add( $query->is_active( '*' ) )
 *     ->get_results();
 *
 * Note:
 * - If no is_active() condition is used when constructing the query, is_active(true) is used. To get both
 *     active and non-active relationship definitions, you need to manually add is_active('*').
 * - If no has_active_post_types() condition is used when constructing the query, has_active_post_types(true)
 *     is used for both parent and child role.
 * - If no origin() condition is used, origin( 'wizard' ) is added by default.
 * - This mechanism doesn't recognize where, how and if these conditions are actually applied, so even
 *     $query->do_if( false, $query->is_active( true ) ) will disable the default is_active() condition.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_V2 {


	/** @var IToolset_Relationship_Query_Condition[] */
	private $conditions = array();

	/** @var wpdb */
	private $wpdb;

	/** @var Toolset_Relationship_Definition_Translator */
	private $definition_translator;


	private $should_add_default_conditions = true;
	private $has_is_active_condition = false;
	private $has_is_post_type_active_condition = false;
	private $has_origin_condition = false;


	/** @var Toolset_Relationship_Database_Unique_Table_Alias */
	private $unique_table_alias;


	/** @var Toolset_Relationship_Query_Sql_Expression_Builder */
	private $expression_builder;


	/** @var Toolset_Relationship_Query_Condition_Factory */
	private $condition_factory;


	/** @var null|Toolset_Relationship_Query_Cardinality_Match_Factory */
	private $_cardinality_match_factory;


	/** @var bool Remember whether get_results() was called. */
	private $was_used = false;


	/**
	 * @var bool Determines whether the SQL query should also calculate found rows.
	 * @since 2.5.8
	 */
	private $need_found_rows = false;


	/**
	 * @var int|null Number of total found rows that will be set in get_results() if there was a request
	 *     to calculate these, null otherwise.
	 * @since 2.5.8
	 */
	private $found_rows;


	/**
	 * Toolset_Relationship_Query_V2 constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Definition_Translator|null $definition_translator_di
	 * @param Toolset_Relationship_Database_Unique_Table_Alias|null $unique_table_alias_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Relationship_Query_Sql_Expression_Builder|null $expression_builder_di
	 * @param Toolset_Relationship_Query_Condition_Factory|null $condition_factory_di
	 * @param Toolset_Relationship_Query_Cardinality_Match_Factory|null $cardinality_match_factory_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Definition_Translator $definition_translator_di = null,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Query_Sql_Expression_Builder $expression_builder_di = null,
		Toolset_Relationship_Query_Condition_Factory $condition_factory_di = null,
		Toolset_Relationship_Query_Cardinality_Match_Factory $cardinality_match_factory_di = null
	) {

		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->definition_translator = (
			null === $definition_translator_di
				? new Toolset_Relationship_Definition_Translator()
				: $definition_translator_di
		);

		$this->unique_table_alias = (
			null === $unique_table_alias_di
				? new Toolset_Relationship_Database_Unique_Table_Alias()
				: $unique_table_alias_di
		);

		$this->expression_builder = (
			null === $expression_builder_di
				? new Toolset_Relationship_Query_Sql_Expression_Builder()
				: $expression_builder_di
		);

		$this->condition_factory = (
			null === $condition_factory_di
				? new Toolset_Relationship_Query_Condition_Factory()
				: $condition_factory_di
		);

		$this->_cardinality_match_factory = $cardinality_match_factory_di;
	}


	/**
	 * Add another condition to the query.
	 *
	 * @param IToolset_Relationship_Query_Condition $condition
	 *
	 * @return $this
	 */
	public function add( IToolset_Relationship_Query_Condition $condition ) {
		$this->conditions[] = $condition;
		return $this;
	}


	/**
	 * Basically, this sets default query parameters.
	 *
	 * The method needs to stay idempotent.
	 */
	private function add_default_conditions() {
		if( ! $this->should_add_default_conditions ) {
			return;
		}

		if( ! $this->has_is_active_condition ) {
			$this->add( $this->is_active() );
		}

		if( ! $this->has_is_post_type_active_condition ) {
			$this->add( $this->has_active_post_types() );
		}

		if( ! $this->has_origin_condition ) {
			$this->add( $this->origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ) );
		}
	}


	/**
	 * @return IToolset_Relationship_Query_Condition MySQL WHERE clause for the query.
	 */
	private function build_root_condition() {
		$this->add_default_conditions();

		if( empty( $this->conditions ) ) {
			return $this->condition_factory->tautology();
		}

		return $this->condition_factory->do_and( $this->conditions );
	}


	/**
	 * @return $this
	 */
	public function do_not_add_default_conditions() {
		$this->should_add_default_conditions = false;
		return $this;
	}


	/**
	 * Apply stored conditions and perform the query.
	 *
	 * todo: Add the results to the relationship repository.
	 *
	 * @return IToolset_Relationship_Definition[]
	 */
	public function get_results() {

		if( $this->was_used ) {
			_doing_it_wrong(
				__FUNCTION__,
				'The relationship query object should not be reused. Create a new instance if you need to run another query.',
				TOOLSET_COMMON_VERSION
			);
		}

		$this->was_used = true;

		$query = $this->build_sql_query();
		$rows = toolset_ensarr( $this->wpdb->get_results( $query ) );

		if( $this->need_found_rows ) {
			$this->found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}

		$results = array();
		foreach( $rows as $row ) {
			$definition = $this->definition_translator->from_database_row( $row );
			$results[] = $definition;
			/*if( null != $definition ) {
				$this->insert_definition( $definition );
			}*/
		}

		return $results;
	}


	/**
	 * Build a complete MySQL query from the conditions.
	 *
	 * @return string
	 */
	private function build_sql_query() {
		$root_condition = $this->build_root_condition();
		return $this->expression_builder->build( $root_condition, $this->need_found_rows );
	}


	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param IToolset_Relationship_Query_Condition[] [$condition1, $condition2, ...]
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function do_or() {
		return $this->condition_factory->do_or( func_get_args() );
	}


	/**
	 * Chain multiple conditions with AND.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param IToolset_Relationship_Query_Condition[] [$condition1, $condition2, ...]
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function do_and() {
		return $this->condition_factory->do_and( func_get_args() );
	}


	/**
	 * Condition that the relationship involves a certain domain.
	 *
	 * @param string $domain_name One of the Toolset_Element_Domain values.
	 * @param IToolset_Relationship_Role_Parent_Child|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_domain( $domain_name, $in_role = null ) {
		if( null === $in_role ) {
			return $this->do_or(
				$this->has_domain( $domain_name, new Toolset_Relationship_Role_Parent() ),
				$this->has_domain( $domain_name, new Toolset_Relationship_Role_Child() )
			);
		}

		return $this->condition_factory->has_domain( $domain_name, $in_role );
	}


	/**
	 * Condition that the relationship comes from a certain source
	 *
	 * @param string|null $origin One of the keywords from IToolset_Relationship_Origin or null to include relationships with all origins.
	 *
	 * @return Toolset_Relationship_Query_Condition_Origin
	 */
	public function origin( $origin ) {
		$this->has_origin_condition = true;
		return $this->condition_factory->origin( $origin );
	}


	/**
	 * Condition that the relationship includes a certain intermediary object.
	 *
	 * @param string $intermediary_type An intermediary object slug.
	 *
	 * @return Toolset_Relationship_Query_Condition_Intermediary
	 *
	 * @since 2.6.7
	 */
	public function intermediary_type( $intermediary_type ) {
		return $this->condition_factory->intermediary_type( $intermediary_type );
	}


	/**
	 * Condition that the relationship has a certain type in a given role.
	 *
	 * @param string $type
	 * @param IToolset_Relationship_Role_Parent_Child|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_type( $type, $in_role = null ) {
		if( null === $in_role ) {
			return $this->do_or(
				$this->has_type( $type, new Toolset_Relationship_Role_Parent() ),
				$this->has_type( $type, new Toolset_Relationship_Role_Child() )
			);
		}

		return $this->condition_factory->has_type( $type, $in_role );
	}


	/**
	 * Condition that the relationship has a certain type in a given role.
	 *
	 * @param string $type
	 * @param IToolset_Relationship_Role_Parent_Child|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function exclude_type( $type, $in_role = null ) {
		if( null === $in_role ) {
			return $this->do_and(
				$this->exclude_type( $type, new Toolset_Relationship_Role_Parent() ),
				$this->exclude_type( $type, new Toolset_Relationship_Role_Child() )
			);
		}

		return $this->condition_factory->exclude_type( $type, $in_role );
	}


	/**
	 * Condition that the relationship has a certain type and a domain in a given role.
	 *
	 * @param string $type
	 * @param string $domain One of the Toolset_Element_Domain values.
	 * @param IToolset_Relationship_Role_Parent_Child|null $in_role If null is provided, the type
	 *    can be in both parent or child role for the condition to be true.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 * @since 2.5.6
	 */
	public function has_domain_and_type( $type, $domain, $in_role = null ) {
		if( null === $in_role ) {
			return $this->do_or(
				$this->has_domain_and_type( $type, $domain, new Toolset_Relationship_Role_Parent() ),
				$this->has_domain_and_type( $type, $domain, new Toolset_Relationship_Role_Child() )
			);
		}

		return $this->do_and(
			$this->condition_factory->has_domain( $domain, $in_role ),
			$this->condition_factory->has_type( $type, $in_role )
		);
	}


	/**
	 * Condition that the relationship was migrated from the legacy implementation.
	 *
	 * @param bool $should_be_legacy
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function is_legacy( $should_be_legacy = true ) {
		return $this->condition_factory->is_legacy( $should_be_legacy );
	}


	/**
	 * Condition that the relationship is active.
	 *
	 * @param bool $should_be_active
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function is_active( $should_be_active = true ) {
		$this->has_is_active_condition = true;
		return $this->condition_factory->is_active( $should_be_active );
	}


	/**
	 * Condition that the relationship has at least one active post type in a given role (or another domain than posts).
	 *
	 * @param bool $has_active_post_types
	 * @param IToolset_Relationship_Role_Parent_Child|null $in_role
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_active_post_types( $has_active_post_types = true, IToolset_Relationship_Role_Parent_Child $in_role = null ) {
		if( null === $in_role ) {
			return $this->do_and(
				$this->has_active_post_types( $has_active_post_types, new Toolset_Relationship_Role_Parent() ),
				$this->has_active_post_types( $has_active_post_types, new Toolset_Relationship_Role_Child() )
			);
		}

		$this->has_is_post_type_active_condition = true;
		return $this->condition_factory->has_active_post_types( $has_active_post_types, $in_role );
	}


	/**
	 * Get a factory of cardinality constrains, which can be used as an argument for $this->has_cardinality().
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Factory
	 */
	public function cardinality() {
		if( null === $this->_cardinality_match_factory ) {
			$this->_cardinality_match_factory = new Toolset_Relationship_Query_Cardinality_Match_Factory();
		}

		return $this->_cardinality_match_factory;
	}


	/**
	 * Condition that a relationship has a certain cardinality.
	 *
	 * Use methods on $this->cardinality() to obtain a valid argument for this method.
	 *
	 * @param IToolset_Relationship_Query_Cardinality_Match $cardinality_match Object
	 *     that holds cardinality constraints.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_cardinality( IToolset_Relationship_Query_Cardinality_Match $cardinality_match ) {
		return $this->condition_factory->has_cardinality( $cardinality_match );
	}


	/**
	 * Choose a query condition depending on a boolean expression.
	 *
	 * @param bool $statement A boolean condition statement.
	 * @param IToolset_Relationship_Query_Condition $if_branch Query condition that will be used
	 *     if the statement is true.
	 * @param IToolset_Relationship_Query_Condition|null $else_branch Query condition that will be
	 *     used if the statement is false. If none is provided, a tautology is used (always true).
	 *
	 * @return IToolset_Relationship_Query_Condition
	 * @since 2.5.6
	 */
	public function do_if(
		$statement,
		IToolset_Relationship_Query_Condition $if_branch,
		IToolset_Relationship_Query_Condition $else_branch = null
	) {
		if( $statement ) {
			return $if_branch;
		} elseif( null !== $else_branch ) {
			return $else_branch;
		} else {
			return $this->condition_factory->tautology();
		}
	}


	/**
	 * Indicate that the query should also determine the total number of found rows.
	 *
	 * This has to be set to true if you plan using get_found_rows().
	 *
	 * @param bool $is_needed
	 *
	 * @since 2.5.8
	 * @return Toolset_Relationship_Query_V2
	 */
	public function need_found_rows( $is_needed = true ) {
		$this->need_found_rows = (bool) $is_needed;
		return $this;
	}


	/**
	 * Return a number of found rows.
	 *
	 * This can be called only after get_results() if need_found_rows() was set to true
	 * while building the query. Otherwise, an exception will be thrown.
	 *
	 * @return int
	 * @throws RuntimeException
	 * @since 2.5.8
	 */
	public function get_found_rows() {
		if( null === $this->found_rows ) {
			throw new RuntimeException(
				'Cannot return the number of found rows because the query was not instructed to obtain them.'
			);
		}

		return (int) $this->found_rows;
	}


	/**
	 * Condition that excludes a relationship.
	 *
	 * @param Toolset_Relationship_Definition $relationship Relationship Definition.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function exclude_relationship( $relationship ) {
		return $this->condition_factory->exclude_relationship( $relationship );
	}
}
