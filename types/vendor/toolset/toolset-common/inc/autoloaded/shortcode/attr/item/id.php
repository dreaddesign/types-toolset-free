<?php

/**
 * Class Toolset_Shortcode_Attr_Item_Id
 *
 * Adds support for post id for the "item" attribute.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Id implements Toolset_Shortcode_Attr_Interface {

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function get( array $data ) {
		if( ! $role_id = $this->handle_attr_synonyms( $data ) ) {
			// no 'id' attribute used, means current post should be shown
			global $post;

			if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
				$role_id = $post->ID;
			}

			return $this->return_single_id( $role_id );
		}

		$single_id = $this->return_single_id( $role_id );

		if( ! $single_id
             && ( // check if legacy attribute ('id' or 'post_id') is in use
             	( isset( $data['id'] ) && ! empty( $data['id'] ) )
                || ( isset( $data['post_id'] ) && ! empty( $data['post_id'] ) )
			 )
		) {
			// 'id' attribute used, but no valid id given -> use current global post
			// NOTE: we just keep this behaviour as legacy introduced it and we want to max backward compatibilty
			global $post;

			if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
				$single_id = $post->ID;
			}
		}

		return $this->return_single_id( $single_id );
	}

	/**
	 * For our shortcode [types] we allow to use "item" / "id" and "post_id" to select the requested item
	 * This functions handles these synonyms
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	protected function handle_attr_synonyms( array $data ) {
		// 'item' attribute has highest priority
		if ( isset( $data['item'] ) && ! empty( $data['item'] ) ) {
			return $data['item'];
		}

		// no 'item', check for 'id'
		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			return $data['id'];
		}

		// no 'id', check for 'post_id'
		if ( isset( $data['post_id'] ) && ! empty( $data['post_id'] ) ) {
			return $data['post_id'];
		}

		return false;
	}

	/**
	 * @param $return
	 *
	 * @return array|bool|mixed
	 */
	protected function return_single_id( $return ) {
		if( is_array( $return ) && count( $return ) == 1 ) {
			$return = array_shift( $return );
		}

		if( is_int( $return ) || ctype_digit( $return ) ) {
			return $return;
		}

		return false;
	}
}