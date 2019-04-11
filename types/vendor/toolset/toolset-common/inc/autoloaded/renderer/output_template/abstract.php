<?php

/**
 * Common functionality for all template types.
 *
 * @since 2.5.9
 */
abstract class Toolset_Output_Template implements IToolset_Output_Template {


	/** @var string */
	protected $base_path;


	/** @var string */
	protected $template_name;


	/**
	 * Toolset_Output_Template constructor.
	 *
	 * @param string $base_path
	 * @param string $template_name
	 */
	public function __construct( $base_path, $template_name ) {
		$this->base_path = $base_path;
		$this->template_name = $template_name;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_full_path() {
		return trailingslashit( $this->base_path ) . $this->template_name;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_name() {
		return $this->template_name;
	}

}