<?php

/**
 * Various and constants for the Toolset relationships functionality.
 *
 * Note: Code related to native associations and database should go to Toolset_Relationship_Database_Operations.
 *
 * @since m2m
 */
class Toolset_Relationship_Utils {

	/**
	 * @param string|Toolset_Relationship_Definition $relationship_definition_source
	 *
	 * @return null|IToolset_Relationship_Definition
	 */
	public static function get_relationship_definition( $relationship_definition_source ) {
		$rd_factory = Toolset_Relationship_Definition_Repository::get_instance();

		if( $relationship_definition_source instanceof IToolset_Relationship_Definition ) {
			return $relationship_definition_source;
		} elseif( is_string( $relationship_definition_source ) ) {
			return $rd_factory->get_definition( $relationship_definition_source );
		} elseif( is_int( $relationship_definition_source ) ) {
			return $rd_factory->get_definition_by_row_id( $relationship_definition_source );
		}

		return null;
	}

	/**
	 * This method returns all relationships, which includes at least one translated post type
	 *
	 * @param Toolset_Relationship_Definition_Repository|null $relationship_definition_repository
	 *
	 * @return IToolset_Relationship_Definition[]
	 */
	public static function get_all_translated_relationships( Toolset_Relationship_Definition_Repository $relationship_definition_repository = null ) {
		$relationships = $relationship_definition_repository
			? $relationship_definition_repository->get_definitions()
			: Toolset_Relationship_Definition_Repository::get_instance()->get_definitions();

		// collect all relationships which include at least one translatable element
		$relationships_with_translatable_type = array();

		foreach( $relationships as $relationship ) {
			if( $relationship->get_parent_type()->is_translatable()
				|| $relationship->get_child_type()->is_translatable() ) {
				// parent and/or child type is translatable
				$relationships_with_translatable_type[] = $relationship;
			}
		}

		// return collections
		return $relationships_with_translatable_type;
	}
}