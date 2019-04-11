<?php

/**
 * Handler for the types_filter_get_field_group_ids_by_post_type filter API.
 *
 * @since 2.2.14
 */
class Types_Api_Handler_Filter_Get_Field_Group_Ids_By_Post_Type implements Types_Api_Handler_Interface {


	private $wpdb;


	public function __construct( wpdb $wpdb_di = null ) {
		global $wpdb;
		$this->wpdb = ( null === $wpdb_di ? $wpdb : $wpdb_di );
	}


	/**
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return string[]
	 */
	public function process_call( $arguments ) {

		$post_type = toolset_getarr( $arguments, 1 );

		if( ! is_string( $post_type ) ) {
			throw new InvalidArgumentException( 'Invalid argument for a post type.' );
		}

		$query = $this->wpdb->prepare(
			"SELECT post_id FROM {$this->wpdb->postmeta}
            WHERE 
                meta_key = '_wp_types_group_post_types'
                AND (
                    meta_value LIKE %s 
                    OR meta_value = 'all' 
                    OR meta_value REGEXP '^[,]+$'
                )
            ORDER BY post_id ASC",
			'%' . $post_type . '%'
		);

		$post_ids = $this->wpdb->get_col( $query );

		return $post_ids;

	}

}