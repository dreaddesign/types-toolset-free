<?php

/**
 * Model of a WordPress post.
 *
 * Simplifies the access to field instances and associations.
 *
 * Always use Toolset_Element_Factory to instantiate this class.
 *
 * @since m2m
 */
class Toolset_Post extends Toolset_Element implements IToolset_Post {


	// FIXME document this
	const SORTORDER_META_KEY = 'toolset-post-sortorder';


	/** @var WP_Post */
	private $post;


	/** @var string Language code of the current post or an empty string if unknown or not applicable. */
	private $language_code = null;


	private $_post_type_repository;

	private $element_factory;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Toolset_Element constructor.
	 *
	 * @param mixed|int $object_source The underlying object or its ID.
	 * @param string|null $language_code Post's language. An empty string will be interpreted as
	 *     "this post has no language", while null can be passed if this unknown (and it will be
	 *     determined first time it's needed).
	 * @param null|Toolset_Field_Group_Post_Factory $group_post_factory DI for phpunit
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	public function __construct(
		$object_source,
		$language_code = null,
		$group_post_factory = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->_post_type_repository = $post_type_repository_di;

		if( Toolset_Utils::is_natural_numeric( $object_source ) ) {
			$post = WP_Post::get_instance( $object_source );
		} else {
			$post = $object_source;
		}

		if( ! $post instanceof WP_Post ) {
			throw new Toolset_Element_Exception_Element_Doesnt_Exist(
				Toolset_Element_Domain::POSTS,
				$object_source
			);
		}

		if( ! is_string( $language_code ) && null !== $language_code ) {
			throw new InvalidArgumentException( 'Invalid language code provided.' );
		}

		parent::__construct( $post, $group_post_factory );

		$this->post = $post;
		$this->language_code = $language_code;
		$this->element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory() : $element_factory_di );
		$this->wpml_service = ( null === $wpml_service_di ? Toolset_WPML_Compatibility::get_instance() : $wpml_service_di );
	}


	/**
	 * Instantiate the post.
	 *
	 * To be used only within m2m API. For instantiating Toolset elements, you should
	 * always use Toolset_Element::get_instance().
	 *
	 * @param string|WP_Post $object_source
	 * @param string|null $language_code
	 *
	 * @deprecated Use Toolset_Element_Factory::get_post() instead.
	 *
	 * @return Toolset_Post
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public static function get_instance( $object_source, $language_code = null ) {
		$element_factory = new Toolset_Element_Factory();
		return $element_factory->get_post_untranslated( $object_source, $language_code );
	}


	/**
	 * @return string One of the Toolset_Field_Utils::get_domains() values.
	 */
	public function get_domain() { return Toolset_Element_Domain::POSTS; }


	/**
	 * @return int Post ID.
	 */
	public function get_id() { return $this->post->ID; }


	/**
	 * @return string Post title.
	 */
	public function get_title() { return $this->post->post_title; }


	/**
	 * @inheritdoc
	 * @return Toolset_Field_Group_Post[]
	 * @since m2m
	 */
	protected function get_relevant_field_groups() {

		$selected_groups = $this->group_post_factory->get_groups_by_post_type( $this->get_type() );

		return $selected_groups;
	}


	/**
	 * @return string Post type slug.
	 * @since m2m
	 */
	public function get_type() {
		return $this->post->post_type;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_translatable() {
		return $this->wpml_service->is_post_type_translatable( $this->get_type() );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_language() {
		if( null === $this->language_code ) {
			$this->language_code = $this->wpml_service->get_post_language( $this->get_id() );
		}

		return $this->language_code;
	}


	/**
	 * @param string $title New post title
	 *
	 * @return void
	 * @since m2m
	 */
	public function set_title( $title ) {
		$this->post->post_title = sanitize_text_field( $title );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_slug() {
		return $this->post->post_name;
	}


	protected function get_post_type_repository() {
		if( null === $this->_post_type_repository ) {
			$this->_post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}

		return $this->_post_type_repository;
	}


	/**
	 * @return IToolset_Post_Type|null
	 * @since 2.5.10
	 */
	public function get_type_object() {
		return $this->get_post_type_repository()->get( $this->get_type() );
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $language_code
	 * @param bool $exact_match_only
	 *
	 * @return IToolset_Element|null
	 */
	public function translate( $language_code, $exact_match_only = false ) {
		if( ! $this->is_translatable() ) {
			return $this;
		}

		// This could happen only in very rare cases, when WPML is active,
		// someone obtains a translation set from Toolset_Element_Factory,
		// calls translate() on it, which would return this instance, and then
		// they call translate() again... and here we are.
		$translation_set = $this->element_factory->get_post_translation_set( array( $this ) );
		return $translation_set->translate( $language_code, $exact_match_only );
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.10
	 */
	public function get_default_language_id() {
		if( ! $this->is_translatable() ) {
			return $this->get_id();
		}

		// This could happen only in very rare cases, when WPML is active,
		// someone obtains a translation set from Toolset_Element_Factory,
		// calls translate() on it, which would return this instance, and then
		// they call this method... and here we are.
		$translation_set = $this->element_factory->get_post_translation_set( array( $this ) );
		return $translation_set->get_default_language_id();
	}


	/**
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_revision() {
		return ( 'revision' === $this->get_type() );
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_author() {
		return (int) $this->post->post_author;
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_trid() {
		return $this->wpml_service->get_post_trid( $this->get_id() );
	}
}
