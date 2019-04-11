<?php

namespace OTGS\Toolset\Common\M2M\PotentialAssociation;


/**
 * Handle the MySQL JOIN clause construction when augmenting the WP_Query in Toolset_Potential_Association_Query_Posts.
 *
 * Make sure that JOINs come in the right order and are not duplicated.
 *
 * Note that hook() and unhook() must be called around the WP_Query usage for proper function.
 *
 * @package OTGS\Toolset\Common\M2M\PotentialAssociation
 * @since 2.8
 */
class JoinManager extends \Toolset_Wpdb_User {


	/** @var \IToolset_Relationship_Definition */
	protected $relationship;


	/** @var \IToolset_Element */
	protected $for_element;


	/** @var \IToolset_Relationship_Role_Parent_Child */
	protected $target_role;


	/** @var \Toolset_WPML_Compatibility */
	protected $wpml_service;


	/** @var null|\Toolset_Relationship_Table_Name */
	private $_table_names;


	/** @var string[] Keywords determining what needs to be joined */
	private $tables_to_join = array();


	// Keywords for specific JOINs
	const JOIN_DEFAULT_POST_TRANSLATION = 'default_post_translation';
	const JOIN_ASSOCIATIONS_TABLE = 'associations_table';
	const JOIN_DEFAULT_LANG_ASSOCIATIONS = 'default_lang_associations';


	/**
	 * JoinManager constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param \IToolset_Relationship_Role_Parent_Child $target_role
	 * @param \IToolset_Element $for_element
	 * @param \Toolset_Relationship_Table_Name|null $table_names_di
	 * @param \wpdb|null $wpdb_di
	 * @param \Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		\IToolset_Relationship_Definition $relationship,
		\IToolset_Relationship_Role_Parent_Child $target_role,
		\IToolset_Element $for_element,
		\Toolset_Relationship_Table_Name $table_names_di = null,
		\wpdb $wpdb_di = null,
		\Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->_table_names = $table_names_di;
		$this->relationship = $relationship;
		$this->for_element = $for_element;
		$this->target_role = $target_role;
		$this->wpml_service = $wpml_service_di ?: \Toolset_WPML_Compatibility::get_instance();
	}


	public function hook() {
		// The priority is later so that we can add all the JOINs necessary at once.
		add_filter( 'posts_join', array( $this, 'add_join_clauses' ), 20 );
	}


	public function unhook() {
		remove_filter( 'posts_join', array( $this, 'add_join_clauses' ), 20 );
	}


	/**
	 * Indicate that a certain table (or tables) need to be joined.
	 *
	 * @param string $table_keyword One of the JOIN_ constants
	 */
	public function register_join( $table_keyword ) {
		if( in_array( $table_keyword, $this->tables_to_join ) ) {
			return;
		}

		$this->tables_to_join[] = $table_keyword;
	}


	/**
	 * Append a JOIN clause if it was not appended previously.
	 *
	 * @param string $join
	 * @param string $table_keyword
	 * @param string[] $already_joined Keywords of previously joined tables.
	 *
	 * @return string
	 */
	private function maybe_add_join( $join, $table_keyword, &$already_joined ) {
		if( in_array( $table_keyword, $already_joined ) ) {
			return $join;
		}

		switch( $table_keyword ) {
			case self::JOIN_DEFAULT_POST_TRANSLATION:
				$join = $this->join_default_post_translation( $join );
				break;
			case self::JOIN_ASSOCIATIONS_TABLE:
				$join = $this->join_associations_table( $join );
				break;
			case self::JOIN_DEFAULT_LANG_ASSOCIATIONS:
				$join = $this->join_association_table_on_default_language( $join, $already_joined );
				break;
		}

		$already_joined[] = $table_keyword;

		return $join;
	}


	/**
	 * Add JOINs for determining the default language version of the post.
	 *
	 * Note that the correct ID may be either default_lang_translation.element_id or wp_posts.ID, depending
	 * on whether the post is translatable & has a translation or not.
	 *
	 * @param string $join
	 * @return string Augmented JOIN clause.
	 */
	private function join_default_post_translation( $join ) {
		$icl_translations = $this->wpdb->prefix . 'icl_translations';
		$default_language = esc_sql( $this->wpml_service->get_default_language() );
		$posts_table_name = $this->wpdb->posts;

		$join .= $this->wpdb->prepare(
			" LEFT JOIN {$icl_translations} AS element_lang_info ON (
					{$posts_table_name}.ID = element_lang_info.element_id
					AND element_lang_info.element_type LIKE %s
				) LEFT JOIN {$icl_translations} AS default_lang_translation ON (
					element_lang_info.trid = default_lang_translation.trid
					AND default_lang_translation.language_code = %s
				) ",
			'post_%',
			$default_language
		);

		return $join;
	}


	/**
	 * Join an association table for a particular association (between the post in the row and a given source element).
	 *
	 * @param string $join
	 * @return string Augmented JOIN clause.
	 */
	private function join_associations_table( $join ) {
		$association_table = $this->get_table_names()->association_table();
		$posts_table_name = $this->wpdb->posts;
		$target_element_column = $this->target_role->get_name() . '_id';
		$for_element_column = $this->target_role->other() . '_id';

		$join .= $this->wpdb->prepare(
			" LEFT JOIN {$association_table} AS toolset_associations ON ( 
				toolset_associations.relationship_id = %d
				AND toolset_associations.{$target_element_column} = {$posts_table_name}.ID
				AND toolset_associations.{$for_element_column} = %d
			) ",
			$this->relationship->get_row_id(),
			$this->for_element->get_default_language_id()
		);

		return $join;
	}


	/**
	 * Same as in join_associations_table(), but this time for the default language version of the target post.
	 *
	 * @param string $join
	 * @param string[] $already_joined
	 *
	 * @return string
	 */
	private function join_association_table_on_default_language( $join, &$already_joined ) {
		$association_table = $this->get_table_names()->association_table();
		$target_element_column = $this->target_role->get_name() . '_id';
		$for_element_column = $this->target_role->other() . '_id';

		$join = $this->maybe_add_join( $join, self::JOIN_DEFAULT_POST_TRANSLATION, $already_joined );
		$join .= $this->wpdb->prepare(
			" LEFT JOIN {$association_table} AS default_lang_association ON (
					default_lang_association.relationship_id = %d
					AND default_lang_translation.element_id = default_lang_association.{$target_element_column}
					AND default_lang_association.{$for_element_column} = %d
				) ",
			'post_%',
			$this->relationship->get_row_id()
		);

		return $join;
	}


	protected function get_table_names() {
		if( null === $this->_table_names ) {
			$this->_table_names = new \Toolset_Relationship_Table_Name();
		}

		return $this->_table_names;
	}


	/**
	 * Add all registered JOINs to the JOIN clause.
	 *
	 * Note that this has to be idempotent since the filter may be applied several times within a single WP_Query instance.
	 *
	 * @param string $join
	 * @return string
	 */
	public function add_join_clauses( $join ) {
		$already_joined = array();
		foreach( $this->tables_to_join as $table_keyword ) {
			$join = $this->maybe_add_join( $join, $table_keyword, $already_joined );
		}
		return $join;
	}

}