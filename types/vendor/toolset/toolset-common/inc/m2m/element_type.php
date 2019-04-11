<?php

/**
 * Defines an element type that can take a role in a relationship.
 *
 * It encapsulates the domain of the element and its types within the domain. It also hides the polymorphism away
 * in a lower abstraction level.
 *
 * @since m2m
 */
class Toolset_Relationship_Element_Type {

	
	/** @var string One of the DOMAIN_* values. */
	private $domain;

	
	/** @var string[] Possible element types within the domain. */
	private $types;


	// Currently, only posts are supported.

	/**
	 * @deprecated Use Toolset_Element_Domain::POSTS instead.
	 */
	const DOMAIN_POSTS = 'posts';

	
	// Constants for the entity type definition array.
	const DA_DOMAIN = 'domain';
	const DA_TYPES = 'types';


	/**
	 * Toolset_Relationship_Element_Type constructor.
	 *
	 * @param string[] $element_type_definition Element type definition array, which is usually part of the relationship
	 *     definition array. If you need to create a new instance from scratch, consider using one of the static 
	 *     helper methods.
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function __construct( $element_type_definition ) {

		$domain = toolset_getarr( $element_type_definition, self::DA_DOMAIN );

		$available_domains = self::get_available_domains();
		if( !in_array( $domain, $available_domains ) ) {
			throw new InvalidArgumentException( 'Invalid domain provided.' );
		}
		$this->domain = $domain;

		$types = toolset_getarr( $element_type_definition, self::DA_TYPES );
		if( !is_array( $types ) ) {
			$types = array( $types );
		}
		if( empty( $types ) ) {
			throw new InvalidArgumentException( 'No types provided.' );
		}
		foreach( $types as $type ) {
			if( sanitize_title( $type ) != $type ) {
				throw new InvalidArgumentException( 'Invalid type provided' );
			}
		}
		$this->types = $types;
	}


	/**
	 * Get entity domain.
	 * 
	 * @return string
	 * @since m2m
	 */
	public function get_domain() { return $this->domain; }


	/**
	 * Retrieve an array of available domains.
	 * 
	 * @return string[]
	 * @since m2m
	 */
	private static function get_available_domains() {
		return array( Toolset_Element_Domain::POSTS );
	}


	/**
	 * @return string[]
	 */
	public function get_types() { return $this->types; }


	/**
	 * Determine if this is a polymorphic entity.
	 * 
	 * @return bool
	 * @since m2m
	 */
	public function is_polymorphic() {
		return ( count( $this->types ) > 1 );
	}


	/**
	 * Build a definition array for persisting in the database.
	 * 
	 * It should not be used for other reasons.
	 * 
	 * @return array
	 * @since m2m
	 */
	public function get_definition_array() {
		return array(
			self::DA_DOMAIN => $this->get_domain(),
			self::DA_TYPES => $this->get_types()
		);
	}


	/**
	 * Create an instance for a single post type - the simplest  and most common scenario.
	 * 
	 * @param string $post_type_slug Valid post type slug.
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public static function build_for_post_type( $post_type_slug ) {
		return new self(
			array(
				self::DA_DOMAIN => Toolset_Element_Domain::POSTS,
				self::DA_TYPES => array( $post_type_slug )
			)
		);
	}


	/**
	 * Determine whether an element matches this type.
	 *
	 * @param IToolset_Element $element
	 * @return bool
	 * @since m2m
	 */
	public function is_match( $element ) {

		if( ! $element instanceof IToolset_Element ) {
			throw new InvalidArgumentException( 'Invalid element provided.' );
		}

		if( $element->get_domain() !== $this->get_domain() ) {
			return false;
		}

		// If the domain matches, we'll check by the type (where applicable).
		switch( $this->get_domain() ) {
			case Toolset_Element_Domain::POSTS:
				/** @var IToolset_Post $post */
				$post = $element;
				return in_array( $post->get_type(), $this->get_types() );
			default:
				throw new RuntimeException( 'Not implemented.' );
		}
	}


	/** @var null|bool Cache for is_translatable(). */
	private $is_translatable = null;


	/**
	 * Check whether an element matching this type definition can be translatable.
	 *
	 * For polymorphic post type definitions, true is returned if at least one post type is translatable.
	 *
	 * The result is cached for performance reasons.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_translatable() {

		if( null === $this->is_translatable ) {

			$this->is_translatable = false;

			if( Toolset_Element_Domain::POSTS === $this->get_domain() ) {
				foreach( $this->get_types() as $post_type_slug ) {
					if( Toolset_Wpml_Utils::is_post_type_translatable( $post_type_slug ) ) {
						$this->is_translatable = true;
						break;
					}
				}
			}

			// We might need to implement this for terms in the future, which in fact can be translatable.

		}

		return $this->is_translatable;
	}
}