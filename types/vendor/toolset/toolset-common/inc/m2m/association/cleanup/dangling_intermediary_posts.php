<?php

/**
 * Delete a batch of dangling intermediary posts (DIP).
 *
 * A DIP is a post belonging to an intermediary post type that is not involved in an association
 * and is not a translation of any such post. DIPs should not exist and this class queries
 * and permanently deletes them.
 *
 * Only a single batch is deleted on each pass because this might be an expensive operation
 * which can be called from various contexts like WP-Cron or an user-triggered batch process.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Dangling_Intermediary_Posts extends Toolset_Wpdb_User {


	/** @var Toolset_Relationship_Query_Factory */
	private $query_factory;


	/** @var bool After a batch is performed, this will be set to false if there are no more DIPs. */
	private $has_remaining_posts = true;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var Toolset_Post_Type_Query_Factory */
	private $post_type_query_factory;


	private $deleted_posts = 0;


	/**
	 * Toolset_Association_Cleanup_Dangling_Intermediary_Posts constructor.
	 *
	 * @param Toolset_Relationship_Query_Factory|null $query_factory_di
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param Toolset_Post_Type_Query_Factory|null $post_type_query_factory_di
	 */
	public function __construct(
		Toolset_Relationship_Query_Factory $query_factory_di = null,
		wpdb $wpdb_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null,
		Toolset_Post_Type_Query_Factory $post_type_query_factory_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->query_factory = $query_factory_di ?: new Toolset_Relationship_Query_Factory();
		$this->table_name = $table_name_di ?: new Toolset_Relationship_Table_Name( $wpdb_di );
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
		$this->post_type_query_factory = $post_type_query_factory_di ?: new Toolset_Post_Type_Query_Factory();
	}


	/**
	 * Perform one batch of DIP deletions.
	 *
	 * @since 2.5.10
	 */
	public function do_batch() {
		$post_ids = array_map( 'intval', $this->get_post_ids() );
		foreach( $post_ids as $post_to_delete ) {
			add_filter( Toolset_Association_Cleanup_Post::IS_DELETING_FILTER, '__return_true' );
			wp_delete_post( $post_to_delete, true );
			remove_filter( Toolset_Association_Cleanup_Post::IS_DELETING_FILTER, '__return_true' );
		}

		$this->deleted_posts = count( $post_ids );
	}


	private function get_post_ids() {
		$post_ids = $this->wpdb->get_col( $this->build_query() );

		$found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$this->has_remaining_posts = ( $found_rows > Toolset_Association_Cleanup_Post::DELETE_POSTS_PER_BATCH );

		return $post_ids;
	}


	/**
	 * After a batch operation was performed, this will return false if there are no
	 * remaining DIPs to be deleted. Otherwise returns true.
	 *
	 * @return bool
	 */
	public function has_remaining_posts() {
		return $this->has_remaining_posts;
	}


	/**
	 * After a batch operation was performed, this will return the number of posts
	 * that have actually been deleted.
	 *
	 * @return int
	 */
	public function get_deleted_posts() {
		return $this->deleted_posts;
	}


	/**
	 * Build a query for DIPs depending on WPML status.
	 *
	 * @return string Valid and safe MySQL query string.
	 */
	private function build_query() {
		$ipts = '\'' . implode( '\', \'', esc_sql( $this->get_intermediary_post_types() ) ) . '\'';
		$limit = (int) Toolset_Association_Cleanup_Post::DELETE_POSTS_PER_BATCH;

		if( $this->wpml_service->is_wpml_active_and_configured() ) {
			$icl_translations = $this->wpdb->prefix . 'icl_translations';
			$default_language = esc_sql( $this->wpml_service->get_default_language() );

			// Main goal: Query posts of given types that are neither used as intermediary posts directly
			// nor as translations of intermediary posts.
			// We achieve that by doing a LEFT JOINs and then checking if the columns in
			// joined tables are NULL (no match).
			$query = "
				SELECT SQL_CALC_FOUND_ROWS post.ID 
				FROM {$this->wpdb->posts} AS post
					LEFT JOIN {$this->table_name->association_table()} AS association
						ON (post.ID = association.intermediary_id)
					LEFT JOIN {$icl_translations} AS translation
						ON (
							post.ID = translation.element_id
							AND translation.element_type LIKE 'post_%'
							AND translation.language_code != '{$default_language}'
						)
					LEFT JOIN {$icl_translations} AS default_language_translation
						ON (
							translation.trid = default_language_translation.trid
							AND default_language_translation.language_code = '{$default_language}'
						)
					LEFT JOIN {$this->table_name->association_table()} AS default_language_association
						ON(
							default_language_association.intermediary_id = default_language_translation.element_id
						)
				WHERE
					association.intermediary_id IS NULL
					AND default_language_association.intermediary_id IS NULL
					AND post.post_type IN ({$ipts})  
				LIMIT {$limit}";

		} else {
			// Ditto but without the WPML part, as the icl_translations table probably doesn't
			// even exist.
			$query = "
				SELECT SQL_CALC_FOUND_ROWS post.ID
				FROM {$this->wpdb->posts} AS post
					LEFT JOIN {$this->table_name->association_table()} AS association
						ON (post.ID = association.intermediary_id)
				WHERE
					association.intermediary_id IS NULL
					AND post.post_type IN ({$ipts})  
				LIMIT {$limit}";
		}

		return $query;
	}


	/**
	 * @return string[] IPT slugs.
	 */
	private function get_intermediary_post_types() {
		$query = $this->post_type_query_factory->create(
			array(
				'is_intermediary' => true,
				'return' => 'slug'
			)
		);

		return $query->get_results();
	}

}