<?php

/**
 * A class for querying relationship definitions.
 *
 * Arguments:
 *     todo document
 *
 * Usage:
 *
 *     $query = new Toolset_Relationship_Query( $args );
 *     $results = $query->get_results();
 *
 * Notes:
 *
 *   - For now, it doesn't query the database because all relationship definitions are loaded at once by the factory.
 *     That may change in the future but it should not influence the interface.
 *   - If you need to query by some parameters that are not supported, either create a feature request about it or
 *     submit a merge request rather than going around the query and touching the database directly.
 *
 * @since m2m
 *
 * @deprecated Use Toolset_Relationship_Query_V2 instead.
 */
class Toolset_Relationship_Query extends Toolset_Relationship_Query_Base {


	/**
	 * Filter by affected domain.
	 *
	 * Selectd only relationship definitions that have the specified domain on the side of a child, parent or both.
	 */
	const QUERY_HAS_DOMAIN = 'has_domain';


	const QUERY_HAS_TYPE = 'has_type';


	const QUERY_HAS_OWNER_TYPE = 'has_owner_type';


	const QUERY_IS_TRANSLATABLE = 'is_translatable';


	const QUERY_IS_LEGACY = 'is_legacy';


	const QUERY_IS_ACTIVE = 'is_active';

	/**
	 * @inheritdoc
	 * @param array $query
	 */
	protected function parse_query( $query ) {

		$this->parse_query_arg( $query, self::QUERY_HAS_DOMAIN, 'strval' );
		$this->parse_query_arg( $query, self::QUERY_HAS_TYPE, 'toolset_ensarr' );
		$this->parse_query_arg( $query, self::QUERY_HAS_OWNER_TYPE, 'toolset_ensarr' );
		$this->parse_query_arg( $query, self::QUERY_IS_TRANSLATABLE, 'boolval' );
		$this->parse_query_arg( $query, self::QUERY_IS_LEGACY, 'boolval' );
		$this->parse_query_arg( $query, self::QUERY_IS_ACTIVE, 'boolval', true );

	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_subject_name_for_cache() {
		return 'relationships';
	}


	// This method should be never called in this class.
	protected function build_sql_statement() {
		throw new RuntimeException();
	}

	// This method should be never called in this class.
	protected function postprocess_results( $rows ) {
		throw new RuntimeException();
	}


	/**
	 * @inheritdoc
	 * @return Toolset_Relationship_Definition[]
	 * @deprecated Use Toolset_Relationship_Query_V2 instead.
	 */
	public function get_results() {

		/* _doing_it_wrong(
			'Toolset_Relationship_Query::get_results',
			'Toolset_Relationship_Query is deprecated in favor of Toolset_Relationship_Query_V2',
			''
		); */

		if ( $this->use_cached_results ) {
			$cache = Toolset_Relationship_Query_Cache::get_instance();
			$value = $cache->get( $this->query, $this->get_subject_name_for_cache() );
			if ( false !== $value ) {
				return $value;
			}
		}

		// Avoid using database - get all relationship definitions from the factory and then filter them.
		$definition_factory = Toolset_Relationship_Definition_Repository::get_instance();
		$all_definitions = $definition_factory->get_definitions();
		$results = $this->apply_query_arguments( $all_definitions );
		// No postprocessing is needed.

		if ( $this->cache_results ) {
			$cache = Toolset_Relationship_Query_Cache::get_instance();
			$cache->set( $this->query, $this->get_subject_name_for_cache(), $results );
		}

		return $results;

	}



	private function apply_query_arguments( $all_definitions ) {

		$results = $all_definitions;

		$filters_to_apply = array(
			self::QUERY_HAS_DOMAIN => 'filter_has_domain',
			self::QUERY_HAS_TYPE => 'filter_has_type',
			self::QUERY_HAS_OWNER_TYPE => 'filter_has_owner_type',
			self::QUERY_IS_TRANSLATABLE => 'filter_is_translatable',
			self::QUERY_IS_LEGACY => 'filter_is_legacy',
			self::QUERY_IS_ACTIVE => 'filter_is_active',
		);

		foreach ( $filters_to_apply as $query_var_name => $callback_name ) {
			if ( $this->has_query_var( $query_var_name ) ) {
				$results = array_filter( $results, array( $this, $callback_name ) );
			}
		}

		return $results;
	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 * Filter relationships by the QUERY_HAS_DOMAIN argument.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 *
	 * @return bool
	 */
	private function filter_has_domain( $relationship_definition ) {

		$domain_query = $this->get_query_var( self::QUERY_HAS_DOMAIN );

		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $element_role ) {
			$domain = $relationship_definition->get_element_type( $element_role )->get_domain();
			if ( $domain_query == $domain ) {
				return true;
			}
		}

		return false;

	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 * Filter relationships by the QUERY_HAS_TYPE argument.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 *
	 * @return bool
	 */
	private function filter_has_type( $relationship_definition ) {

		$type_query = $this->get_query_var( self::QUERY_HAS_TYPE );
		$target_domain = toolset_getarr( $type_query, 'domain' );
		$target_types = toolset_wraparr( toolset_getarr( $type_query, 'type' ) );
		$target_types[0] = toolset_wraparr( $target_types[0] );
		$target_types['comparison'] = isset( $target_types['comparison'] ) ? $target_types['comparison'] : 'OR';

		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			$element_type = $relationship_definition->get_element_type( $role );
			$types = $element_type->get_types();

			foreach ( $target_types[0] as $target_type ) {
				switch ( $target_types['comparison'] ) {
					case 'OR':
						if ( $element_type->get_domain() == $target_domain && in_array( $target_type, $types ) ) {
							return true;
						}
						break;
					default:
						throw new RuntimeException( 'Not implemented' );
						break;
				}
			}
		}

		return false;
	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 * Filter relationships by the QUERY_HAS_OWNER_TYPE argument.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 *
	 * @return bool
	 */
	private function filter_has_owner_type( $relationship_definition ) {

		$type_query = $this->get_query_var( self::QUERY_HAS_TYPE );
		$target_domain = toolset_getarr( $type_query, 'domain' );
		$target_type = toolset_getarr( $type_query, 'type' );

		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			$element_type = $relationship_definition->get_element_type( $role );
			$types = $element_type->get_types();

			if ( $element_type->get_domain() === $target_domain && in_array( $target_type, $types ) ) {
				if ( $relationship_definition->get_owner() == $role ) {
					return true;
				}
			}
		}

		return false;
	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @return bool
	 */
	private function filter_is_translatable( $relationship_definition ) {
		$should_be_translatable = $this->get_query_var( self::QUERY_IS_TRANSLATABLE );
		return ( $relationship_definition->is_translatable() === $should_be_translatable );
	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @return bool
	 */
	private function filter_is_legacy( $relationship_definition ) {
		$should_be_legacy = $this->get_query_var( self::QUERY_IS_LEGACY );
		return ( $relationship_definition->needs_legacy_support() === $should_be_legacy );
	}


	/** @noinspection PhpUnusedPrivateMethodInspection (used by apply_query_argument())
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @return bool
	 */
	private function filter_is_active( $relationship_definition ) {
		$should_be_active = $this->get_query_var( self::QUERY_IS_ACTIVE );
		return ( $relationship_definition->is_active() === $should_be_active );
	}
}