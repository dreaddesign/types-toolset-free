<?php

/**
 * Transform association query results into association UIDs.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Result_Transformation_Association_Uid
	implements IToolset_Association_Query_Result_Transformation {


	/**
	 * @inheritdoc
	 *
	 * @param object $database_row
	 *
	 * @return int
	 */
	public function transform(
		$database_row, IToolset_Association_Query_Element_Selector $element_selector
	) {
		return (int) $database_row->id;
	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return void
	 * @since 2.5.10
	 */
	public function request_element_selection( IToolset_Association_Query_Element_Selector $element_selector ) {
		// We're only returning the association UID but don't care about its elements.
		$element_selector->request_association_and_relationship_in_results();
	}

}