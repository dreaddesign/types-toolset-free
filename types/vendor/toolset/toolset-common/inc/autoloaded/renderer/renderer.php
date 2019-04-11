<?php

/**
 * Entry point for the template rendering API.
 *
 * How it works:
 *
 * Each template file is registered in Toolset_Output_Template_Repository or in another subclass of
 * Toolset_Output_Template_Repository if it's contained only in a specific plugin. The registration already
 * includes everything necessary for rendering the template (path and also required namespaces in case of
 * Twig templates), except the template context (which is for passing along dynamic data at the time of rendering).
 *
 * So far, three types of templates are supported:
 * - static templates (.html)
 * - PHTML (PHP + HTML) (.phtml)
 * - Twig templates (.twig)
 *
 * The difference between static templates and PHTML is that the latter can be passed some context information
 * at the time of rendering, while the former is truly static without any sort of context.
 *
 * Toolset_Renderer accepts the template as an IToolset_Output_Template instance (which is provided by the repository)
 * and uses the correct mechanism to render it (print or return).
 *
 * For Twig templates, there's a caching mechanism that reuses the same Twig environment if two templates have identical
 * Twig namespaces.
 *
 * Usage:
 *
 * 1. Choose where to put your template. If it's in Toolset Common, use the Toolset_Output_Template_Repository class.
 *    Otherwise you may need to subclass Toolset_Output_Template_Repository_Abstract to add plugin-specific templates.
 * 2. Define your template in the repository class. The template name must be the name of the actual template file.
 *    The filename suffis is relevant: "twig" for Twig templates, "phtml" for PHTML ones and "html" for static templates.
 *    Nothing else will be accepted.
 * 3. Use the renderer like this:
 *
 *    $template_repository = MyPlugin_Output_Template_Repository::get_instance()
 *    $renderer = Toolset_Renderer::get_instance();
 *    $output = $renderer->render(
 *        $template_repository->get( MyPlugin_Output_Template_Repository::MY_TEMPLATE ),
 *        $context,
 *        false // do not print but return the result
 *    );
 *
 * Note: Except reusable templates in Toolset Common, you may also use the template directly
 * instead of registering it inside a template repository class, if you prefer.
 *
 * @since 2.5.9
 */
class Toolset_Renderer {


	/** @var Toolset_Renderer */
	private static $instance;


	/** @var null|Toolset_Gui_Base */
	private $_gui_base;


	/** @var Toolset_Files */
	private $files;


	/** @var Twig_Environment[] */
	private $twig_environments = array();


	/**
	 * Toolset_Renderer constructor.
	 *
	 * @param Toolset_Gui_Base|null $gui_base_di
	 * @param Toolset_Files|null $files_di
	 */
	public function __construct(
		Toolset_Gui_Base $gui_base_di = null,
		Toolset_Files $files_di = null
	) {
		$this->_gui_base = $gui_base_di;
		$this->files = ( null === $files_di ? new Toolset_Files() : $files_di );
	}


	/**
	 * @return Toolset_Renderer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Render a template.
	 *
	 * @param IToolset_Output_Template $template
	 * @param mixed $context Arbitrary context that will be passed to the template, depending
	 *     on its type.
	 * @param bool $echo Should the output be echoed?
	 *
	 * @return string
	 * @throws Twig_Error_Loader In case of incorrect Twig configuration.
	 * @throws Twig_Error_Runtime In case of incorrect Twig configuration.
	 * @throws Twig_Error_Syntax In case of incorrect Twig configuration.
	 */
	public function render( IToolset_Output_Template $template, $context, $echo = true ) {

		if( $template instanceof Toolset_Output_Template_Twig ) {
			$output = $this->render_twig_template( $template, $context );
		} elseif( $template instanceof Toolset_Output_Template_Phtml ) {
			$output = $this->render_phtml_template( $template, $context );
		} else {
			$output = $this->render_static_template( $template );
		}

		if( $echo ) {
			echo $output;
		}

		return $output;
	}


	/**
	 * Render the given PHTML/HTML template.
	 *
	 * @param  Toolset_Output_Template $template The path of the template to render.
	 * @param  mixed $context The context of the template for passing some additional data to the template, if needed.
	 *
	 * @return string The template output.
	 */
	private function render_phtml_template( $template, $context ) {
		$template_path = $template->get_full_path();
		$template_output = '';
		if ( $this->files->is_file( $template_path ) ) {
			$template_output = $this->files->get_include_file_output(
				$template_path, array( 'context' => $context )
			);
		}

		return $template_output;
	}


	/**
	 * @param IToolset_Output_Template $template
	 *
	 * @return string
	 */
	private function render_static_template( $template ) {
		if( ! $this->files->is_file( $template->get_full_path() ) ) {
			return '';
		}

		$output = $this->files->file_get_contents( $template->get_full_path() );

		if( false === $output ) {
			return '';
		}

		return $output;
	}


	/**
	 * @param Toolset_Output_Template_Twig $template
	 * @param array $context
	 *
	 * @return string
	 * @throws Twig_Error_Loader In case of incorrect Twig configuration.
	 * @throws Twig_Error_Runtime In case of incorrect Twig configuration.
	 * @throws Twig_Error_Syntax In case of incorrect Twig configuration.
	 */
	private function render_twig_template( Toolset_Output_Template_Twig $template, $context ) {

		$twig = $this->get_twig(
			$template->get_twig_namespaces(),
			$template->get_twig_environment_hash()
		);

		return $twig->render( $template->get_name(), $context );
	}


	/**
	 * Returns GUI Base
	 *
	 * @return Toolset_Gui_Base
	 * @since m2m
	 */
	private function get_gui_base() {
		if( null === $this->_gui_base ) {
			$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
			$toolset_common_bootstrap->register_gui_base();

			$gui_base = Toolset_Gui_Base::get_instance();
			$gui_base->init();

			$this->_gui_base = $gui_base;
		}

		return $this->_gui_base;
	}


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @param string[] $namespaces
	 * @param string $hash
	 *
	 * @return Twig_Environment In case of incorrect Twig configuration.
	 * @since m2m
	 * @throws Twig_Error_Loader In case of incorrect Twig configuration.
	 */
	private function get_twig( $namespaces, $hash ) {
		if( ! array_key_exists( $hash, $this->twig_environments ) ) {
			$gui_base = $this->get_gui_base();
			$this->twig_environments[ $hash ] = $gui_base->create_twig_environment( $namespaces );
		}

		return $this->twig_environments[ $hash ];
	}


}