<?php

/**
 * Factory for instantiating relationship definitions.
 *
 * For internal m2m API use only.
 *
 * @since m2m
 */
class Toolset_Relationship_Definition_Factory {

	/**
	 * @param array $definition_array Definition array of the relationship.
	 *
	 * @return Toolset_Relationship_Definition
	 * @throws InvalidArgumentException
	 */
	public function create( $definition_array ) {
		return new Toolset_Relationship_Definition( $definition_array );
	}

}