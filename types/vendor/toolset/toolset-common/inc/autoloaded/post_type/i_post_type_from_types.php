<?php

/**
 * Interface IToolset_Post_Type_From_Types
 *
 * TODO: Fill with methods from Toolset_Post_Type_From_Types instead of using the class directly.
 */
interface IToolset_Post_Type_From_Types extends IToolset_Post_Type {

	/**
	 * "touch" the post type before saving, update the timestamp and user who edited it last.
	 */
	public function touch();


	/**
	 * Get the definition array from Types.
	 *
	 * Do not use directly if possible: Instead, implement the getter you need.
	 *
	 * @return array
	 */
	public function get_definition();


	/**
	 * Set a specific post type label.
	 * @param string $label_name Label name from Toolset_Post_Type_Labels.
	 * @param string $value Value of the label.
	 */
	public function set_label( $label_name, $value );


	/**
	 * Flag a (fresh) post type as an intermediary one.
	 *
	 * @param bool $should_stay_visible
	 */
	public function set_as_intermediary( $should_stay_visible = false );


	/**
	 * Remove the intermediary flag from the post type.
	 *
	 * @return void
	 */
	public function unset_as_intermediary();


	/**
	 * Set the flag indicating whether this post type acts as a repeating field group.
	 *
	 * @param bool $value
	 * @return void
	 */
	public function set_is_repeating_field_group( $value );


	/**
	 * Never use directly: Change the slug via Toolset_Post_Type_Repository::rename() instead.
	 *
	 * @param string $new_value
	 */
	public function set_slug( $new_value );


	/**
	 * Set the 'public' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_public( $value );


	/**
	 * Set the 'disabled' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_disabled( $value );


	/**
	 * @return bool Corresponds with the disabled status of the post type.
	 */
	public function is_disabled();


	/**
	 * @return IToolset_Post_Type_Registered|null
	 * @since 2.6.3
	 */
	public function get_registered_post_type();


	/**
	 * @param IToolset_Post_Type_Registered $registered_post_type
	 * @since 2.6.3
	 */
	public function set_registered_post_type( IToolset_Post_Type_Registered $registered_post_type );
}