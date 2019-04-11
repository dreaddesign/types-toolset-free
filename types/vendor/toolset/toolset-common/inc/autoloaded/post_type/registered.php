<?php

/**
 * Represents a post type that is currently registered on the site.
 *
 * Always use Toolset_Post_Type_Repository for obtaining instances.
 *
 * @since m2m
 */
class Toolset_Post_Type_Registered extends Toolset_Post_Type_Abstract implements IToolset_Post_Type_Registered {


	/** @var WP_Post_Type */
	private $wp_post_type;

	/** @var null|array Cache for the post type labels. */
	private $labels = null;


	/**
	 * Toolset_Post_Type_Registered constructor.
	 *
	 * @param WP_Post_Type $wp_post_type The core object representing the post type.
	 * @param Toolset_WPML_Compatibility|null $wpml_compatibility_di
	 */
	public function __construct( WP_Post_Type $wp_post_type, Toolset_WPML_Compatibility $wpml_compatibility_di = null ) {
		parent::__construct( $wpml_compatibility_di );
		$this->wp_post_type = $wp_post_type;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_slug() {
		return $this->wp_post_type->name;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	function is_from_types() {
		return false;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	function is_registered() {
		return true;
	}


	/**
	 * @return WP_Post_Type The underlying WP core object.
	 */
	function get_wp_object() {
		return $this->wp_post_type;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	function is_builtin() {
		return $this->wp_post_type->_builtin;
	}


	/**
	 * @inheritdoc
	 * @param string $label_name
	 * @return string
	 */
	public function get_label( $label_name = Toolset_Post_Type_Labels::NAME ) {
		if( null === $this->labels ) {
			$this->labels = (array) get_post_type_labels( $this->wp_post_type );
		}

		$label = toolset_getarr( $this->labels, $label_name );

		if( !empty( $label ) ) {
			return $label;
		} elseif( Toolset_Post_Type_Labels::NAME !== $label_name ) {
			return $this->get_label( Toolset_Post_Type_Labels::NAME );
		} else {
			return $this->get_slug();
		}
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_public() {
		return $this->wp_post_type->public;
	}


	/**
	 * @inheritdoc
	 *
	 * Note: Returns always false. We don't know anything about it at this point.
	 * Will be overridden by the encapsulating class if true.
	 *
	 * @return bool
	 */
	public function is_intermediary() {
		return false;
	}


	/**
	 * @inheritdoc
	 *
	 * Note: Returns always false. We don't know anything about it at this point.
	 * Will be overridden by the encapsulating class if true.
	 *
	 * @return bool True if the post type is used as a repeating field group.
	 */
	public function is_repeating_field_group() {
		return false;
	}


	/**
	 * @inheritdoc
	 *
	 * Note: Returns always false. We don't know anything about it at this point.
	 * Will be overridden by the encapsulating class if true.
	 *
	 * @return bool True if the post type has a special purpose and shouldn't be used elsewhere.
	 */
	public function has_special_purpose() {
		return false;
	}

	/**
	 * @inheritdoc
	 *
	 * @param Toolset_Field_Group $field_group
	 *
	 * @return bool
	 */
	public function allows_field_group( Toolset_Field_Group $field_group ) {
		return true;
	}


}