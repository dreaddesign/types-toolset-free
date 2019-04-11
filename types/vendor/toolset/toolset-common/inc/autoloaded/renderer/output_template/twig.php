<?php

/**
 * Template for Twig templates.
 *
 * @since 2.5.9
 */
class Toolset_Output_Template_Twig extends Toolset_Output_Template {


	/**
	 * @var array Namespaces that the Twig environment requires to render this template properly.
	 */
	private $twig_namespaces;


	/**
	 * @var null|string Hash created from the requirements for Twig environment,
	 *    which allows to reuse these environments.
	 */
	private $environment_hash = null;


	const FILE_EXTENSION = '.twig';


	/**
	 * Toolset_Output_Template_Twig constructor.
	 *
	 * @param string $base_path
	 * @param string $template_name
	 * @param array $twig_namespaces
	 * @throws InvalidArgumentException if a file with a wrong suffix is provided.
	 */
	public function __construct( $base_path, $template_name, $twig_namespaces = array() ) {

		if( self::FILE_EXTENSION !== substr( $template_name, - strlen( self::FILE_EXTENSION ) ) ) {
			throw new InvalidArgumentException( 'Provided template file is not a Twig template.' );
		}

		parent::__construct( $base_path, $template_name );

		$this->twig_namespaces = toolset_ensarr( $twig_namespaces );
	}


	/**
	 * @return array Get the namespaces required for the Twig environment.
	 */
	public function get_twig_namespaces() {
		return $this->twig_namespaces;
	}


	/**
	 * @return string Hash created from the requirements for Twig environment,
	 *    which allows to reuse these environments.
	 */
	public function get_twig_environment_hash() {
		if( null === $this->environment_hash ) {
			$hash_source = '';
			foreach ( $this->twig_namespaces as $namespace => $namespace_path ) {
				$hash_source .= $namespace . '-->' . $namespace_path . ';';
			}
			$this->environment_hash = md5( $hash_source );
		}
		return $this->environment_hash;
	}

}