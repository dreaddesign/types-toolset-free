<?php

/**
 * Interface of a field definition
 *
 * @since 2.3
 */
interface Toolset_Field_Definition_Interface {

	/**
	 * @return string Field definition slug.
	 */
	public function get_slug();


	/**
	 * @return string Field definition display name.
	 */
	public function get_name();


	/**
	 * @return string Description provided by the user.
	 */
	public function get_description();


	/**
	 * @return string Meta key used to store values of these fields.
	 */
	public function get_meta_key();

	/**
	 * Determine whether the field is currently under Types control.
	 *
	 * @return mixed
	 */
	public function is_under_types_control();


	/**
	 * @return Toolset_Field_Group[]
	 */
	public function get_associated_groups();


	/**
	 * Does the field definition match a certain string?
	 *
	 * Searches it's name and slug.
	 *
	 * @param string $search_string
	 * @return bool
	 */
	public function is_match( $search_string );


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
	public function to_json();

}