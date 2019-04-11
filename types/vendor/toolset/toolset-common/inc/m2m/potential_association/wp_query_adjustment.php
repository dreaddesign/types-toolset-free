<?php

namespace OTGS\Toolset\Common\M2M\PotentialAssociation;

/**
 * Shared functionality for adjusting the WP_Query behaviour.
 *
 * @package OTGS\Toolset\Common\M2M\PotentialAssociation
 */
abstract class WpQueryAdjustment extends \Toolset_Wpdb_User {


	/** @var \IToolset_Relationship_Definition */
	protected $relationship;


	/** @var \IToolset_Element */
	protected $for_element;


	/** @var \IToolset_Relationship_Role_Parent_Child */
	protected $target_role;


	/** @var \Toolset_WPML_Compatibility */
	protected $wpml_service;


	/** @var JoinManager */
	protected $join_manager;

	/** @var null|\Toolset_Relationship_Table_Name */
	private $_table_names;


	/**
	 * Determine whether the WP_Query should be augmented.
	 *
	 * @return bool
	 */
	protected abstract function is_actionable();


	/**
	 * WpQueryAdjustment constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param \IToolset_Relationship_Role_Parent_Child $target_role
	 * @param \IToolset_Element $for_element
	 * @param JoinManager $join_manager
	 * @param \Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param \Toolset_Relationship_Table_Name|null $table_names_di
	 * @param \wpdb|null $wpdb_di
	 */
	public function __construct(
		\IToolset_Relationship_Definition $relationship,
		\IToolset_Relationship_Role_Parent_Child $target_role,
		\IToolset_Element $for_element,
		JoinManager $join_manager,
		\Toolset_WPML_Compatibility $wpml_service_di = null,
		\Toolset_Relationship_Table_Name $table_names_di = null,
		\wpdb $wpdb_di = null )
	{
		parent::__construct( $wpdb_di );
		$this->_table_names = $table_names_di;
		$this->relationship = $relationship;
		$this->for_element = $for_element;
		$this->target_role = $target_role;
		$this->wpml_service = $wpml_service_di ?: \Toolset_WPML_Compatibility::get_instance();
		$this->join_manager = $join_manager;
	}


	/**
	 * Hooks to filters in order to add extra clauses to the MySQL query.
	 */
	public function before_query() {
		if( ! $this->is_actionable() ) {
			return;
		}

		add_filter( 'posts_join', array( $this, 'add_join_clauses' ) );
		add_filter( 'posts_where', array( $this, 'add_where_clauses' ) );

		// WPML in the back-end filters strictly by the current language by default,
		// but we need it to include default language posts, too, if the translation to the current language
		// doesn't exist. This needs to behave consistently in all contexts.
		add_filter( 'wpml_should_use_display_as_translated_snippet', '__return_true' );
	}


	/**
	 * Cleanup - unhooks the filters added in before_query().
	 */
	public function after_query() {
		if( ! $this->is_actionable() ) {
			return;
		}

		remove_filter( 'posts_join', array( $this, 'add_join_clauses' ) );
		remove_filter( 'posts_where', array( $this, 'add_where_clauses' ) );

		remove_filter( 'wpml_should_use_display_as_translated_snippet', '__return_true' );
	}


	protected function get_table_names() {
		if( null === $this->_table_names ) {
			$this->_table_names = new \Toolset_Relationship_Table_Name();
		}

		return $this->_table_names;
	}


	protected function get_wpdb() {
		return $this->wpdb;
	}

}