<?php

/**
 * Condition to query associations by a particular element involved in a particular role.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Element_Id_And_Domain extends Toolset_Association_Query_Condition {


	/** @var int */
	private $element_id;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Element_Selector_Provider */
	private $element_selector_provider;


	/** @var bool */
	private $translate_provided_id;


	/** @var bool */
	private $query_original_element;


	/** @var string */
	private $domain;


	/**
	 * Toolset_Association_Query_Condition_Element_Id constructor.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_id
	 */
	public function __construct(
		$element_id,
		$domain,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_id
	) {
		if(
			! Toolset_Utils::is_nonnegative_integer( $element_id )
			|| ! in_array( $domain, Toolset_Element_Domain::all(), true )
		) {
			throw new InvalidArgumentException();
		}

		if(
			$for_role instanceof Toolset_Relationship_Role_Intermediary
			&& Toolset_Element_Domain::POSTS !== $domain
		) {
			throw new InvalidArgumentException( 'Querying by an intermediary post with a wrong element domain.' );
		}

		$this->element_id = (int) $element_id;
		$this->domain = $domain;
		$this->for_role = $for_role;
		$this->element_selector_provider = $element_selector_provider;
		$this->translate_provided_id = (bool) $translate_provided_id;
		$this->query_original_element = (bool) $query_original_element;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$column_name = $this->element_selector_provider
			->get_selector()
			->get_element_id_value(
				$this->for_role, ! $this->query_original_element
			);

		return sprintf(
			'%s %s %d', $column_name, $this->get_operator(), $this->get_element_id_to_query()
		);
	}


	private function get_element_id_to_query() {
		if( Toolset_Element_Domain::POSTS === $this->domain && $this->translate_provided_id ) {
			$element_id = apply_filters( 'wpml_object_id', $this->element_id, 'any', true );
			return $element_id;
		}

		return $this->element_id;
	}


	protected function get_operator() {
		return '=';
	}

}