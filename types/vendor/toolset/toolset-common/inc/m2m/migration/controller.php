<?php

/**
 * Manages the migration from legacy post relationships to m2m data structures.
 *
 * The install_m2m() method is to be called once on TCL upgrade.
 *
 * @since m2m
 */
class Toolset_Relationship_Migration_Controller extends Toolset_Wpdb_User {


	const MESSAGE_SEPARATOR = "\n> ";


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/**
	 * This one needs to be initialized later because it will break when relationship tables don't exist yet.
	 *
	 * @var Toolset_Relationship_Definition_Repository|null
	 */
	private $_relationship_definition_repository;


	/** @var Toolset_Relationship_Migration_Associations|null */
	private $_association_migrator;


	/** @var null|Toolset_Post_Type_Repository */
	private $_post_type_repository;


	/** @var bool */
	private $_do_detailed_logging;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_compatibility;


	/**
	 * Toolset_Relationship_Migration_Controller constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Relationship_Definition_Repository|null $relationship_definition_repository_di
	 * @param Toolset_Relationship_Migration_Associations|null $association_migrator_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository_di = null,
		Toolset_Relationship_Migration_Associations $association_migrator_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->database_operations = $database_operations_di ?: new Toolset_Relationship_Database_Operations();
		$this->_relationship_definition_repository = $relationship_definition_repository_di;
		$this->_association_migrator = $association_migrator_di;
		$this->table_name = $table_name_di ?: new Toolset_Relationship_Table_Name();
		$this->wpml_compatibility = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
		$this->_post_type_repository = $post_type_repository_di;
	}


	private function get_relationship_repository() {
		if( null === $this->_relationship_definition_repository ) {
			$this->_relationship_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_relationship_definition_repository;
	}


	private function get_association_migrator( $create_default_language_if_missing, $copy_post_content_when_creating ) {
		if( null === $this->_association_migrator ) {
			$this->_association_migrator = new Toolset_Relationship_Migration_Associations(
				$this->get_relationship_repository(), $create_default_language_if_missing, $copy_post_content_when_creating,
				null, null, $this->do_detailed_logging()
			);
		}

		return $this->_association_migrator;
	}


	/**
	 * Update the database to support the native m2m implementation.
	 *
	 * Practically that means creating the wp_toolset_associations table.
	 *
	 * @since m2m
	 *
	 * @refactoring TODO is it possible to reliably detect dbDelta failure?
	 */
	public function do_native_dbdelta() {
		return $this->database_operations->do_native_dbdelta();
	}


	/**
	 * If it's enabled by filter, drop all m2m-related tables.
	 *
	 * Useful mainly when debugging the migration process.
	 *
	 * @return Toolset_Result|Toolset_Result_Set
	 * @since m2m
	 */
	public function maybe_drop_m2m_tables() {

		/**
		 * toolset_drop_m2m_tables_before_migration
		 *
		 * If this filter returns true, all m2m-related tables will be dropped at the beginning of the
		 * migration process.
		 *
		 * @since m2m
		 */
		$drop_tables = apply_filters( 'toolset_drop_m2m_tables_before_migration', true );
		if( ! $drop_tables ) {
			return new Toolset_Result( true );
		}

		$m2m_tables = array(
			$this->table_name->association_table(),
			$this->table_name->relationship_table(),
			$this->table_name->type_set_table(),

			// Obsolete table
			$this->wpdb->prefix . 'toolset_association_translations'
		);

		$results = new Toolset_Result_Set();
		foreach( $m2m_tables as $table_name ) {

			if( ! $this->database_operations->table_exists( $table_name ) ) {
				continue;
			}

			$query_result = $this->wpdb->query( 'DROP TABLE ' . $table_name );
			$table_dropped = ( 1 == $query_result );
			$results->add(
				$table_dropped,
				sprintf(
					$table_dropped ? __( 'Dropped table "%s".', 'toolset' ) : __( 'Error while dropping table "%s".', 'toolset'),
					$table_name
				)
			);
		}

		if( ! $results->has_results() ) {
			$results->add( true, __( 'No tables had to be dropped.', 'wpcf' ) );
		}

		return $results;
	}


	/**
	 * Read legacy post relationship settings and convert them into one-to-many relationship definitions.
	 *
	 * Relationship slugs will be {$parent_post_type}_{$child_post_type}. Overwrites existing definitions.
	 *
	 * @param bool $adjust_translation_mode
	 *
	 * @return Toolset_Result_Set
	 * @since m2m
	 */
	public function migrate_relationship_definitions( $adjust_translation_mode ) {

		$relationships = $this->get_legacy_relationship_post_type_pairs();

		$results = new Toolset_Result_Set();

		// Handle empty input (report success)
		if( empty( $relationships ) ) {
			$results->add( new Toolset_Result( true, __( 'No relationships to migrate.', 'wpcf' ) ) );
		}

		foreach( $relationships as $post_type_pair ) {
			$parent_post_type = $post_type_pair['parent'];
			$child_post_type = $post_type_pair['child'];
			$relationship_slug = $post_type_pair['slug'];

			$results->add( $this->maybe_adjust_post_type_translation_mode( $parent_post_type, $adjust_translation_mode ) );
			$results->add( $this->maybe_adjust_post_type_translation_mode( $child_post_type, $adjust_translation_mode ) );

			$result = $this->create_relationship_definition( $parent_post_type, $child_post_type, $relationship_slug );
			$results->add( $result );
		}

		// Now we need to persist everything
		/** @noinspection PhpDeprecationInspection */
		$this->get_relationship_repository()->save_definitions();

		return $results;
	}


	/**
	 * If WPML is active and the given post type has the standard translation mode, switch it to "display as translated".
	 *
	 * @param string $post_type_slug
	 * @param bool $adjust_translation_mode Whether or not to adjust the translation mode.
	 *
	 * @return Toolset_Result
	 * @since 2.5.11
	 */
	private function maybe_adjust_post_type_translation_mode( $post_type_slug, $adjust_translation_mode ) {
		if( Toolset_WPML_Compatibility::MODE_TRANSLATE !== $this->wpml_compatibility->get_post_type_translation_mode( $post_type_slug ) ) {
			// This will happen also if WPML is not active at all.
			return new Toolset_Result( true );
		}

		if( ! $adjust_translation_mode ) {
			return new Toolset_Result(
				true,
				sprintf(
					__( 'The post type "%s" has a "show only translated items" translation mode but no adjustments are made as per user\'s choice.', 'wpcf'),
					sanitize_title( $post_type_slug )
				)
			);
		}

		// Fix the wrong translation mode.
		$this->wpml_compatibility->set_post_type_translation_mode( $post_type_slug, Toolset_WPML_Compatibility::MODE_DISPLAY_AS_TRANSLATED );
		return new Toolset_Result(
			true,
			sprintf(
				__( 'Adjusted the translation mode of the post type "%s" to "display as translated".', 'wpcf' ),
				sanitize_title( $post_type_slug )
			)
		);
	}


	/**
	 * Read the legacy relationships data stored in an option and transform it into array that can be
	 * processed more easily.
	 *
	 * @return array[] Each item is an array with 'parent' and 'child' post type, and also with a proposed 'slug'
	 *     for the relationship definition.
	 * @since m2m
	 */
	public function get_legacy_relationship_post_type_pairs() {

		// Get the legacy relationships definition.
		//
		// It looks somehow like this:
		//
		// array(
		//     “parent_type” => array(
		//         “child_type” => array( /* display options */ ),
		//          ...
		//     ),
		//     ...
		// )
		//
		$relationships = toolset_ensarr( get_option( 'wpcf_post_relationship', array() ) );

		$results = array();

		foreach ( $relationships as $parent_post_type => $relationships_per_post_type ) {
			if( ! $parent_post_type_obj = $this->get_post_type_repository()->get( $parent_post_type ) ) {
				// no parent post type object
				continue;
			}
			$relationships_per_post_type = toolset_ensarr( $relationships_per_post_type );

			foreach ( $relationships_per_post_type as $child_post_type => $temporarily_ignored ) {
				if( ! $child_post_type_obj = $this->get_post_type_repository()->get( $child_post_type ) ) {
					// no child post type object
					continue;
				}

				$results[] = array(
					'parent' => $parent_post_type,
					'child' => $child_post_type,
					'slug' => $this->derive_relationship_slug( $parent_post_type, $child_post_type ),
					'parent_has_show_only_translated_mode' => (
						$this->post_type_has_only_show_translated_mode( $parent_post_type ) ? 1 : 0
					),
					'child_has_show_only_translated_mode' => (
						$this->post_type_has_only_show_translated_mode( $child_post_type ) ? 1 : 0
					),
				);
			}
		}

		return $results;
	}


	private function post_type_has_only_show_translated_mode( $post_type_slug ) {
		if( ! $this->wpml_compatibility->is_wpml_active_and_configured() ) {
			return false;
		}

		if( ! $this->wpml_compatibility->is_post_type_translatable( $post_type_slug ) ) {
			return false;
		}

		return ! $this->wpml_compatibility->is_post_type_display_as_translated( $post_type_slug );
	}


	/**
	 * Create an one-to-many relationship definitions for two provided post types.
	 *
	 * Doesn't persist anything.
	 *
	 * @param string $parent_post_type
	 * @param string $child_post_type
	 * @param string $relationship_slug
	 *
	 * @return Toolset_Result
	 * @since m2m
	 */
	private function create_relationship_definition( $parent_post_type, $child_post_type, $relationship_slug ) {

		$definition_repository = $this->get_relationship_repository();

		// Overwrite the definition if it already exists.
		if( $definition_repository->definition_exists( $relationship_slug ) ) {
			$definition_repository->remove_definition( $relationship_slug );
		}

		try {
			$parent_type = Toolset_Relationship_Element_Type::build_for_post_type( $parent_post_type );
			$child_type = Toolset_Relationship_Element_Type::build_for_post_type( $child_post_type );

			/** @var Toolset_Relationship_Definition $definition */
			$definition = $definition_repository->create_definition( $relationship_slug, $parent_type, $child_type );

			// All legacy relationships are one-to-many
			$cardinality = new Toolset_Relationship_Cardinality( 1, Toolset_Relationship_Cardinality::INFINITY );
			$definition->set_cardinality( $cardinality );

			// All legacy relationships are distinct by definition.
			$definition->is_distinct( true );

			// All legacy relationships need extra backward compatibility support
			$definition->set_legacy_support_requirement( true );

		} catch ( Exception $e ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Could not create relationship definition because an error happened: %s', 'wpcf' ),
					$e->getMessage()
				)
			);
		}

		if( $this->do_detailed_logging() ) {
			return new Toolset_Result(
				true,
				sprintf(
					__( 'Relationship "%s" between post types "%s" and "%s" was created.', 'wpcf' ),
					$relationship_slug,
					$parent_post_type,
					$child_post_type
				)
			);
		}

		return new Toolset_Result( true );
	}


	/**
	 * Generate a relationship slug from two post type slugs.
	 *
	 * @param string $parent_post_type
	 * @param string $child_post_type
	 *
	 * @return string
	 * @since m2m
	 */
	private function derive_relationship_slug( $parent_post_type, $child_post_type ) {

		$relationship_slug = sprintf(
			'%s_%s',
			sanitize_title( $parent_post_type ),
			sanitize_title( $child_post_type )
		);

		return $relationship_slug;
	}


	/**
	 * Migrate post relationship data from the old Types post relationships to the native m2m.
	 *
	 * @since m2m
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param bool $create_default_language_if_missing
	 * @param bool $copy_post_content_when_creating
	 *
	 * @return Toolset_Result_Updated|Toolset_Result_Set
	 */
	public function migrate_associations(
		$offset, $limit, $create_default_language_if_missing, $copy_post_content_when_creating
	) {

		$associations_to_migrate = $this->get_associations_to_migrate( $offset, $limit );

		// Indicate success if there are no more items to process.
		if( empty( $associations_to_migrate ) ) {
			return new Toolset_Result_Updated( true, 0 );
		}

		$results = new Toolset_Result_Set();

		foreach( $associations_to_migrate as $association_to_migrate ) {
			$result = $this->get_association_migrator(
				$create_default_language_if_missing, $copy_post_content_when_creating
			)->migrate_association(
				$association_to_migrate['parent_id'],
				$association_to_migrate['child_id'],
				$association_to_migrate['relationship_slug']
			);
			$results->add( $result );
		}

		if( $results->is_complete_success() ) {
			return new Toolset_Result_Updated( true, count( $associations_to_migrate ), $results->concat_messages( self::MESSAGE_SEPARATOR ) );
		} else {
			return $results;
		}

	}


	/**
	 * Read a batch of legacy association data and prepare it for migration.
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array[] Each element has 'parent_id', 'child_id' and a 'relationship_slug'.
	 * @since m2m
	 */
	public function get_associations_to_migrate( $offset, $limit ) {
		$postmeta_records = $this->get_association_postmeta_records( $offset, $limit );

		$results = array();
		foreach( $postmeta_records as $association_postmeta ) {
			$matches = array();
			preg_match( '/_wpcf_belongs_(.*)_id/', $association_postmeta->relationship_meta_key, $matches );
			$parent_post_type = toolset_getarr( $matches, 1 );

			$results[] = array(
				'parent_id' => (int) $association_postmeta->parent_id,
				'child_id' => (int) $association_postmeta->child_id,
				'relationship_slug' => $this->derive_relationship_slug( $parent_post_type, $association_postmeta->post_type )
			);
		}

		return $results;
	}


	/**
	 * Retrieve postmeta records with legacy post relatioships.
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return object[] Each result has these fields: parent_id, child_id, post_type, relationship_meta_key.
	 *     post_type is related to the child post, whereas relationship_meta_key is
	 *     a string "_wpcf_belongs_{$parent_post_type}_id".
	 *
	 * @since m2m
	 */
	private function get_association_postmeta_records( $offset, $limit ) {

		$query = $this->wpdb->prepare(
			"SELECT post.ID AS child_id,
		  		postmeta.meta_key AS relationship_meta_key,
				postmeta.meta_value AS parent_id,
				post.post_type AS post_type
			FROM {$this->wpdb->postmeta} AS postmeta JOIN {$this->wpdb->posts} AS post ON (postmeta.post_id = post.ID)
			WHERE postmeta.meta_key LIKE %s
			LIMIT %d, %d",
			'\_wpcf\_belongs\_%\_id',
			$offset,
			$limit
		);

		return $this->wpdb->get_results( $query );
	}


	/**
	 * @return Toolset_Post_Type_Repository
	 */
	private function get_post_type_repository() {
		if( null === $this->_post_type_repository ) {
			$this->_post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}

		return $this->_post_type_repository;
	}


	/**
	 * Final migration step.
	 *
	 * @since m2m
	 */
	public function finish() {
	    update_option( Toolset_Relationship_Controller::IS_M2M_ENABLED_OPTION, 'yes', true );
	}


	private function do_detailed_logging() {
		if( null === $this->_do_detailed_logging ) {
			/**
			 * toolset_m2m_migration_do_detailed_logging
			 *
			 * Allow for reducing the m2m migration log output by skipping successful associations.
			 *
			 * @since 3.0
			 */
			$this->_do_detailed_logging = apply_filters( 'toolset_m2m_migration_do_detailed_logging', true );
		}
		return $this->_do_detailed_logging;
	}


}


/**
 * @deprecated Fallback after class renaming. Use Toolset_Relationship_Migration_Controller instead.
 */
class Toolset_Relationship_Migration extends Toolset_Relationship_Migration_Controller {

}
