<?php

/**
 * Template for static files.
 *
 * @since 2.5.9
 */
class Toolset_Output_Template_Phtml extends Toolset_Output_Template {


	const FILE_EXTENSION = '.phtml';


	/**
	 * Toolset_Output_Template_Phtml constructor.
	 *
	 * @param string $base_path
	 * @param string $template_name
	 * @throws InvalidArgumentException Thrown if a file with a wrong suffix is provided.
	 */
	public function __construct( $base_path, $template_name ) {

		if( self::FILE_EXTENSION !== substr( $template_name, - strlen( self::FILE_EXTENSION ) ) ) {
			throw new InvalidArgumentException( 'Provided template file is not a PHTML.' );
		}

		parent::__construct( $base_path, $template_name );
	}
}