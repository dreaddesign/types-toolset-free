<?php

/**
 * Field accessor for term meta.
 *
 * @since 1.9
 */
final class WPCF_Field_Accessor_Termmeta_Field extends WPCF_Field_Accessor_Termmeta {

	/** @var WPCF_Field_Instance_Abstract */
	private $field;


	/**
	 * WPCF_Field_Accessor_Termmeta_Field constructor.
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param bool $is_repetitive
	 * @param WPCF_Field_Instance_Abstract $field_instance
	 * @throws InvalidArgumentException
	 */
	public function __construct( $object_id, $meta_key, $is_repetitive, $field_instance ) {
		parent::__construct( $object_id, $meta_key, $is_repetitive );
		if( ! $field_instance instanceof WPCF_Field_Instance_Abstract ) {
			throw new InvalidArgumentException( 'Field instance required.' );
		}
		$this->field = $field_instance;
	}


	public function delete_raw_value( $value = '' ) {

		$action = ( $this->field->get_definition()->get_is_repetitive() ? 'delete_repetitive' : 'delete' );

		do_action( "wpcf_termeta_before_{$action}", $this->field );

		$result = delete_term_meta( $this->object_id, $this->meta_key, $value );

		do_action( "wpcf_termmeta_after_{$action}", $this->field );

		return $result;
	}

	/**
	 * Add new metadata.
	 *
	 * @param mixed $value New value to be saved to the database
	 * @return mixed
	 */
	public function add_raw_value( $value ) {
		return add_term_meta( $this->object_id, $this->meta_key, $value, $this->is_single );
	}

}