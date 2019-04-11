<?php

/**
 * Types_Helper_Twig
 *
 * @since 2.0
 */
class Types_Helper_Twig {

	private $filesystem;
	private $twig;

	public function __construct() {
		// backwards compatibility for php5.2
		if ( ! defined( 'E_DEPRECATED' ) )
			define( 'E_DEPRECATED', 8192 );

		if ( ! defined( 'E_USER_DEPRECATED' ) )
			define( 'E_USER_DEPRECATED', 16384 );

		$this->filesystem = new Twig_Loader_Filesystem();
		$this->filesystem->addPath( TYPES_ABSPATH . '/application/views' );
		$this->twig = new Twig_Environment( $this->filesystem );
		$this->twig->addFunction( new Twig_SimpleFunction( '__', array( $this, 'translate' ) ) );
	}

	/**
	 * This allows to use __( 'Text to translate', 'wpcf' ) in twig templates
	 *
	 * @param $text
	 * @param string $domain
	 *
	 * @return mixed
	 */
	public function translate( $text, $domain = 'wpcf' ) {
		return __( $text, $domain );
	}
	
	public function render( $file, $data ) {
		if( $this->filesystem->exists( $file ) )
			return $this->twig->render( $file, $data );

		return false;
	}
}