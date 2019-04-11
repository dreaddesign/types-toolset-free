<?php

/**
 * Helper class for extending a Twig environment in a standardized way.
 *
 * @since 2.2
 */
class Toolset_Twig_Extensions {

	private static $instance;


	private $last_unique_id = 0;
	
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __clone() { }
	
	private function __construct() { }

	
	/**
	 * Extend the provided Twig environment.
	 *
	 * @param Twig_Environment $twig
	 * @return Twig_Environment
	 * @since 2.2
	 */
	public function extend_twig( $twig ) {

		$twig->addFunction( '__', new Twig_SimpleFunction( '__', array( $this, 'translate' ) ) );
		$twig->addFunction( 'do_meta_boxes', new Twig_SimpleFunction( 'do_meta_boxes', array( $this, 'do_meta_boxes' ) ) );
		$twig->addFunction( 'unique_name', new Twig_SimpleFunction( 'unique_name', array( $this, 'unique_name' ) ) );
		$twig->addFunction( 'printf', new Twig_SimpleFunction( 'printf', 'printf' ) );
		$twig->addFunction( 'sprintf', new Twig_SimpleFunction( 'sprintf', 'sprintf' ) );
		$twig->addFunction( 'apply_filters', new Twig_SimpleFunction( 'apply_filters', 'apply_filters' ) );

		return $twig;
	}


	public function translate( $text, $domain = 'types' ) {
		return __( $text, $domain );
	}


    public function do_meta_boxes( $context = 'normal', $object = '') {
        do_meta_boxes( get_current_screen(), $context, $object );
	}


	public function unique_name( $prefix = 'toolset_element_' ) {
		$id = ++$this->last_unique_id;

		return $prefix . $id;
	}


}