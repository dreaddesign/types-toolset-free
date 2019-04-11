<?php

/**
 * Abstract of a field definition (common interface and code for generic and Types field definitions).
 */
abstract class Toolset_Field_Definition_Abstract implements Toolset_Field_Definition_Interface {


	/**
	 * Does the field definition match a certain string?
	 *
	 * Searches it's name and slug.
	 *
	 * @param string $search_string
	 * @return bool
	 */
	public function is_match( $search_string ) {
		return (
			Types_Utils::is_string_match( $search_string, $this->get_name() )
			|| Types_Utils::is_string_match( $search_string, $this->get_slug() )
		);
	}


	/**
	 * @return string[] Slugs of field groups where this field belongs to.
	 * @since 2.1
	 */
	private function get_group_slugs() {
		$groups = $this->get_associated_groups();
		$group_slugs = array();
		foreach( $groups as $group ) {
			$group_slugs[] = $group->get_slug();
		}
		return $group_slugs;
	}


	/**
	 * Get field definition data as an associative array for coversion to JSON.
	 * 
	 * Doesn't return the JSON string directly because child classes may reuse this method and add their own
	 * properties.
	 * 
	 * Guaranteed properties are: isUnderTypesControl, slug, displayName, groups.
	 * 
	 * @return array
	 * @since 2.0
	 */
	public function to_json() {
		
		$object_data = array(
			'isUnderTypesControl' => $this->is_under_types_control(),
			'slug' => $this->get_slug(),
			'metaKey' => $this->get_slug(),
			'displayName' => stripslashes( $this->get_name() ),
			'groups' => $this->get_group_slugs()
		);
		
		return $object_data;
	}


}