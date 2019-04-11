<?php

/**
 * Validate the relationship slug before renaming.
 *
 * @since m2m
 */
class Toolset_Relationship_Slug_Validator {

	private $slug_candidate;
	private $relationship_to_rename;
	private $definition_repository;


	/**
	 * Toolset_Relationship_Slug_Validator constructor.
	 *
	 * @param string $slug_candidate
	 * @param Toolset_Relationship_Definition $relationship_to_rename
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di
	 */
	public function __construct(
		$slug_candidate,
		Toolset_Relationship_Definition $relationship_to_rename,
		Toolset_Relationship_Definition_Repository $definition_repository_di = null ) {

		if(
			! $relationship_to_rename instanceof Toolset_Relationship_Definition
			|| ! is_string( $slug_candidate )
			|| empty( $slug_candidate )
		) {
			throw new InvalidArgumentException();
		}

		$this->slug_candidate = $slug_candidate;
		$this->relationship_to_rename = $relationship_to_rename;
		$this->definition_repository = ( null === $definition_repository_di ? Toolset_Relationship_Definition_Repository::get_instance() : $definition_repository_di );
	}


	/**
	 * Validate and return the result with a user-friendly message.
	 *
	 * @return Toolset_Result
	 */
	public function validate() {

		$is_slug_too_long = ( strlen( $this->slug_candidate ) > Toolset_Relationship_Database_Operations::MAXIMUM_RELATIONSHIP_SLUG_LENGTH );
		if( $is_slug_too_long ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Unable to rename the relationship slug "%s" to "%s" because it is too long. The maximum allowed length is %d characters.', 'wpcf' ),
					$this->relationship_to_rename->get_slug(),
					$this->slug_candidate,
					Toolset_Relationship_Database_Operations::MAXIMUM_RELATIONSHIP_SLUG_LENGTH
				)
			);
		}

		$is_slug_sanitized = ( sanitize_title( $this->slug_candidate ) === $this->slug_candidate );
		if( ! $is_slug_sanitized ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Unable to rename the relationship slug "%s" to "%s" because only lowercase letters, numbers, underscores and dashes are allowed.', 'wpcf' ),
					$this->relationship_to_rename->get_slug(),
					esc_html( $this->slug_candidate )
				)
			);
		}

		$is_already_used = ( $this->definition_repository->get_definition( $this->slug_candidate ) !== null );
		if( $is_already_used ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Unable to rename the relationship slug "%s" to "%s" because it is already in use.', 'wpcf' ),
					$this->relationship_to_rename->get_slug(),
					esc_html( $this->slug_candidate )
				)
			);
		}

		return new Toolset_Result( true, __( 'The relationship slug can be renamed.', 'wpcf' ) );
	}
}