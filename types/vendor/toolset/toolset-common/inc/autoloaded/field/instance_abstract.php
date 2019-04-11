<?php

abstract class Toolset_Field_Instance_Abstract {

	/** @var Toolset_Field_Definition */
	protected $definition;


	/** @var null|Toolset_Field_Accessor_Abstract */
	protected $accessor = null;


	/**
	 * Toolset_Field_Instance constructor.
	 *
	 * @param Toolset_Field_Definition $definition Field definition.
	 */
	public function __construct( $definition ) {
		if( ! $definition instanceof Toolset_Field_Definition ) {
			throw new InvalidArgumentException( 'Invalid type of field definition.' );
		}
		$this->definition = $definition;
	}


	public function get_definition() { return $this->definition; }


	public function get_field_type() {
		$definition = $this->get_definition();
		return $definition->get_type(); 
	}


	/**
	 * Get the accessor to the field value.
	 *
	 * We can't access it directly because we don't know how - it can be stored in post meta, user meta or term meta.
	 *
	 * @return Toolset_Field_Accessor_Abstract
	 */
	protected abstract function get_accessor();


	/**
	 * Get raw value of the field directly from the database.
	 *
	 * @deprecated Use with caution! Usually the value is expected in the intermediate format, which is what get_value() is for.
	 * @return mixed Raw value of the field.
	 */
	public function get_raw_value() {
		$accessor = $this->get_accessor();
		return $accessor->get_raw_value();
	}


	/**
	 * @return mixed Value of the field in the "intermediate" format.
	 */
	public function get_value() {
		return $this->get_raw_value();
	}


	/**
	 * @return int ID of the object that owns the field.
	 */
	public abstract function get_object_id();

}