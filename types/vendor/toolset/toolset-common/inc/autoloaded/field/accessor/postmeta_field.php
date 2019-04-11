<?php

/**
 * Field accessor for post meta.
 *
 * @since m2m
 */
class Toolset_Field_Accessor_Postmeta_Field extends Toolset_Field_Accessor_Postmeta {

	/** @var Toolset_Field_Instance_Abstract */
	private $field;


	/**
	 * Toolset_Field_Accessor_Postmeta_Field constructor.
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param bool $is_repetitive
	 * @param Toolset_Field_Instance_Abstract $field_instance
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$object_id, $meta_key, $is_repetitive, Toolset_Field_Instance_Abstract $field_instance
	) {
		parent::__construct( $object_id, $meta_key, $is_repetitive );

		$this->field = $field_instance;
	}


	public function delete_raw_value( $value = '' ) {

		$action = ( $this->field->get_definition()->get_is_repetitive() ? 'delete_repetitive' : 'delete' );

		do_action( "wpcf_postmeta_before_{$action}", $this->field );

		$result = delete_post_meta( $this->object_id, $this->meta_key, $value );

		do_action( "wpcf_postmeta_after_{$action}", $this->field );

		return $result;
	}

}