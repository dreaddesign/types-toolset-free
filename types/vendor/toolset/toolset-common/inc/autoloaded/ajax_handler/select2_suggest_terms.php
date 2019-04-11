<?php

/**
 * Handles toolset_select2 instances that suggest terms.
 * 
 * @since m2m
 */
class Toolset_Ajax_Handler_Select2_Suggest_Terms extends Toolset_Ajax_Handler_Abstract {


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_TERMS,
				'is_public' => true
			)
		);
		
		// Read and validate input
		$s = toolset_getpost( 's' );
		
		
		if ( empty( $s ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing query.', 'wpv-views' ) ), false );
		}
		
		global $wpdb;
		
		if ( method_exists( $wpdb, 'esc_like' ) ) { 
			$s = '%' . $wpdb->esc_like( $s ) . '%'; 
		} else { 
			$s = '%' . like_escape( esc_sql( $s ) ) . '%'; 
		}
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, name 
				FROM {$wpdb->terms} 
				WHERE name LIKE %s 
				LIMIT 0, 15",
				$s
			)
		);
		
		if ( 
			isset( $results ) 
			&& ! empty( $results ) 
		) {
			$output = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $result ) {
					$output[] = array(
						'text' => $result->name,
						'id' => $result->term_id,
					);
				}
			}
			$this->ajax_finish( $output, true );
		}
		
		$this->ajax_finish( array( 'message' => __( 'Error while retrieving result.', 'wpv-views' ) ), false );
		
	}

}