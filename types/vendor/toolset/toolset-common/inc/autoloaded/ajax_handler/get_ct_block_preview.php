<?php

/**
 * Handles AJAX calls to get the Content Template block preview.
 * 
 * @since m2m
 */
class Toolset_Ajax_Handler_Get_Content_Template_Block_Preview extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW,
				'is_public' => false,
			)
		);

		$ct_post_name = sanitize_text_field( toolset_getpost( 'ct_post_name', '' ) );

		if ( empty( $ct_post_name ) ) {
			$this->ajax_finish( array( 'message' => __( 'Content Template not set.', 'wpv-views' ) ), false );
		}

		$args = array(
			'name' => $ct_post_name,
			'posts_per_page' => 1,
			'post_type' => 'view-template',
			'post_status' => 'publish',
		);

		$ct = get_posts( $args );

		if (
			null !== $ct
			&& count( $ct ) == 1
		) {
			$ct_post_content = str_replace( "\t", '&nbsp;&nbsp;&nbsp;&nbsp;', str_replace( "\n", '<br />', $ct[0]->post_content ) );
			$this->ajax_finish( $ct_post_content, true );
		}

		$this->ajax_finish( array( 'message' => sprintf( __( 'Error while retrieving the Content Template preview. The selected Content Template (Slug: "%s") was not found.', 'wpv-views' ), $ct_post_name ) ), false );
	}
}
