<?php

class Toolset_Field_Renderer_Preview_Post extends Toolset_Field_Renderer_Preview_Base {

	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {
		if ( ! $value ) {
			return '';
		}
		$post = get_post( (int) $value);

		if ( ! $post ) {
			return '';
		}

		$has_permission = current_user_can( 'edit_posts' );
		$has_permissions = apply_filters( 'toolset_access_api_get_post_type_permissions', $has_permission, $post->post_type, 'publish', get_current_user_id() );

		if ( $has_permissions ) {
			return sprintf( '<a href="%s" target="_blank">%s</a>',
				esc_attr( get_edit_post_link( $post ) ),
				esc_html( get_the_title( $post ) )
			);
		} else {
			return esc_html( get_the_title( $post ) );
		}
	}
}
