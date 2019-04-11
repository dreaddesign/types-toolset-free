<?php

/**
 * The class fetches all post ids of all published posts given a post type and deletes them programmatically, without letting m2m API run
 * additional filters to delete Asociations and their data too.
 *
 * Class Toolset_Association_Cleanup_Post_Type
 */
class Toolset_Association_Cleanup_Post_Type extends Toolset_Wpdb_User {


	const DELETE_POSTS_PER_BATCH = 25;


	/** @var int */
	private $found_rows = 0;


	/**
	 * Toolset_Association_Cleanup_Post_Type constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 */
	public function __construct( wpdb $wpdb_di = null ) {
		parent::__construct( $wpdb_di );
	}


	/**
	 * @param $post_type
	 *
	 * @return array
	 */
	protected function get_post_type_posts_ids( $post_type ) {

		$limit = (int) self::DELETE_POSTS_PER_BATCH;

		$query = $this->wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS post.ID FROM {$this->wpdb->posts} AS post 
			WHERE post.post_type = %s LIMIT %d", $post_type, $limit
		);

		$posts_ids = $this->wpdb->get_col( $query );

		$this->found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return $posts_ids;
	}

	/**
	 * @param $post_type
	 *
	 * @return array
	 */
	public function clean_up_posts( $post_type ) {
		$post_ids = array_map( 'intval', $this->get_post_type_posts_ids( $post_type ) );

		add_filter( Toolset_Association_Cleanup_Post::IS_DELETING_FILTER, '__return_true' );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		remove_filter( Toolset_Association_Cleanup_Post::IS_DELETING_FILTER, '__return_true' );

		$results = array();

		$results['total_posts'] = $this->found_rows;

		$results['deleted_posts'] = count( $post_ids );

		return $results;
	}

}