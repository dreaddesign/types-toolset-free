<?php

/**
 * Handles toolset_select2 instances that suggest post by title.
 *
 * If a given post type is not provided, it will return no results.
 * valueType - If a return value among [ 'ID', 'post_name' ] is not provided, it will return result built upon post ID.
 * orderBy - Can order by 'date', 'title', 'ID'.
 * order - Can order as 'DESC' or 'ASC'.
 * author - Can filter by an author ID, or return no results if passed as zero.
 * 
 * @since m2m
 */
class Toolset_Ajax_Handler_Select2_Suggest_Posts_By_Post_Type extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE,
				'is_public' => true
			)
		);
		
		$post_type = toolset_getpost( 'postType' );
		
		if ( empty( $post_type ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing query.', 'wpv-views' ) ), false );
		}
		
		$return = toolset_getpost( 'valueType' );
		$return = in_array( $return, array( 'ID', 'post_name' ) ) 
			? $return 
			: 'ID';

		global $wpdb;
		$values_to_prepare = array();

		$post_type_query = '';


		$post_type_query     = "AND post_type = %s ";
		$values_to_prepare[] = $post_type;
		
		$author_query = '';
		if ( '' != toolset_getpost('author') ) {
			$author_query = "AND post_author = %s";
			$values_to_prepare[] = (int) toolset_getpost('author');
		}
		
		$orderby = toolset_getpost( 'orderBy', 'ID', array( 'date', 'title', 'ID' ) );
		$values_to_prepare[] = $orderby;
		
		$order = toolset_getpost( 'order', 'DESC', array( 'ASC', 'DESC' ) );
		$values_to_prepare[] = $order;
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_type, post_title, post_name 
				FROM {$wpdb->posts} 
				WHERE post_status = 'publish' 
				{$post_type_query} 
				{$author_query} 
				ORDER BY %s %s
				LIMIT 0, 15",
				$values_to_prepare
			)
		);


		if ( 
			isset( $results ) 
			&& ! empty( $results ) 
		) {

			$final = array();

			if ( is_array( $results ) ) {

				foreach ( $results as $result ) {
					$final[] = array(
						'id' => $result->$return,
						'text' => $result->post_title
					);
				}

				$final = array_values( $final );

			}
			$this->ajax_finish( $final, true );

		} else {
			// return empty result set
			$result  = array();
			$this->ajax_finish( $result, true );
		}
		
		$this->ajax_finish( array( 'message' => __( 'Error while retrieving result.', 'wpv-views' ) ), false );
		
	}

	/**
	 * Return only post type from post object, used for array_map
	 * @param $one_post
	 * @return string
	 */
	private function get_post_type( $one_post ){
		return $one_post->post_type;
	}

}