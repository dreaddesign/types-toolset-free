<?php

/**
 * Factory for IToolset_Output_Template.
 *
 * It should be needed only by Toolset_Output_Template_Repository_Abstract and its subclasses.
 *
 * @since 2.5.9
 */
class Toolset_Output_Template_Factory {


	/**
	 * @param string $base_path
	 * @param string $template_name
	 *
	 * @return Toolset_Output_Template_Static
	 */
	public function static_template( $base_path, $template_name ) {
		return new Toolset_Output_Template_Static( $base_path, $template_name );
	}


	/**
	 * @param string $base_path
	 * @param string $template_name
	 *
	 * @return Toolset_Output_Template_Phtml
	 */
	public function phtml_template( $base_path, $template_name ) {
		return new Toolset_Output_Template_Phtml( $base_path, $template_name );
	}


	/**
	 * @param string $base_path
	 * @param string $template_name
	 * @param array $twig_namespaces
	 *
	 * @return Toolset_Output_Template_Twig
	 */
	public function twig_template( $base_path, $template_name, $twig_namespaces ) {
		// Add the main namespace as a base path, in case it's missing.
		// If the template doesn't use it, no harm done.
		if( ! array_key_exists( '__main__', $twig_namespaces ) ) {
			$twig_namespaces['__main__'] = $base_path;
		}
		return new Toolset_Output_Template_Twig( $base_path, $template_name, $twig_namespaces );
	}


	/**
	 * Create the right type of template object according to the suffix of the template name.
	 *
	 * @param string $base_path
	 * @param string $template_name
	 * @param array $twig_namespaces
	 *
	 * @return IToolset_Output_Template
	 * @throws RuntimeException Thrown if the template type is not recognized (wrong suffix).
	 */
	public function create_by_suffix( $base_path, $template_name, $twig_namespaces = array() ) {
		$file_suffix = $this->get_suffix( $template_name );

		switch( $file_suffix ) {
			case 'twig':
				return $this->twig_template( $base_path, $template_name, $twig_namespaces );
			case 'phtml':
				return $this->phtml_template( $base_path, $template_name );
			case 'html':
				return $this->static_template( $base_path, $template_name );
			default:
				throw new RuntimeException( 'Template type not recognized from the suffix.' );
		}
	}


	/**
	 * Get a suffix from the file name.
	 *
	 * @param string $template_name
	 *
	 * @return bool|string The suffix or false if it's not detected.
	 */
	private function get_suffix( $template_name ) {
		$name_parts = explode( '.', $template_name );

		if( count( $name_parts ) < 2 ) {
			return false;
		}

		$extension = end( $name_parts );

		if( empty( $extension ) ) {
			return false;
		}

		return $extension;
	}


}