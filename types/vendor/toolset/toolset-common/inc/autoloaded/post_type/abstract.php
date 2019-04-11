<?php

/**
 * Shared functionality for all kinds of post types.
 *
 * @since 2.5.10
 */
abstract class Toolset_Post_Type_Abstract implements IToolset_Post_Type {


	/** @var Toolset_WPML_Compatibility|null */
	private $_wpml_compatibility;


	/** @var Toolset_Relationship_Query_Factory|null */
	private $_relationship_definition_query_factory;


	/**
	 * Toolset_Post_Type_Abstract constructor.
	 *
	 * @param Toolset_WPML_Compatibility|null $wpml_compatibility_di
	 * @param null|Toolset_Relationship_Query_Factory $relationship_definition_query_factory_di
	 */
	public function __construct(
		Toolset_WPML_Compatibility $wpml_compatibility_di = null,
		$relationship_definition_query_factory_di = null
	) {
		$this->_wpml_compatibility = $wpml_compatibility_di;
		$this->_relationship_definition_query_factory = $relationship_definition_query_factory_di;
	}


	/**
	 * @return Toolset_WPML_Compatibility
	 */
	protected function get_wpml_compatibility() {
		if( null === $this->_wpml_compatibility ) {
			$this->_wpml_compatibility = Toolset_WPML_Compatibility::get_instance();
		}
		return $this->_wpml_compatibility;
	}


	/**
	 * @return Toolset_Relationship_Query_V2
	 */
	protected function get_relationship_definition_query() {
		if( null === $this->_relationship_definition_query_factory ) {
			if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				throw new InvalidArgumentException( 'Trying to use m2m functionality without m2m enabled.' );
			}

			do_action( 'toolset_do_m2m_full_init' );

			$this->_relationship_definition_query_factory = new Toolset_Relationship_Query_Factory();
		}

		return $this->_relationship_definition_query_factory->relationships_v2();
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_translatable() {
		return $this->get_wpml_compatibility()->is_post_type_translatable( $this->get_slug() );
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_in_display_as_translated_mode() {
		return $this->get_wpml_compatibility()->is_post_type_display_as_translated( $this->get_slug() );
	}

	/**
	 * @inheritdoc
	 * @return Toolset_Result
	 */
	public function can_be_used_in_relationship() {
		if ( 'attachment' == $this->get_slug() ) {
			// Media post type can be used in relationships.
			return new Toolset_Result( false, __( 'Media post type can not be part of a relationship.') );
		}

		if( ! $this->get_wpml_compatibility()->is_wpml_active_and_configured() ) {
			// no wpml = no limitations on relationships
			return new Toolset_Result( true );
		}

		if( ! $this->is_translatable() ) {
			// no translation mode selected = all good
			return new Toolset_Result( true );
		}

		if( $this->is_in_display_as_translated_mode() ) {
			// "display as translated" mode selected = all good
			return new Toolset_Result( true );
		}

		// at the end, we've decided to allow any translation mode for relationships
		return new Toolset_Result( true );
		// return new Toolset_Result( false, __( 'This post type uses the <strong>Translatable - only show translated items</strong> WPML translation mode. In order to use it in a relationship, switch to <strong>Translatable - use translation if available or fallback to default language</strong> mode.', 'wpcf' ) );
	}


	/**
	 * @inheritdoc
	 *
	 * Note: This operation may be rather expensive.
	 *
	 * @return bool
	 */
	public function is_involved_in_relationship() {
		if( $this->is_intermediary() ) {
			return true;
		}

		$query = $this->get_relationship_definition_query();

		$results = $query
			->add( $query->has_domain_and_type( $this->get_slug(), Toolset_Element_Domain::POSTS ) )
			->get_results();

		return ( count( $results ) > 0 );
	}

}
