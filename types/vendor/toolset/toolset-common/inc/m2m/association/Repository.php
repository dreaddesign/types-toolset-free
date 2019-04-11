<?php


namespace OTGS\Toolset\Common\M2M\Association;


/**
 * Class Repository
 *
 * This is useful when you know you will have to do a lot of association request.
 * Instead of doing a lot of small database queries you load them to this repository by doing bigger queries
 * and use this for the smaller afterwards request instead of asking the database.
 *
 * Example of usage: Types_Import_Export::wp_export_before()
 *
 * It can also be (ab)used as a container of Assocation/Relationship_Query and Roles
 * (to make sure you can still do very specific requests, without the need of additional injection).
 * Always consider to extend this Repository class instead of abusing it as a container.
 *
 * @package OTGS\Toolset\Types\Relationship\Association
 */
class Repository {
	/** @var \Toolset_Relationship_Role_Parent */
	private $role_parent;

	/** @var \Toolset_Relationship_Role_Child */
	private $role_child;

	/** @var \Toolset_Relationship_Role_Intermediary */
	private $role_intermediary;

	/** @var \Toolset_Relationship_Query_Factory */
	private $query_factory;

	/** @var \Toolset_Element_Domain */
	private $element_domain;

	/** @var \Toolset_Association[] */
	private $associations = array();

	/** @var array log of what's already loaded */
	private $alreadyLoaded = array(
		'posttype' => array(),
		'child' => array()
	);

	/**
	 * Repository constructor.
	 *
	 * @param \Toolset_Relationship_Query_Factory $query_factory
	 * @param \Toolset_Relationship_Role_Parent $role_parent
	 * @param \Toolset_Relationship_Role_Child $role_child
	 * @param \Toolset_Relationship_Role_Intermediary $role_Intermediary
	 * @param \Toolset_Element_Domain $element_domain
	 */
	public function __construct(
		\Toolset_Relationship_Query_Factory $query_factory,
		\Toolset_Relationship_Role_Parent $role_parent,
		\Toolset_Relationship_Role_Child $role_child,
		\Toolset_Relationship_Role_Intermediary $role_Intermediary,
		\Toolset_Element_Domain $element_domain
	) {
		$this->query_factory      = $query_factory;
		$this->role_parent        = $role_parent;
		$this->role_child         = $role_child;
		$this->role_intermediary  = $role_Intermediary;
		$this->element_domain     = $element_domain;
	}

	/**
	 * @param \IToolset_Post $toolset_post
	 *
	 * @return \Toolset_Association[]
	 * @throws \Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function getAssociationsByChildPost( \IToolset_Post $toolset_post ) {
		$result = array();

		foreach( $this->associations as $association ){
			if( $association->get_element_id( $this->getRoleChild() ) == $toolset_post->get_id() ) {
				$result[$association->get_uid()] = $association;
			}
		}

		return $result;
	}

	/**
	 * @param \IToolset_Association $association
	 */
	public function addAssociation( \IToolset_Association $association ) {
		$this->associations[ $association->get_uid() ] = $association;
	}

	public function addAssociationsByChild( \IToolset_Element $child ) {
		if( isset( $this->alreadyLoaded['child'][ $child->get_id() ] ) ) {
			// Associations of $child already applied
			return;
		}

		// get associations by $child
		$qry = $this->getAssociationQuery();

		$associations = $qry
			->add( $qry->element( $child, $this->getRoleChild(), false, false ) )
			->get_results();

		foreach( $associations as $association ) {
			$this->addAssociation( $association );
		}

		// mark $child asoociations $
		$this->alreadyLoaded['child'][ $child->get_id() ] = true;
	}

	/**
	 * Load associations by given post type
	 * This methods also tracks what's already loaded, means you don't need to care about loading associations
	 * of a post type more than once.
	 *
	 * @param \IToolset_Post_Type $post_type
	 */
	public function addAssociationsByPostType( \IToolset_Post_Type $post_type ) {
		if( isset( $this->alreadyLoaded['posttype'][ $post_type->get_slug() ] ) ) {
			// Associations of $post_type already applied
			return;
		}

		// extra assignment is needed because PHP 5.3 does not support: $this->var::CONST, but $var::CONST is fine
		$element_domain = $this->element_domain;

		$qry = $this->getRelationshipQuery();
		$relationships = $qry
			->add( $qry->has_domain_and_type( $post_type->get_slug(), $element_domain::POSTS ) )
			->add( $qry->origin( null ) )
			->get_results();

		foreach( $relationships as $relationship ) {
			$qry = $this->getAssociationQuery();
			$associations = $qry
				->add( $qry->relationship( $relationship ) )
				->get_results();

			foreach( $associations as $association ) {
				$this->addAssociation( $association );
			}
		}

		// mark $post_type as loaded
		$this->alreadyLoaded['posttype'][ $post_type->get_slug() ] = true;
	}

	/**
	 * @param int|null $limit Optional (the best feature of Toolset_Association_Query is back)
	 *
	 * @return \Toolset_Association_Query_V2
	 */
	public function getAssociationQuery( $limit = null ) {
		$qry = $this->query_factory->associations_v2();
		$qry->limit( $limit ?: 999999999 );
		return $qry;
	}

	/**
	 * @return \Toolset_Relationship_Query_V2
	 */
	public function getRelationshipQuery() {
		return $this->query_factory->relationships_v2();
	}

	/**
	 * @return \Toolset_Relationship_Role_Parent
	 */
	public function getRoleParent() {
		return $this->role_parent;
	}

	/**
	 * @return \Toolset_Relationship_Role_Child
	 */
	public function getRoleChild() {
		return $this->role_child;
	}

	/**
	 * @return \Toolset_Relationship_Role_Intermediary
	 */
	public function getRoleIntermediary() {
		return $this->role_intermediary;
	}


}