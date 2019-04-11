<?php

/**
 * Factory for IToolset_Element.
 *
 * @since m2m
 * @since 2.5.10 The get_post() method is open to external use and can return the post translation
 *     set as well, if WPML is active. get_post_untranslated() can be used instead of the previous
 *     get_post() implementation.
 */
class Toolset_Element_Factory {


	/** @var Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured */
	private $is_wpml_active;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Toolset_Element_Factory constructor.
	 *
	 * @param Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured|null $is_wpml_active
	 * @param Toolset_WPML_Compatibility|null $wpml_service
	 */
	public function __construct(
		Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active = null,
		Toolset_WPML_Compatibility $wpml_service = null
	) {
		$this->is_wpml_active = ( null === $is_wpml_active ? new Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured() : $is_wpml_active );
		$this->wpml_service = ( null === $wpml_service ? Toolset_WPML_Compatibility::get_instance() : $wpml_service );
	}


	/**
	 * Get an element instance based on it's domain.
	 *
	 * @param string $domain Valid element domain as defined in Toolset_Field_Utils.
	 * @param mixed $object_source Source of the underlying object that will be recognized by the specific element
	 *     class. It also recognizes translation sets (array of sources, indexed by language code) for posts.
	 *
	 * @return IToolset_Element
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	public function get_element( $domain, $object_source ) {
		switch( $domain ) {
			case Toolset_Element_Domain::POSTS:
				return $this->get_post( $object_source );

			case Toolset_Element_Domain::TERMS:
			case Toolset_Element_Domain::USERS:
				throw new RuntimeException( 'Not implemented.' );
		}

		throw new InvalidArgumentException( 'Invalid domain name.' );
	}


	/**
	 * Instantiate the post.
	 *
	 * @param int|WP_Post $object_source
	 *
	 * @return IToolset_Post
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_post( $object_source ) {
		if( $this->is_wpml_active->is_met() ) {

			if( ! $object_source instanceof WP_Post
				|| $this->wpml_service->is_post_type_translatable( $object_source->post_type )
			) {
				// Either we don't know the post type yet or we know it is translatable.
				return $this->get_post_translation_set( $object_source );
			}
		}

		return $this->get_post_untranslated( $object_source );
	}


	/**
	 * Instantiate the post.
	 *
	 * This is an WPML-unaware version that will always return a Toolset_Post instance.
	 * Use only in cases where this is explicitly needed.
	 *
	 * Otherwise, for instantiating Toolset elements, you should use the get_element() method.
	 *
	 * @param int|WP_Post $object_source
	 * @param null|string $language_code Language of the post being created.
	 *     If it's passed here, it might save one WPML interaction later when requested.
	 *
	 * @return Toolset_Post
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_post_untranslated( $object_source, $language_code = null ) {
		return new Toolset_Post(
			$object_source, $language_code, null, null, $this
		);
	}


	/**
	 * Instantiate the post as a translation set.
	 *
	 * This method must not be used when WPML is not active, and there should be no reasonable need to use
	 * it instead of get_element() or get_post().
	 *
	 * @param array|WP_Post|int $object_source
	 *
	 * @return Toolset_Post_Translation_Set
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_post_translation_set( $object_source ) {
		if( ! is_array( $object_source ) ) {
			$object_source = array( $object_source );
		}
		return new Toolset_Post_Translation_Set( $object_source, $this->wpml_service, $this );
	}

}