<?php

/**
 * Association query class with a more OOP/functional approach.
 *
 * Replaces Toolset_Association_Query.
 *
 * Allows for chaining query conditions and avoiding passing query arguments as associative arrays.
 * It makes it also possible to build queries with nested AND & OR statements in an arbitrary way.
 * The object model may be complex but all the complexity is hidden from the user, they need to know
 * only the methods on this class.
 *
 * Example usage:
 *
 * $query = new Toolset_Association_Query_V2();
 *
 * $results = $query
 *     ->add(
 *         $query->has_domain( 'posts', new Toolset_Relationship_Role_Parent() )
 *     )
 *     ->add(
 *         $query->do_or(
 *             $query->has_type( 'attachment', new Toolset_Relationship_Role_Parent() ),
 *             $query->do_and(
 *                 $query->has_type( 'page', new Toolset_Relationship_Role_Child() ),
 *                 $query->has_type( 'post', new Toolset_Relationship_Role_Child() ),
 *             )
 *         )
 *     )
 *     ->add(
 *         $query->search( 'some string', new Toolset_Relationship_Role_Parent() )
 *     )
 *     ->order_by_field_value( $custom_field_definition )
 *     ->order( 'DESC' )
 *     ->limit( 50 )
 *     ->offset( 100 )
 *     ->return_association_instances()
 *     ->get_results();
 *
 * Note about default conditions:
 * - If no element status (element_status() or has_available_elements()) condition is used when constructing the query,
 *   has_available_elements() is used.
 * - If no has_active_relationship() condition is used when constructing the query, has_active_relationship(true)
 *   is used.
 * - This mechanism doesn't recognize where, how and if these conditions are actually applied, so even
 *   $query->do_if( false, $query->has_active_relationship( true ) ) will disable the default
 *   has_active_relationship() condition.
 * - You can prevent the adding of default conditions by $query->do_not_add_default_conditions().
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_V2 extends Toolset_Wpdb_User {


	/** @var IToolset_Association_Query_Condition[] */
	private $conditions = array();

	/** @var Toolset_Relationship_Database_Unique_Table_Alias */
	private $unique_table_alias;

	/** @var Toolset_Association_Query_Condition_Factory */
	private $condition_factory;

	/** @var Toolset_Association_Query_Sql_Expression_Builder */
	private $expression_builder;

	/** @var bool */
	private $should_add_default_conditions = true;

	/** @var bool */
	private $has_active_relationship_condition = false;

	/** @var bool */
	private $has_element_status_condition = false;

	/** @var Toolset_Association_Translator */
	private $association_translator;

	/** @var Toolset_Relationship_Definition_Repository|null */
	private $_definition_repository;

	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;

	/** @var IToolset_Association_Query_Result_Transformation */
	private $result_transformation;

	/** @var int|null */
	private $limit = null;

	/** @var int */
	private $offset = 0;

	/** @var IToolset_Association_Query_Orderby|null */
	private $orderby = null;

	/** @var string */
	private $order = 'ASC';

	/** @var bool */
	private $need_found_rows = false;

	/** @var int|null */
	private $found_rows;

	/** @var Toolset_Association_Query_Orderby_Factory */
	private $orderby_factory;

	/** @var bool Remember whether get_results() was called. */
	private $was_used = false;

	/** @var Toolset_Association_Query_Element_Selector_Provider */
	private $element_selector_provider;

	/** @var IToolset_Association_Query_Restriction[] */
	private $restrictions = array();

	/** @var string|null */
	private $translation_language;

	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Toolset_Association_Query_V2 constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Unique_Table_Alias|null $unique_table_alias_di
	 * @param Toolset_Association_Query_Sql_Expression_Builder|null $expression_builder_di
	 * @param Toolset_Association_Query_Condition_Factory|null $condition_factory_di
	 * @param Toolset_Association_Translator|null $association_translator_di
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di
	 * @param Toolset_Association_Query_Table_Join_Manager|null $join_manager_di
	 * @param Toolset_Association_Query_Orderby_Factory|null $orderby_factory_di
	 * @param Toolset_Association_Query_Element_Selector_Provider|null $element_selector_provider_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias_di = null,
		Toolset_Association_Query_Sql_Expression_Builder $expression_builder_di = null,
		Toolset_Association_Query_Condition_Factory $condition_factory_di = null,
		Toolset_Association_Translator $association_translator_di = null,
		Toolset_Relationship_Definition_Repository $definition_repository_di = null,
		Toolset_Association_Query_Table_Join_Manager $join_manager_di = null,
		Toolset_Association_Query_Orderby_Factory $orderby_factory_di = null,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->unique_table_alias = $unique_table_alias_di ?: new Toolset_Relationship_Database_Unique_Table_Alias();
		$this->condition_factory = $condition_factory_di ?: new Toolset_Association_Query_Condition_Factory();
		$this->association_translator = $association_translator_di ?: new Toolset_Association_Translator();
		$this->join_manager = $join_manager_di ?: new Toolset_Association_Query_Table_Join_Manager( $this->unique_table_alias );
		$this->expression_builder = $expression_builder_di ?: new Toolset_Association_Query_Sql_Expression_Builder( $this->join_manager );
		$this->orderby_factory = $orderby_factory_di ?: new Toolset_Association_Query_Orderby_Factory();
		$this->element_selector_provider = $element_selector_provider_di ?: new Toolset_Association_Query_Element_Selector_Provider();
		$this->_definition_repository = $definition_repository_di;
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
	}



	/**
	 * Add another condition to the query.
	 *
	 * @param IToolset_Association_Query_Condition $condition
	 * @return $this
	 */
	public function add( IToolset_Association_Query_Condition $condition ) {
		$this->conditions[] = $condition;
		return $this;
	}


	/**
	 * Allow for adding query restrictions to reduce its complexity
	 * right after all conditions have been added.
	 *
	 * @param IToolset_Association_Query_Condition $root_condition
	 */
	private function maybe_add_restrictions( IToolset_Association_Query_Condition $root_condition ) {
		// Nothing to do yet.
	}


	/**
	 * Basically, this sets default query parameters.
	 *
	 * The method needs to stay idempotent.
	 */
	private function add_default_conditions() {
		if ( ! $this->should_add_default_conditions ) {
			return;
		}

		if( ! $this->has_element_status_condition ) {
			$this->add( $this->has_available_elements() );
		}

		if( ! $this->has_active_relationship_condition ) {
			$this->add( $this->has_active_relationship() );
		}
	}


	/**
	 * Prevent the query from adding any default conditions. WYSIWYG.
	 *
	 * @return $this
	 */
	public function do_not_add_default_conditions() {
		$this->should_add_default_conditions = false;
		return $this;
	}


	/**
	 * @return IToolset_Association_Query_Condition MySQL WHERE clause for the query.
	 */
	private function build_root_condition() {
		$this->add_default_conditions();
		return $this->condition_factory->do_and( $this->conditions );
	}


	/**
	 * Build a complete MySQL query from the conditions.
	 *
	 * @return string
	 * @throws RuntimeException If no query limit is set.
	 */
	private function build_sql_query() {
		$root_condition = $this->build_root_condition();
		$this->maybe_add_restrictions( $root_condition );

		if( null === $this->orderby ) {
			$this->dont_order();
		}

		$this->orderby->set_order( $this->order );

		if( null === $this->limit ) {
			throw new RuntimeException(
				'The query limit has not been set. This is necessary to ensure the scalability.'
			);
		}

		return $this->expression_builder->build(
			$root_condition,
			$this->offset,
			$this->limit,
			$this->orderby,
			$this->element_selector_provider->get_selector(),
			$this->need_found_rows,
			$this->result_transformation
		);
	}



	/**
	 * Apply stored conditions and perform the query.
	 *
	 * @return IToolset_Association[]|int[]|IToolset_Element[]
	 */
	public function get_results() {

		if( $this->was_used ) {
			_doing_it_wrong(
				__FUNCTION__,
				'The association query object should not be reused. Create a new instance if you need to run another query.',
				TOOLSET_COMMON_VERSION
			);
		}

		$this->was_used = true;

		// Default value if no result transformation was selected.
		if( null === $this->result_transformation ) {
			$this->return_association_instances();
		}

		// Sometimes it's not as straightforward as "get current language"
		$this->determine_translation_language();

		$this->apply_restrictions();

		// We do this only after restrictions have been applied.
		$this->element_selector_provider->create_selector(
			$this->unique_table_alias, $this->join_manager, $this
		);

		$query = $this->build_sql_query();
		$rows = toolset_ensarr( $this->wpdb->get_results( $query ) );

		if( $this->need_found_rows ) {
			$this->found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}

		$results = array();
		foreach( $rows as $row ) {
			$result = $this->result_transformation->transform( $row, $this->element_selector_provider->get_selector() );

			if ( null !== $result ) {
				$results[] = $result;
			}
		}

		$this->clear_restrictions();

		return $results;
	}



	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param IToolset_Association_Query_Condition[] [$condition1, $condition2, ...]
	 * @return IToolset_Association_Query_Condition
	 */
	public function do_or() {
		return $this->condition_factory->do_or( func_get_args() );
	}


	/**
	 * Chain multiple conditions with AND.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param IToolset_Association_Query_Condition[] [$condition1, $condition2, ...]
	 * @return IToolset_Association_Query_Condition
	 */
	public function do_and() {
		return $this->condition_factory->do_and( func_get_args() );
	}


	/**
	 * Choose a query condition depending on a boolean expression.
	 *
	 * @param bool $statement A boolean condition statement.
	 * @param IToolset_Association_Query_Condition $if_branch Query condition that will be used
	 *     if the statement is true.
	 * @param IToolset_Association_Query_Condition|null $else_branch Query condition that will be
	 *     used if the statement is false. If none is provided, a tautology is used (always true).
	 *
	 * @return IToolset_Association_Query_Condition
	 * @since 2.5.6
	 */
	public function do_if(
		$statement,
		IToolset_Association_Query_Condition $if_branch,
		IToolset_Association_Query_Condition $else_branch = null
	) {
		if( $statement ) {
			return $if_branch;
		} elseif( null !== $else_branch ) {
			return $else_branch;
		} else {
			return $this->condition_factory->tautology();
		}
	}


	public function not( IToolset_Association_Query_Condition $condition ) {
		return $this->condition_factory->not( $condition );
	}

	/**
	 * Query by a row ID of a relationship definition.
	 *
	 * @param int $relationship_id
	 * @return IToolset_Association_Query_Condition
	 */
	public function relationship_id( $relationship_id ) {
		return $this->condition_factory->relationship_id( $relationship_id );
	}

	/**
	 * Query by a row intermediary_id of a relationship definition.
	 *
	 * @param int $relationship_id
	 * @return IToolset_Association_Query_Condition
	 */
	public function intermediary_id( $relationship_id ) {
		return $this->condition_factory->intermediary_id( $relationship_id );
	}


	/**
	 * Query by a relationship definition.
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @return IToolset_Association_Query_Condition
	 */
	public function relationship( IToolset_Relationship_Definition $relationship_definition ) {
		return $this->relationship_id( $relationship_definition->get_row_id() );
	}


	/**
	 * Query by a relationship definition slug.
	 *
	 * @param string $slug
	 * @return IToolset_Association_Query_Condition
	 */
	public function relationship_slug( $slug ) {
		$definition = $this->get_definition_repository()->get_definition( $slug );
		if( null === $definition ) {
			return $this->condition_factory->contradiction();
		}

		return $this->relationship( $definition );
	}


	/**
	 * @return Toolset_Relationship_Definition_Repository
	 */
	private function get_definition_repository() {
		if( null === $this->_definition_repository ) {
			$this->_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_definition_repository;
	}


	/**
	 * Query by an ID of an element in the selected role.
	 *
	 * Warning: This is an WPML-unaware query.
	 *
	 * @param int $element_id
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param bool $need_wpml_unaware_query Set this to true to avoid a _doing_it_wrong notice.
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function element_id(
		$element_id, IToolset_Relationship_Role_Parent_Child $for_role, $need_wpml_unaware_query = true
	) {
		if( ! $need_wpml_unaware_query ) {
			// This is to ensure a smooth transition from using element_id() everywhere to doing it only
			// in cases where it's explicitly needed. We can remove this after the final release.
			trigger_error(
				'You are using the element_id() condition in the association query. '
				. 'However, this condition is WPML-unaware. Consider using element_id_and_domain() instead '
				.'or, if you really need to ignore element translations, set the new $need_wpml_unaware_query to true.',
				E_NOTICE
			);
		}
		return $this->condition_factory->element_id( $element_id, $for_role, $this->element_selector_provider );
	}


	/**
	 * Query by an ID of an element in the selected role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 * @param bool $set_its_translation_language If true, the query may try to use the element's language
	 *     to determine the desired language of the results (see determine_translation_language() for details)
	 *
	 * @return Toolset_Association_Query_Condition_Element_Id_And_Domain
	 * @since 2.5.10
	 */
	public function element_id_and_domain(
		$element_id,
		$domain,
		IToolset_Relationship_Role $for_role,
		$query_original_element = false,
		$translate_provided_id = true,
		$set_its_translation_language = true
	) {
		if( $set_its_translation_language ) {
			$this->set_translation_language_by_element_id_and_domain( $element_id, $domain );
		}

		return $this->condition_factory->element_id_and_domain(
			$element_id,
			$domain,
			$for_role,
			$this->element_selector_provider,
			$query_original_element,
			$translate_provided_id
		);
	}


	/**
	 * Query by an element in the selected role.
	 *
	 * @param IToolset_Element $element
	 * @param IToolset_Relationship_Role|null $for_role If null is provided, the query will involve all roles.
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 * @param bool $set_its_translation_language If true, the query may try to use the element's language
	 *     to determine the desired language of the results (see determine_translation_language() for details)
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function element(
		IToolset_Element $element,
		IToolset_Relationship_Role $for_role = null,
		$query_original_element = false,
		$translate_provided_id = true,
		$set_its_translation_language = true
	) {
		if( $set_its_translation_language ) {
			$this->set_translation_language_by_element_id_and_domain( $element->get_id(), $element->get_domain() );
		}

		if( null === $for_role ) {
			$conditions = array();
			foreach( Toolset_Relationship_Role::all() as $role ) {
				$conditions[] = $this->element(
					$element, $role, $query_original_element, $translate_provided_id, false
				);
			}
			return $this->do_or( $conditions );
		}

		return $this->element_id_and_domain(
			$element->get_id(), $element->get_domain(), $for_role,
			$query_original_element, $translate_provided_id
		);
	}


	/**
	 * Exclude associations with a particular element in the selected role.
	 *
	 * @param IToolset_Element $element
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $query_original_element If true, the query will check the element ID in the original language
	 *     as stored in the association table. Default is false.
	 * @param bool $translate_provided_id If true, this will try to translate the element ID (if
	 *     applicable on the domain) and use the translated one in the final condition. Default is true.
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function exclude_element(
		IToolset_Element $element,
		IToolset_Relationship_Role $for_role,
		$query_original_element = false,
		$translate_provided_id = true
	) {
		return $this->condition_factory->exclude_element(
			$element->get_id(), $element->get_domain(), $for_role,
			$this->element_selector_provider,
			$query_original_element, $translate_provided_id
		);
	}


	/**
	 * Query by a parent element.
	 *
	 * @param IToolset_Element $element_source
	 * @return IToolset_Association_Query_Condition
	 */
	public function parent( IToolset_Element $element_source ) {
		return $this->element( $element_source, new Toolset_Relationship_Role_Parent() );
	}


	/**
	 * Query by a parent element ID.
	 *
	 * @param int $parent_id
	 * @param string $domain
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function parent_id( $parent_id, $domain = Toolset_Element_Domain::POSTS ) {
		return $this->element_id_and_domain( $parent_id, $domain, new Toolset_Relationship_Role_Parent() );
	}


	/**
	 * Query by a child element.
	 *
	 * @param IToolset_Element $element
	 * @return IToolset_Association_Query_Condition
	 */
	public function child( IToolset_Element $element ) {
		return $this->element( $element, new Toolset_Relationship_Role_Child() );
	}


	/**
	 * Query by a child element ID.
	 *
	 * @param int $child_id
	 * @param string $domain
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function child_id( $child_id, $domain = Toolset_Element_Domain::POSTS ) {
		return $this->element_id_and_domain( $child_id, $domain, new Toolset_Relationship_Role_Child() );
	}


	/**
	 * Query by an element status.
	 *
	 * @param string $status 'any'|'is_available'|'is_public'. Meaning of these options
	 *     is domain-dependant.
	 * @param IToolset_Relationship_Role $for_role
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function element_status( $status, IToolset_Relationship_Role $for_role ) {
		$this->has_element_status_condition = true;

		return $this->condition_factory->element_status(
			$status, $for_role, $this->join_manager, $this->element_selector_provider
		);
	}


	/**
	 * Query only associations that have both elements available (see element_status()).
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_available_elements() {
		$conditions = array();

		foreach( Toolset_Relationship_Role::parent_child() as $role ) {
			$conditions[] = $this->element_status(
				Toolset_Association_Query_Condition_Element_Status::STATUS_AVAILABLE,
				$role
			);
		}

		return $this->do_and( $conditions );
	}


	/**
	 * Query associations by the activity status of the relationship.
	 *
	 * @param bool $is_active
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_active_relationship( $is_active = true ) {
		$this->has_active_relationship_condition = true;
		return $this->condition_factory->has_active_relationship( $is_active, $this->join_manager );
	}


	/**
	 * Query associations by the fact whether the relationship was migrated from the legacy implementation.
	 *
	 * @param bool $needs_legacy_support
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_legacy_relationship( $needs_legacy_support = true ) {
		return $this->condition_factory->has_legacy_relationship( $needs_legacy_support, $this->join_manager );
	}


	/**
	 * Query associations by the element domain on a specified role.
	 *
	 * @param string $domain
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_domain( $domain, IToolset_Relationship_Role_Parent_Child $for_role ) {
		return $this->condition_factory->has_domain( $domain, $for_role, $this->join_manager );
	}


	/**
	 * Query associations based on element type.
	 *
	 * Warning: This doesn't query for the domain. Make sure you at least add
	 * a separate element domain condition. Otherwise, the results will be unpredictable.
	 *
	 * The best way is to use the has_domain_and_type() condition instead, which whill allow
	 * for some more advanced optimizations.
	 *
	 * @param string $type Element type.
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_type( $type, IToolset_Relationship_Role_Parent_Child $for_role ) {
		return $this->condition_factory->has_type( $type, $for_role, $this->join_manager, $this->unique_table_alias );
	}


	/**
	 * Query associations based on element domain and type.
	 *
	 * @param string $domain Element domain.
	 * @param string $type Element type
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_domain_and_type( $domain, $type, IToolset_Relationship_Role_Parent_Child $for_role ) {
		return $this->condition_factory->has_domain_and_type(
			$domain, $type, $for_role, $this->join_manager, $this->unique_table_alias
		);
	}


	/**
	 * Condition that a relationship has a certain origin.
	 *
	 * @param String $origin Origin.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_origin( $origin ) {
		return $this->condition_factory->has_origin( $origin, $this->join_manager );
	}


	/**
	 * Condition that the association has an intermediary id.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_intermediary_id() {
		return $this->condition_factory->has_intermediary_id();
	}


	/**
	 * Query by a WP_Query arguments applied on an element of a specified role.
	 *
	 * WARNING: It is important that you read the documentation of Toolset_Association_Query_Condition_Wp_Query
	 * before using this.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param array $query_args
	 * @param string|null $confirmation 'i_know_what_i_am_doing'
	 *
	 * @return IToolset_Association_Query_Condition
	 *
	 * @throws InvalidArgumentException Thrown if you don't know what you are doing.
	 */
	public function wp_query( IToolset_Relationship_Role $for_role, $query_args, $confirmation = null ) {
		if( 'i_know_what_i_am_doing' !== $confirmation ) {
			throw new InvalidArgumentException();
		}
		return $this->condition_factory->wp_query( $for_role, $query_args, $this->join_manager, $this->unique_table_alias );
	}


	/**
	 * Query by a string search in elements of a selected role.
	 *
	 * Note that the behaviour may be different per domain.
	 *
	 * @param string $search_string
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $is_exact
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function search( $search_string, IToolset_Relationship_Role $for_role, $is_exact = false ) {
		return $this->condition_factory->search( $search_string, $is_exact, $for_role, $this->join_manager );
	}


	/**
	 * Query by a specific association ID.
	 *
	 * This will also set the limit of the result count to one.
	 *
	 * @param int $association_id
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function association_id( $association_id ) {
		$this->limit( 1 );
		return $this->condition_factory->association_id( $association_id );
	}


	public function meta( $meta_key, $meta_value, $domain, IToolset_Relationship_Role $for_role = null, $comparison = Toolset_Query_Comparison_Operator::EQUALS ) {
		if( Toolset_Element_Domain::POSTS !== $domain ) {
			throw new RuntimeException( 'The meta query condition is supported only for the posts domain at the moment.' );
		}

		if( null === $for_role ) {
			$queries_per_role = array();
			foreach( Toolset_Relationship_Role::all() as $role ) {
				$queries_per_role[] = $this->meta( $meta_key, $meta_value, $domain, $role, $comparison );
			}
			return $this->condition_factory->do_and( $queries_per_role );
		}

		return $this->condition_factory->postmeta( $meta_key, $meta_value, $comparison, $for_role, $this->join_manager );
	}


	/**
	 * Indicate that get_results() should return instances of IToolset_Association.
	 *
	 * @return $this
	 */
	public function return_association_instances() {
		$this->result_transformation = new Toolset_Association_Query_Result_Transformation_Association_Instance();
		return $this;
	}


	/**
	 * Indicate that get_results() should return UIDs of associations.
	 *
	 * @return $this
	 */
	public function return_association_uids() {
		$this->result_transformation = new Toolset_Association_Query_Result_Transformation_Association_Uid();
		return $this;
	}


	/**
	 * Indicate that get_results() should return element IDs from a selected role.
	 *
	 * @param IToolset_Relationship_Role $role
	 * @return $this
	 */
	public function return_element_ids( IToolset_Relationship_Role $role ) {
		$this->result_transformation = new Toolset_Association_Query_Result_Transformation_Element_Id( $role );
		return $this;
	}


	/**
	 * Indicate that get_results() should return IToolset_Element instances from a selected role.
	 *
	 * @param IToolset_Relationship_Role $role
	 * @return $this
	 */
	public function return_element_instances( IToolset_Relationship_Role $role ) {
		$this->result_transformation = new Toolset_Association_Query_Result_Transformation_Element_Instance( $role );
		return $this;
	}


	/**
	 * Set an offset for the query.
	 *
	 * @param int $value
	 *
	 * @return $this
	 * @throws InvalidArgumentException Thrown if an invalid value is provided.
	 */
	public function offset( $value ) {
		if( ! Toolset_Utils::is_nonnegative_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Invalid offset value.' );
		}
		$this->offset = (int) $value;
		return $this;
	}


	/**
	 * Limit a number of results for the query.
	 *
	 * Note that by default, the limit is set at a certain value, and the query can never be unlimited.
	 *
	 * @param int $value
	 * @return $this
	 * @throws InvalidArgumentException Thrown if an invalid value is provided.
	 */
	public function limit( $value ) {
		if( ! Toolset_Utils::is_natural_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Invalid limit value.' );
		}
		$this->limit = (int) $value;
		return $this;
	}


	/**
	 * Set the sorting order.
	 *
	 * @param string $value 'ASC'|'DESC'
	 * @return $this
	 */
	public function order( $value ) {
		$this->order = $value;
		return $this;
	}


	/**
	 * Indicate whether the query should also retrieve the total number of results.
	 *
	 * This is required for get_found_rows() to work.
	 *
	 * @param bool $is_needed
	 * @return $this
	 */
	public function need_found_rows( $is_needed = true ) {
		$this->need_found_rows = (bool) $is_needed;
		return $this;
	}


	/**
	 * Return the total number of found results after get_results() was called.
	 *
	 * For this to work, need_found_rows() needs to be called when building the query.
	 *
	 * @return int
	 * @throws RuntimeException
	 */
	public function get_found_rows() {
		if( null === $this->found_rows ) {
			throw new RuntimeException(
				'Cannot return the number of found rows because the query was not instructed to obtain them.'
			);
		}

		return $this->found_rows;
	}


	/**
	 * Indicate that no result ordering is needed.
	 *
	 * @return $this
	 */
	public function dont_order() {
		$this->orderby = $this->orderby_factory->nothing();
		return $this;
	}


	/**
	 * Order results by a title of element of given role.
	 *
	 * Note that ordering by intermediary posts will cause the associations without those to be excluded from results.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @return $this
	 */
	public function order_by_title( IToolset_Relationship_Role $for_role ) {
		$this->orderby = $this->orderby_factory->title( $for_role, $this->join_manager );
		return $this;
	}


	/**
	 * Order results by a value of a certain custom field on a selected element role.
	 *
	 * @param Toolset_Field_Definition $field_definition
	 * @param IToolset_Relationship_Role $for_role
	 *
	 * @return $this
	 * @throws RuntimeException Thrown if the element domain is not supported.
	 */
	public function order_by_field_value( Toolset_Field_Definition $field_definition, IToolset_Relationship_Role $for_role ) {
		switch( $field_definition->get_domain() ) {
			case Toolset_Element_Domain::POSTS:
				$cast_to_numeric = $field_definition->get_type() instanceof Toolset_Field_Type_Definition_Numeric
					? 'SIGNED'
					: null;
				$this->orderby = $this->orderby_factory->postmeta(
					$field_definition->get_meta_key(),
					$for_role,
					$this->join_manager,
					$cast_to_numeric
				);
				break;
			default:
				throw new RuntimeException( 'Element domain not supported.' );
		}

		return $this;
	}


	/**
	 * Order results by a value of the element metadata.
	 *
	 * @param string $meta_key Meta key that should be used for ordering.
	 * @param string $domain Valid element domain. At the moment, only posts are supported.
	 * @param IToolset_Relationship_Role $for_role Role of the element whose metadata should be used for ordering.
	 * @param bool $is_numeric If true, numeric ordering will be used.
	 *
	 * @return $this
	 * @since 2.6.1
	 * @throws RuntimeException If unsupported element domain is used.
	 * @throws InvalidArgumentException
	 */
	public function order_by_meta( $meta_key, $domain, IToolset_Relationship_Role $for_role, $is_numeric = false ) {
		if( Toolset_Element_Domain::POSTS !== $domain ) {
			throw new RuntimeException( 'Element domain not supported.' );
		}

		$cast_to = ( $is_numeric ? 'SIGNED' : null );

		$this->orderby = $this->orderby_factory->postmeta( $meta_key, $for_role, $this->join_manager, $cast_to );

		return $this;
	}


	private function apply_restrictions() {
		foreach( $this->restrictions as $restriction ) {
			$restriction->apply();
		}
	}


	private function clear_restrictions() {
		foreach( $this->restrictions as $restriction ) {
			$restriction->clear();
		}
	}


	/**
	 * Make sure that the elements in results will never get translated.
	 *
	 * @since 2.6.4
	 * @return $this
	 */
	public function dont_translate_results() {
		$this->element_selector_provider->attempt_translating_elements( false );
		return $this;
	}


	/**
	 * Set the preferred translation language.
	 *
	 * See determine_translation_language() for details.
	 *
	 * @param string $lang_code Valid language code.
	 *
	 * @return $this
	 */
	public function set_translation_language( $lang_code ) {
		if( ! is_string( $lang_code ) ) {
			throw new InvalidArgumentException();
		}

		$this->translation_language = $lang_code;

		return $this;
	}


	/**
	 * Set the preferred translation language from a given element ID and domain.
	 *
	 * See determine_translation_language() for details.
	 *
	 * @param int $element_id ID of the element to take the language from.
	 * @param string $domain Element domain.
	 *
	 * @return $this
	 * @since 2.6.8
	 */
	public function set_translation_language_by_element_id_and_domain( $element_id, $domain ) {
		if( Toolset_Element_Domain::POSTS !== $domain ) {
			// no language information there
			return $this;
		}

		$post_language = $this->wpml_service->get_post_language( $element_id );
		if( ! empty( $post_language ) ) {
			$this->set_translation_language( $post_language );
		}

		return $this;
	}


	/**
	 * Determine an alternative to the translation language (what language version of the results should be chosen).
	 *
	 * This will be used only if applicable - if WPML is active and the current language is set to "All languages",
	 * in which case we're forced to pick one.
	 *
	 * If we have a valid lang code, we'll pass it to the element selector. Otherwise, it will use the default language.
	 *
	 * @since 2.6.8
	 */
	private function determine_translation_language() {
		if( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return;
		}

		if( ! $this->wpml_service->is_showing_all_languages() ) {
			return;
		}

		if( null === $this->translation_language ) {
			// Here, we may try to determine the language by some other means.
			return;
		}

		$this->element_selector_provider->set_translation_language( $this->translation_language );
	}


	/**
	 * Perform the query to only return the number of found rows, if we're not interested in
	 * the actual results.
	 *
	 * @return int Number of results matching the query.
	 */
	public function get_found_rows_directly() {
		$this->need_found_rows()
			->limit( 1 )
			->return_association_uids()
			->get_results();

		return $this->get_found_rows();
	}
}
