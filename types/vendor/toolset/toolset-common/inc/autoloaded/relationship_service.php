<?php

/**
 * Class Toolset_Relationship_Service
 *
 * Most provided services here require m2m and are useless if "toolset_is_m2m_enabled" is false.
 *
 * @since 2.5.2
 */
class Toolset_Relationship_Service {
	/**
	 * @var bool
	 */
	private $m2m_enabled;

	/**
	 * @return bool
	 */
	private function is_m2m_enabled() {
		if( $this->m2m_enabled === null ) {
			$this->m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );

			if( $this->m2m_enabled ) {
				do_action( 'toolset_do_m2m_full_init' );
			}
		}

		return $this->m2m_enabled;
	}

	/**
	 * @param $string
	 *
	 * @return false|IToolset_Relationship_Definition
	 */
	public function find_by_string( $string ) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}

		Toolset_Relationship_Controller::get_instance()->initialize_full();
		$factory = Toolset_Relationship_Definition_Repository::get_instance();

		if ( $relationship = $factory->get_definition( $string ) ) {
			return $relationship;
		}

		return false;
	}

	/**
	 * Function to find parend id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $child_id
	 * @param null $parent_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 */
	public function find_parent_id_by_relationship_and_child_id(
		IToolset_Relationship_Definition $relationship,
		$child_id,
		$parent_slug = null
	) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );

		if ( $parent_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $parent_slug, new Toolset_Relationship_Role_Parent() ) );
		}
		
		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find parent ID by relationship and intermediary post ID
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $intermediary_post_id
	 * @param null $parent_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 * @since 2.6.7
	 */
	public function find_parent_id_by_relationship_and_intermediary_post_id(
		IToolset_Relationship_Definition $relationship,
		$intermediary_post_id,
		$parent_slug = null
	) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->intermediary_id( $intermediary_post_id ) );

		if ( $parent_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $parent_slug, new Toolset_Relationship_Role_Parent() ) );
		}
		
		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}


	/**
	 * Function to find parend id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $parent_id
	 * @param null $child_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 */
	public function find_child_id_by_relationship_and_parent_id(
		IToolset_Relationship_Definition $relationship,
		$parent_id,
		$child_slug = null
	) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );

		if ( $child_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $child_slug, new Toolset_Relationship_Role_Child() ) );
		}
		
		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find intermediary post id by relationship and child id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $child_id
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 */
	public function find_intermediary_by_relationship_and_child_id( IToolset_Relationship_Definition $relationship, $child_id ) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );
		
		$results = $query
			->return_association_instances()
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find Child ID by relationship and intermediary post ID
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $intermediary_post_id
	 * @param null $child_slug
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 * @since 2.6.7
	 */
	public function find_child_id_by_relationship_and_intermediary_post_id(
		IToolset_Relationship_Definition $relationship,
		$intermediary_post_id,
		$child_slug = null
	) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->intermediary_id( $intermediary_post_id ) );

		if ( $child_slug ) {
			$query->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $child_slug, new Toolset_Relationship_Role_Child() ) );
		}
		
		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * Function to find intermediary post id by relationship and parent id
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param $parent_id
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 *
	 */
	public function find_intermediary_by_relationship_and_parent_id( IToolset_Relationship_Definition $relationship, $parent_id ) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->relationship( $relationship ) );
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );
		
		$results = $query
			->return_association_instances()
			->limit( 1 )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * @param $qry_args
	 *
	 * @return bool|int[]|IToolset_Association[]|IToolset_Element[]
	 */
	private function query_association( $qry_args ) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}

		Toolset_Relationship_Controller::get_instance()->initialize_full();
		$query   = new Toolset_Association_Query( $qry_args );
		$results = $query->get_results();

		if ( ! $results || empty( $results ) ) {
			return false;
		}

		return $results;
	}

	/**
	 * @param $parent_id
	 * @param array $children_args
	 *
	 * @return bool|int[]
	 * @internal param string $child_slug
	 */
	public function find_children_ids_by_parent_id( $parent_id, $children_args = array() ) {
		if( ! $this->is_m2m_enabled() ) {
			return false;
		}
		
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );
		
		$results = $query
			->return_element_ids( new Toolset_Relationship_Role_Child() )
			->limit( PHP_INT_MAX )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return false;
		}
		
		return $results;
	}

	/**
	 * @param $post_id
	 *
	 * @return IToolset_Association[]
	 */
	public function find_associations_by_id( $post_id ) {
		if( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$associations_parent = $this->find_associations_by_parent_id( $post_id );
		$associations_child = $this->find_associations_by_child_id( $post_id );

		return array_merge( $associations_parent, $associations_child );
	}

	/**
	 * Find associations (IToolset_Associations[]) by parent id
	 *
	 * @param $parent_id
	 *
	 * @return IToolset_Association[]
	 */
	private function find_associations_by_parent_id( $parent_id ) {
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->parent_id( $parent_id, Toolset_Element_Domain::POSTS ) );
		
		$results = $query
			->return_association_instances()
			->limit( PHP_INT_MAX )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Find associations (IToolset_Associations[]) by child id
	 *
	 * @param $child_id
	 *
	 * @return IToolset_Association[]
	 */
	private function find_associations_by_child_id( $child_id ) {
		$query = new Toolset_Association_Query_V2();
		
		$query->add( $query->child_id( $child_id, Toolset_Element_Domain::POSTS ) );
		
		$results = $query
			->return_association_instances()
			->limit( PHP_INT_MAX )
			->get_results();
		
		if ( ! $results || empty( $results ) ) {
			return array();
		}

		return $results;
	}

	/**
	 * Function to find parents (Toolset_Element[]) by child id and parent slug.
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return Toolset_Element[]
	 */
	public function find_parents_by_child_id_and_parent_slug( $child_id, $parent_slug ) {
		if( ! $this->is_m2m_enabled() ) {
			return array();
		}

		$associations = $this->find_associations_by_child_id( $child_id );
		$associations_matched = array();

		foreach( $associations as $association ) {
			$parent = $association->get_element( Toolset_Relationship_Role::PARENT );
			$parent_underlying_obj = $parent->get_underlying_object();

			if( ! property_exists( $parent_underlying_obj, 'post_type' ) ) {
				// only post elements supported
				continue;
			}

			if( $parent_underlying_obj->post_type == $parent_slug ) {
				$associations_matched[] = $parent;
			}
		}

		return $associations_matched;
	}

	/**
	 * Function uses legacy structure to find parent id by child id and parent slug.
	 * NOTE: always check "m2m" relationship table before you try to find a legacy relationship
	 *
	 * @param $child_id
	 * @param $parent_slug
	 *
	 * @return bool|int
	 */
	public function legacy_find_parent_id_by_child_id_and_parent_slug( $child_id, $parent_slug ) {
		if( $this->is_m2m_enabled() ) {
			return false;
		}

		$parent_slug = sanitize_title( $parent_slug );

		$option_key = '_wpcf_belongs_' . $parent_slug . '_id';

		return get_post_meta( $child_id, $option_key, false );
	}
}