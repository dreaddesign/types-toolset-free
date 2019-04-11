<?php

/**
 * Represents a set of post translations.
 *
 * This class can act as a single post, working as a proxy to one of its translations.
 *
 * Each of the interface methods has an additional parameter where a language code may be specified. If it isn't,
 * the best available translation will be chosen: current language > default language > original post language.
 *
 * In all other aspects, these methods act exactly the same way as in Toolset_Post.
 *
 * Note: It is not possible to instantiate this class when WPML is not active. You should always
 * use Toolset_Elemen_Factory::get_post() instead of reinventing its logic elsewhere.
 *
 * @since m2m
 */
class Toolset_Post_Translation_Set implements IToolset_Post {


	/** @var Toolset_Post[] */
	private $translations = array();


	/** @var int Any post ID from the translation group, which will be used to quickly obtain a translation from WPML. */
	private $starting_post_id;


	/** @var Toolset_Post[] Cache for get_best_translation().  */
	private $best_translation_for = array();


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var Toolset_Element_Factory */
	private $element_factory;


	/** @var null|Toolset_Post_Type_Repository */
	private $_post_type_repository;


	/** @var null|int */
	private $_trid;


	/**
	 * Toolset_Post_Translation_Set constructor.
	 *
	 * @param Toolset_Post[] $translations Array of this post's translations indexed by language codes.
	 *     It doesn't need to be complete, but having these values ready can improve performance.
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 *
	 * @since m2m
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function __construct(
		$translations,
		Toolset_WPML_Compatibility $wpml_service_di = null,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null
	) {

		$this->wpml_service = ( null === $wpml_service_di ? Toolset_WPML_Compatibility::get_instance() : $wpml_service_di );
		$this->element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory() : $element_factory_di );
		$this->_post_type_repository = $post_type_repository_di;

		if ( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			throw new RuntimeException( 'Attempted to use a post translation set while WPML was inactive' );
		}

		if ( ! is_array( $translations ) || empty( $translations ) ) {
			throw new InvalidArgumentException( 'Invalid argument when creating a post translation set');
		}

		foreach ( $translations as $translation ) {
			if ( ! $translation instanceof Toolset_Post ) {
				$translation = $this->element_factory->get_post_untranslated( $translation );
			}

			$this->translations[ $translation->get_language() ] = $translation;
		}

		if( array_key_exists( $this->wpml_service->get_default_language(), $this->translations ) ) {
			$this->starting_post_id = $this->translations[ $this->wpml_service->get_default_language() ]->get_id();
		} else {
			$some_translation = reset( $this->translations );
			$this->starting_post_id = $some_translation->get_id();
		}
	}


	/**
	 * For a given language code, fetch a translation ID from WPML.
	 *
	 * @param string $language_code Target language.
	 * @param bool $return_original_if_missing If true, something will always be returned.
	 *
	 * @return int ID of the post translation or zero if it doesn't exist.
	 */
	private function fetch_translation( $language_code, $return_original_if_missing = false ) {

		// See https://wpml.org/wpml-hook/wpml_object_id/
		//
		// Notice that $return_original_if_missing is set to false by default, so we'll not get a result that's not
		// truly translated.
		//
		// P.S.: Can't use get_type() for the third argument because that requires having a
		// post instance and it could create an infinite recursion. Luckily, WPML interprets the 'any'
		// type as "any post type".
		$id = (int) apply_filters( 'wpml_object_id', $this->starting_post_id, 'any', $return_original_if_missing, $language_code );

		return $id;
	}


	/**
	 * Get an ID of post translation.
	 *
	 * The result is cached for performance optimization, and may be based on the data provided in the constructor.
	 *
	 * @param string $language_code
	 *
	 * @return Toolset_Post|null The translation or null if none exists.
	 * @since m2m
	 */
	private function get_translation( $language_code ) {

		if ( ! array_key_exists( $language_code, $this->translations ) ) {
			$translated_post_id = $this->fetch_translation( $language_code );
			if ( $translated_post_id !== 0 ) {
				try {
					$translation = $this->element_factory->get_post_untranslated( $translated_post_id, $language_code );
				} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
					$translation = null;
				}
			} else {
				$translation = null;
			}

			$this->translations[ $language_code ] = $translation;
		}

		return $this->translations[ $language_code ];

	}


	private function is_translated_to( $language_code ) {
		$translation = $this->get_translation( $language_code );

		return ( null !== $translation );
	}


	/**
	 * Get the translation in default site language or, if that is not available, the original translation.
	 *
	 * @return Toolset_Post
	 */
	private function get_original_translation() {
		$translated_post_id = $this->fetch_translation(
			$this->wpml_service->get_default_language(),
			true
		);

		$post_language_details = apply_filters( 'wpml_post_language_details', null, $translated_post_id );
		$language_code = toolset_getarr( $post_language_details, 'language_code' );

		$translation = $this->element_factory->get_post_untranslated( $translated_post_id, $language_code );

		// Store it in cache only if we got a language code. At this point I don't trust anything.
		if ( is_string( $language_code ) && ! empty( $language_code ) ) {
			$this->translations[ $language_code ] = $translation;
		}

		return $translation;
	}


	/**
	 * Choose the best available translation.
	 *
	 * Priorities: given language > current language > default language > original post language.
	 *
	 * @param null|string $language_code
	 *
	 * @return Toolset_Post
	 */
	private function get_best_translation( $language_code = null ) {

		if ( null === $language_code ) {
			$language_code = $this->wpml_service->get_current_language();
		}

		if( array_key_exists( $language_code, $this->best_translation_for ) ) {
			return $this->best_translation_for[ $language_code ];
		}

		if ( $this->is_translated_to( $language_code ) ) {
			$post = $this->get_translation( $language_code );
		} else {
			$default_language = $this->wpml_service->get_default_language();
			if ( $this->is_translated_to( $default_language ) ) {
				$post = $this->get_translation( $default_language );
			} else {
				$post = $this->get_original_translation();
			}
		}

		$this->best_translation_for[ $language_code ] = $post;

		return $post;
	}


	/**
	 * @return string One of the Toolset_Field_Utils::get_domains() values.
	 */
	public function get_domain() {
		return Toolset_Element_Domain::POSTS;
	}


	/**
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return int ID of the underlying object.
	 */
	public function get_id( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_id();
	}


	/**
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return string Post title.
	 */
	public function get_title( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_title();
	}


	/**
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return string Post type slug.
	 * @since m2m
	 */
	function get_type( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_type();
	}


	/**
	 * Load custom fields of the element if they're not loaded yet.
	 *
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return void
	 * @since m2m
	 */
	function initialize_fields( $language_code = null ) {
		$this->get_best_translation( $language_code )->initialize_fields();
	}


	/**
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return bool
	 */
	function are_fields_loaded( $language_code = null ) {
		return $this->get_best_translation( $language_code )->are_fields_loaded();
	}


	/**
	 * Get the object this model is wrapped around.
	 *
	 * @param null $language_code
	 *
	 * @return mixed Depends on the subclass.
	 * @since m2m
	 */
	function get_underlying_object( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_underlying_object();
	}


	/**
	 * Determine if the element has a particular field.
	 *
	 * It depends on the field definitions and field groups assigned to the element, not on the actual values in the
	 * database.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or a field slug.
	 *
	 * @param null $language_code
	 *
	 * @return bool True if a field with given slug exists.
	 * @since m2m
	 */
	function has_field( $field_source, $language_code = null ) {
		return $this->get_best_translation( $language_code )->has_field( $field_source );
	}


	/**
	 * Get a field instance.
	 *
	 * Check if has_field() before, otherwise you'll get an exception.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or a field slug.
	 *
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return Toolset_Field_Instance
	 */
	function get_field( $field_source, $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_field( $field_source );
	}


	/**
	 * Get all field instances belonging to the element.
	 *
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return Toolset_Field_Instance[]
	 * @since m2m
	 */
	function get_fields( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_fields();
	}


	/**
	 * @param null|string $language_code If null, the best translation will be selected automatically.
	 *
	 * @return int
	 */
	function get_field_count( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_field_count();
	}


	/**
	 * @return bool
	 */
	function is_translatable() {
		// Assumption: get_type() is the same for all translations, so it doesn't
		// matter which one gets picked up. If this doesn't work as expected,
		// the site has way more serious problems.
		return $this->wpml_service->is_post_type_translatable( $this->get_type() );
	}


	/**
	 * Get the actual language of the post when selecting a specific language.
	 *
	 * It will differ in case of missing translations.
	 *
	 * @param null $language_code
	 *
	 * @return string
	 */
	function get_language( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_language();
	}


	/**
	 * @param null|string $language_code
	 *
	 * @return string Post slug
	 * @since m2M
	 */
	public function get_slug( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_slug();
	}


	/**
	 * @param string $title New post title
	 *
	 * @param null|string $language_code
	 *
	 * @return void
	 * @since m2m
	 */
	public function set_title( $title, $language_code = null ) {
		$this->get_best_translation( $language_code )->set_title( $title );
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
	 * Return an element translation.
	 *
	 * If the element domain and type are non-translatable, it will return itself.
	 *
	 * If the element could be translated to the target language but is not,
	 * the return value will depend on the $exact_match_only parameter:
	 * If it's true, it will return null. Otherwise, it will return the best possible
	 * translation (default language/original/any).
	 *
	 * @param string $language_code
	 * @param bool $exact_match_only
	 *
	 * @return IToolset_Element|null
	 */
	public function translate( $language_code, $exact_match_only = false ) {
		if( $exact_match_only && $this->is_translatable() ) {
			return $this->get_translation( $language_code );
		} else {
			return $this->get_best_translation( $language_code );
		}
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.10
	 */
	public function get_default_language_id() {
		$translation = $this->get_translation( $this->wpml_service->get_default_language() );
		if( null === $translation ) {
			return 0;
		}

		return $translation->get_id();
	}

	/**
	 * @param string|null $language_code
	 *
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_revision( $language_code = null ) {
		return $this->get_best_translation( $language_code )->is_revision();
	}


	/**
	 * @inheritdoc
	 *
	 * @param string|null $language_code
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_author( $language_code = null ) {
		return $this->get_best_translation( $language_code )->get_author();
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_trid() {
		if( null === $this->_trid ) {
			$this->_trid = $this->wpml_service->get_post_trid( $this->starting_post_id );
		}
		return $this->_trid;
	}
}