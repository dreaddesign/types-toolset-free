<?php

/**
 * User field group.
 *
 * @since 2.0
 */
class Toolset_Field_Group_User extends Toolset_Field_Group {


	const POST_TYPE = 'wp-types-user-group';



	/**
	 * @param WP_Post $field_group_post Post object representing a user field group.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $field_group_post ) {
		parent::__construct( $field_group_post );
		if( self::POST_TYPE != $field_group_post->post_type ) {
			throw new InvalidArgumentException( 'incorrect post type' );
		}
	}


	/**
	 * @return Toolset_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected function get_field_definition_factory() {
		return Toolset_Field_Definition_Factory_User::get_instance();
	}
}
