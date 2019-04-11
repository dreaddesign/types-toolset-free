<?php

/**
 * Types_Helper_Output_Meta_Box
 *
 * @since 2.0
 */
class Types_Helper_Output_Meta_Box implements Types_Helper_Output_Interface {

	private $output;

	public $id;
	public $title    = '';
	public $screen   = null;
	public $context  = 'normal';
	public $priority = 'high';
	public $css_class = false;

	public function __construct( $id = false ) {
		if( $id )
			$this->set_id( $id );
	}

	public function set_id( $id ) {
		$this->id = $id;
	}

	public function set_title( $string ) {
		$this->title = $string;
	}

	public function set_css_class( $string ) {
		$this->css_class = $string;
	}

	public function set_context( $context ) {
		switch( $context ) {
			case 'normal':
			case 'side':
			case 'advanced':
				$this->context = $context;
				break;
		}
	}

	public function set_content( $content ){
		$this->output = $content;
	}

	public function meta_box_output() {
		echo $this->output;
	}

	public function add_css_class( $classes ) {
		$classes[] = $this->css_class;
		return $classes;
	}

	public function output() {
		add_meta_box(
			$this->id,
			$this->title,
			array( $this, 'meta_box_output' ),
			$this->screen,
			$this->context,
			$this->priority
		);

		if( $this->css_class ) {
			$screen = get_current_screen();
			add_action( 'postbox_classes_'.$screen->id.'_'. $this->id, array( $this, 'add_css_class' ) );
		}
	}
}