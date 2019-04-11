<?php

/**
 * Helper class for migrating a single legacy association between two posts into m2m.
 *
 * Not to be used outside the m2m API.
 *
 * @since m2m
 */
class Toolset_Relationship_Migration_Associations {


	/** @var Toolset_Relationship_Definition_Repository */
	private $definition_repository;


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var Toolset_Potential_Association_Query_Factory */
	private $potential_association_query_factory;


	/** @var bool */
	private $create_default_language_if_missing;


	/** @var bool */
	private $copy_post_content_when_creating;


	/** @var bool */
	private $do_detailed_logging;


	/**
	 * Toolset_Relationship_Migration_Associations constructor.
	 *
	 * @param Toolset_Relationship_Definition_Repository $definition_repository
	 * @param bool $create_default_language_if_missing
	 * @param bool $copy_post_content_when_creating
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_Potential_Association_Query_Factory|null $potential_association_query_factory_di
	 * @param bool $do_detailed_logging
	 */
	public function __construct(
		Toolset_Relationship_Definition_Repository $definition_repository,
		$create_default_language_if_missing,
		$copy_post_content_when_creating,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_Potential_Association_Query_Factory $potential_association_query_factory_di = null,
		$do_detailed_logging = true
	) {
		$this->definition_repository = $definition_repository;
		$this->create_default_language_if_missing = (bool) $create_default_language_if_missing;
		$this->copy_post_content_when_creating = (bool) $copy_post_content_when_creating;
		$this->element_factory = $element_factory_di ?: new Toolset_Element_Factory();
		$this->potential_association_query_factory = $potential_association_query_factory_di ?: new Toolset_Potential_Association_Query_Factory();
		$this->do_detailed_logging = (bool) $do_detailed_logging;
	}


	/**
	 * @param int $parent_id
	 * @param int $child_id
	 * @param int $relationship_slug
	 *
	 * @return Toolset_Result
	 */
	public function migrate_association( $parent_id, $child_id, $relationship_slug ) {

		try {
			$relationship_definition = $this->definition_repository->get_definition( $relationship_slug );

			if( null == $relationship_definition ) {
				throw new RuntimeException( sprintf( __( 'Relationship definition "%s" not found.', 'wpcf' ), $relationship_slug ) );
			}

			// We specifically require individual posts (element_factory->get_post_untranslated())
			// and not translation sets (which we might get when using element_factory->get_post()).
			//
			// Here, we create an association between two specific posts, no matter their language.
			// The m2m API always creates the association between default language posts, or fails if unable
			// to do so.
			//
			// If these posts are non-default language versions of already associated posts,
			// another association will not be created.
			$parent = $this->element_factory->get_post_untranslated( $parent_id );
			$child = $this->element_factory->get_post_untranslated( $child_id );

			// Note: This might throw exceptions in some obscure cases, like trying to connect
			// posts of type that don't belong to the correct relationship.
			//
			// Imagine the following situation:
			// - post A of type "type-a" with ID "42"
			// - post B of type "type-b"
			// - post B has has a postmeta "_wpcf_belongs_type-x_id" with value "42"
			//
			// When migrating legacy relationships, we'll read post B and
			// create a new relationship based on the combination of two post types: "type-b", which is the post type
			// of the currently processed relationship, and "type-x", which is the parent post type, judging
			// from the postmeta value. That will create a "type-x-type-b" relationship.
			//
			// Then, we will try to connect post B with a post with the ID "42" (post A) in a relationship "type-x-type-b".
			// But this will not be accepted by the IToolset_Potential_Association_Query at all, because post A
			// has a different post type than "type-x". And voilá... an exception.
			$potential_association_query = $this->potential_association_query_factory->create(
				$relationship_definition,
				new Toolset_Relationship_Role_Child(),
				$parent
			);

			// Specifically check whether the element is already associated. This can happen only when
			// trying to create the same association for posts in different languages, and we want to skip
			// those cases without triggering a warning.
			if( $potential_association_query->is_element_already_associated( $child ) ) {
				return new Toolset_Result(
					true,
					sprintf(
						__( 'Skipping the association between posts #%d (%s) and #%d (%s), because these elements are already associated. This can happen when migrating post translations.', 'wpcf' ),
						$parent_id,
						esc_textarea( $parent->get_title() ),
						$child_id,
						esc_textarea( $child->get_title() )
					)
				);
			}

			$results = new Toolset_Result_Set();

			// Handle posts without default language versions (if applicable).
			//
			// If the situation couldn't be handled according to user's choice, skip the current association
			// and report the issue immediately.
			//
			// Otherwise, we'll print the (positive) output after the association is created successfully.
			$parent_default_lang_check = $this->check_default_language_version( $parent, $parent, $child );
			if( ! $parent_default_lang_check->is_success() ) {
				return $parent_default_lang_check;
			}
			$results->add( $parent_default_lang_check );

			$child_default_lang_check = $this->check_default_language_version( $child, $parent, $child );
			if( ! $child_default_lang_check->is_success() ) {
				return $child_default_lang_check;
			}
			$results->add( $child_default_lang_check );

			// Check for other cases where it's not allowed to create this association.
			// Those we will report as problems.
			$can_associate = $potential_association_query->check_single_element( $child, false );
		} catch( Exception $e ) {
			$display_message = sprintf(
				__( 'Unable to migrate an association from post #%d to #%d to a relationship "%s"', 'wpcf'),
				$parent_id,
				$child_id,
				$relationship_slug
			);
			return new Toolset_Result( $e, $display_message );
		}

		if( ! $can_associate->is_success() ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'The association between posts #%d (%s) and #%d (%s) in the relationship "%s" is not allowed: ', 'wpcf' ),
					$parent->get_id(),
					esc_textarea( $parent->get_title() ),
					$child->get_id(),
					esc_textarea( $child->get_title() ),
					$relationship_slug
				)
				. $can_associate->get_message()
			);
		}

		try {
			$association = $relationship_definition->create_association( $parent_id, $child_id );
		} catch( Exception $e ) {
			return new Toolset_Result( $e );
		}

		if( $association instanceof Toolset_Result ) {
			$message = ( $association->has_message() ? $association->get_message() : __( 'Error while saving an association to database', 'wpcf' ) );
			return new Toolset_Result(
				false,
				sprintf( "%s\n\tparent: #%d (%s)\n\tchild: #%d (%s)\n\trelationship: \"%s\"",
					$message,
					$parent->get_id(),
					esc_textarea( $parent->get_title() ),
					$child->get_id(),
					esc_textarea( $child->get_title() ),
					$relationship_slug
				)
			);
		}

		// Happy end!
		if( $this->do_detailed_logging ) {
			$results->add(
				true,
				sprintf(
					__( 'Connected #%d (%s) and #%d (%s) in the relationship "%s".', 'wpcf' ),
					$parent->get_id(),
					$parent->get_title(),
					$child->get_id(),
					$child->get_title(),
					$relationship_slug
				)
			);
		} else {
			$results->add( true );
		}

		return $results->aggregate( Toolset_Relationship_Migration_Controller::MESSAGE_SEPARATOR );
	}


	/**
	 * @param IToolset_Post $post The post to check.
	 * @param IToolset_Post $parent Parent post of the association (for logging purposes).
	 * @param IToolset_Post $child Child post of the association (for logging purposes).
	 *
	 * @return Toolset_Result
	 */
	private function check_default_language_version( IToolset_Post $post, IToolset_Post $parent, IToolset_Post $child ) {
		$translation_migration = new Toolset_Relationship_Migration_Post_Translation(
			$post, $parent, $child, $this->create_default_language_if_missing, $this->copy_post_content_when_creating
		);
		return $translation_migration->run();
	}

}
