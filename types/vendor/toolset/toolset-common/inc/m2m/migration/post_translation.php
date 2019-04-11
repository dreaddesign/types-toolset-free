<?php

/**
 * Handle the creation of a default languge post translation during the m2m migration.
 *
 * The behaviour depends on user's settings passed to the constructor.
 *
 * @since 2.5.11
 */
class Toolset_Relationship_Migration_Post_Translation {


	/** @var IToolset_Post */
	private $post;


	/** @var IToolset_Post */
	private $parent;


	/** @var IToolset_Post */
	private $child;


	/** @var bool */
	private $create_default_language_if_missing;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var bool */
	private $copy_post_content_when_creating;


	/**
	 * Toolset_Relationship_Migration_Post_Translation constructor.
	 *
	 * @param IToolset_Post $post
	 * @param IToolset_Post $parent
	 * @param IToolset_Post $child
	 * @param $create_default_language_if_missing
	 * @param $copy_post_content_when_creating
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		IToolset_Post $post, IToolset_Post $parent, IToolset_Post $child,
		$create_default_language_if_missing,
		$copy_post_content_when_creating,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->post = $post;
		$this->parent = $parent;
		$this->child = $child;
		$this->create_default_language_if_missing = (bool) $create_default_language_if_missing;
		$this->copy_post_content_when_creating = (bool) $copy_post_content_when_creating;
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
	}


	/**
	 * Check for the missing translation and either create it or report a failure, depending
	 * on the user's choice.
	 *
	 * @return Toolset_Result
	 */
	public function run() {
		if( ! $this->is_missing_default_language_version( $this->post ) ) {
			return new Toolset_Result( true );
		}

		if( ! $this->create_default_language_if_missing ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Skipping the association between posts #%d (%s) and #%d (%s) because #%d doesn\'t have a default language version and you chose to skip such associations.', 'wpcf' ),
					$this->parent->get_id(),
					$this->parent->get_title(),
					$this->child->get_id(),
					$this->child->get_title(),
					$this->post->get_id()
				)
			);
		}

		return $this->create_default_language_version( $this->post );
	}


	/**
	 * @param IToolset_Post $post
	 * @return bool
	 */
	private function is_missing_default_language_version( IToolset_Post $post ) {
		if( ! $post->is_translatable() ) {
			return false;
		}
		$default_language_id = $post->get_default_language_id();
		return ( 0 === $default_language_id );
	}


	/**
	 * @param IToolset_Post $post
	 * @return Toolset_Result
	 */
	private function create_default_language_version( IToolset_Post $post ) {
		if( $this->copy_post_content_when_creating ) {
			return $this->create_duplicate_default_language_version( $post );
		}

		return $this->create_clean_default_language_version( $post );
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return Toolset_Result
	 */
	private function create_clean_default_language_version( IToolset_Post $post ) {
		// Create the new post, an empty draft with a similar title as the original.
		$result = wp_insert_post(
			array(
				'post_title' => sprintf(
					'[%s] %s',
					$this->wpml_service->get_default_language(),
					$post->get_title()
				),
				'post_content' => '',
				'post_status' => 'draft',
				'post_author' => $post->get_author(),
				'post_type' => $post->get_type()
			),
			true
		);

		if( $result instanceof WP_Error ) {
			return new Toolset_Result( $result );
		}

		$default_lang_post_id = (int) $result;

		// Set the language of the new post and connect it to the original.
		$this->wpml_service->add_post_translation( $post, $default_lang_post_id, $this->wpml_service->get_default_language() );

		// Unfortunately, there is no return value from the WPML hook (it's an action), we have to assume it went through.
		return new Toolset_Result(
			true,
			sprintf(
				__( 'Created a default language translation for post #%d (%s) in draft mode.', 'wpcf' ),
				$post->get_id(),
				$post->get_title()
			)
		);
	}


	/**
	 * @param IToolset_Post $post
	 *
	 * @return Toolset_Result
	 */
	private function create_duplicate_default_language_version( IToolset_Post $post ) {

		// First, create the duplicate (but don't mark it as an actual WPML duplicate to prevent value synchronization)
		$duplicate_id = $this->wpml_service->create_post_duplicate(
			$post, $this->wpml_service->get_default_language(), false
		);

		if( ! $duplicate_id ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Unable to create a default language translation for post #%d (%s).', 'wpcf' ),
					$post->get_id(),
					$post->get_title()
				)
			);
		}

		// Adjust the title and turn it into a draft.
		$result = wp_update_post(
			array(
				'ID' => $duplicate_id,
				'post_title' => sprintf(
					'[%s] %s',
					$this->wpml_service->get_default_language(),
					$post->get_title()
				),
				'post_status' => 'draft',
			),
			true
		);


		if( $result instanceof WP_Error ) {
			return new Toolset_Result( $result );
		}

		return new Toolset_Result(
			true,
			sprintf(
				__( 'Created a default language translation for post #%d (%s) in draft mode.', 'wpcf' ),
				$post->get_id(),
				$post->get_title()
			)
		);
	}


}