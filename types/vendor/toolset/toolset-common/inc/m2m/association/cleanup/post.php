<?php

/**
 * Perform a cleanup after a single post has been deleted.
 *
 * Needs to be hooked to the before_delete_post action.
 *
 * Short version:
 *
 * This situation is much more tricky than when just deleting a single association. One post
 * can be involved in many associations and deleting those might trigger also deleting of
 * intermediary posts and their translations.
 *
 * Long version:
 *
 * Associations themselves can be handled with a single MySQL query,
 * but for deleting intermediary posts, we have to perform consecutive wp_delete_post() calls,
 * which in turn may trigger further deletions if those intermediary posts are translated
 * to more languages.
 *
 * We simply cannot afford to delete all intermediary posts at once, because that might be
 * easily much more than the server can handle, and we can't immediately show a
 * batch deletion dialog because we don't know in which context the initial post is deleted.
 * It may be even during an AJAX call or whatnot.
 *
 * The problem is that we don't want to have lingering intermediary posts because the user
 * might use them in a View, for example, and assume that an intermediary post == an association.
 *
 * Here, a compromise solution is implemented: We immediately delete a certain number of
 * intermediary posts, which will cover 99% of these cases, and for the remaining 1%
 * of big deletions, offer a clean-up routine on the Toolset Troubleshooting page.
 *
 * If we detect that such a cleanup is needed, we'll display a notice until the user goes
 * to the troubleshooting page and clicks the button.
 *
 * On top of that, a CRON job will be created to complete the cleanup if the user doesn't
 * take action soon enough.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Post extends Toolset_Wpdb_User {


	const DELETE_POSTS_PER_BATCH = 25;


	const IS_DELETING_FILTER = 'toolset_is_deleting_intermediary_post_purposefully';


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var Toolset_Relationship_Query_Factory */
	private $query_factory;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var null|Toolset_Cron */
	private $_cron;


	/** @var null|Toolset_Association_Cleanup_Factory */
	private $_cleanup_factory;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var null|Toolset_Association_Intermediary_Post_Persistence */
	private $_ip_persistence;


	/**
	 * Toolset_Association_Cleanup_Post constructor.
	 *
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_Relationship_Query_Factory|null $query_factory_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Cron|null $cron_di
	 * @param Toolset_Association_Cleanup_Factory|null $cleanup_factory_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param Toolset_Association_Intermediary_Post_Persistence|null $intermediary_post_persistence_di
	 */
	public function __construct(
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_Relationship_Query_Factory $query_factory_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		wpdb $wpdb_di = null,
		Toolset_Cron $cron_di = null,
		Toolset_Association_Cleanup_Factory $cleanup_factory_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null,
		Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence_di = null
	) {
		parent::__construct( null );
		$this->element_factory = $element_factory_di ?: new Toolset_Element_Factory();
		$this->query_factory = $query_factory_di ?: new Toolset_Relationship_Query_Factory();
		$this->table_name = $table_name_di ?: new Toolset_Relationship_Table_Name();
		$this->_cron = $cron_di;
		$this->_cleanup_factory = $cleanup_factory_di;
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
		$this->_ip_persistence = $intermediary_post_persistence_di;
	}


	/**
	 * Clean up affected associations before a post is permanently deleted.
	 *
	 * @param int $post_id
	 */
	public function cleanup( $post_id ) {
		/**
		 * Filter that can be used to indicate that an intermediary post is deleted
		 * purposefully, and that the association shouldn't be removed.
		 *
		 * @since 2.6.8
		 */
		$is_deleting_association = apply_filters( self::IS_DELETING_FILTER, false );

		if( $is_deleting_association ) {
			// Prevent an infinite recursion if a single association is being deleted.
			// If we got here, it means that the association's intermediary post is about to
			// be deleted and everything else is already handled either
			// in Toolset_Association_Cleanup_Association, or within this class.
			//
			// Or there is a different situation where an intermediary post is being deleted
			// but we want to preserve the association.
			return;
		}

		try {
			$post = $this->element_factory->get_post_untranslated( $post_id );

		} /** @noinspection PhpRedundantCatchClauseInspection */
		catch( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// The post is already gone, do nothing.
			return;
		}

		if( $post->is_revision() ) {
			// No need to handle revisions. They're not supposed to have any
			// associations at all. Just let WordPress proceed with the deletion.
			return;
		}

		if( ! $this->is_involved_in_association_directly( $post ) ) {
			// A post may be a translation of another post that is involved in an association
			// as a parent, a child or an intermediary post. But in any of these cases, we don't
			// have to delete anything. Not even the intermediary post translation. We allow even such
			// scenarios as having a translatable intermediary post type but non-translatable
			// parent and child.
			//
			// Intermediary post translations will be deleted only when the whole association is deleted
			// or they can be deleted manually, if the user cares about it.
			return;
		}

		// The post was directly involved in an association - either it's non-translatable
		// or in the default language. We delete the associations and we're done.
		$this->delete_associations_involving_post( $post );
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return bool
	 */
	private function is_involved_in_association_directly( IToolset_Post $post ) {
		$query = $this->query_factory->associations_v2();

		$found_rows = $query
			->add( $query->element(
				$post, null, true, false
			) )
			->do_not_add_default_conditions()
			->get_found_rows_directly();

		return ( $found_rows > 0 );
	}


	/**
	 * @param IToolset_Post $post
	 */
	private function delete_associations_involving_post( IToolset_Post $post ) {
		$this->delete_involved_intermediary_posts( $post );
		$this->delete_association_rows( $post );
	}


	/**
	 * Delete all the affected association rows from the database.
	 *
	 * @param IToolset_Post $post
	 *
	 * @return Toolset_Result
	 */
	private function delete_association_rows( IToolset_Post $post ) {
		$associations = $this->table_name->association_table();
		$relationships = $this->table_name->relationship_table();

		$query = $this->wpdb->prepare(
			"DELETE association 
			FROM {$associations} AS association
			JOIN {$relationships} AS relationship 
				ON ( association.relationship_id = relationship.id )
			WHERE 
				( relationship.parent_domain = 'posts' AND parent_id = %d )
				OR ( relationship.child_domain = 'posts' AND child_id = %d )
				OR ( intermediary_id = %d )",
			$post->get_id(),
			$post->get_id(),
			$post->get_id()
		);

		$deleted_rows = $this->wpdb->query( $query );

		return new Toolset_Result( $deleted_rows > 0 );
	}


	/**
	 * Delete a first batch of intermediary posts that should be removed together
	 * with the association. If some intermediary posts remain, set up a CRON job and an admin notice
	 * for the user.
	 *
	 * @param IToolset_Post $post
	 */
	private function delete_involved_intermediary_posts( IToolset_Post $post ) {
		$query = $this->query_factory->associations_v2();

		$intermediary_post_ids = $query
			->add( $query->do_or(
				// Not intermediary posts. If the element is an intermediary post,
				// we need to exclude it (it's already being deleted) and we wouldn't get any other
				// associations where it's involved.
				$query->element( $post, new Toolset_Relationship_Role_Parent(), true, false ),
				$query->element( $post, new Toolset_Relationship_Role_Child(), true, false )
			) )
			->do_not_add_default_conditions()
			->limit( self::DELETE_POSTS_PER_BATCH )
			->return_element_ids( new Toolset_Relationship_Role_Intermediary() )
			->need_found_rows()
			->get_results();

		foreach( $intermediary_post_ids as $intermediary_post_id ) {
			// This will also delete post translations.
			$this->get_intermediary_post_persistence()->delete_intermediary_post( $intermediary_post_id );
		}

		if( $query->get_found_rows() > self::DELETE_POSTS_PER_BATCH ) {
			// Some dangling posts are left, there's too much of them to be deleted at once.
			// Schedule a WP-Cron event to delete them by batches until there are none left.
			$this->schedule_dangling_post_removal();
		}
	}


	/**
	 * @return Toolset_Association_Cleanup_Factory
	 */
	private function get_cleanup_factory() {
		if( null === $this->_cleanup_factory ) {
			$this->_cleanup_factory = new Toolset_Association_Cleanup_Factory();
		}
		return $this->_cleanup_factory;
	}


	/**
	 * @return Toolset_Cron
	 */
	private function get_cron() {
		if( null === $this->_cron ) {
			$this->_cron = Toolset_Cron::get_instance();
		}
		return $this->_cron;
	}


	private function schedule_dangling_post_removal() {
		$cron_event = $this->get_cleanup_factory()->cron_event();
		$this->get_cron()->schedule_event( $cron_event );
	}


	/**
	 * @return Toolset_Association_Intermediary_Post_Persistence
	 */
	private function get_intermediary_post_persistence() {
		if( null === $this->_ip_persistence ) {
			$this->_ip_persistence = new Toolset_Association_Intermediary_Post_Persistence();
		}
		return $this->_ip_persistence;
	}


}