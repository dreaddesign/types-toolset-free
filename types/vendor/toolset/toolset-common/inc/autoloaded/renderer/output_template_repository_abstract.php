<?php

/**
 * Repository for templates that can be subclassed to use in your plugin with Toolset_Renderer.
 *
 * @since 2.5.9
 */
abstract class Toolset_Output_Template_Repository_Abstract {


	/** @var Toolset_Output_Template_Factory */
	private $template_factory;


	/** @var Toolset_Constants */
	protected $constants;


	/**
	 * Toolset_Output_Template_Repository_Abstract constructor.
	 *
	 * @param Toolset_Output_Template_Factory|null $template_factory_di
	 * @param Toolset_Constants|null $constants_di
	 */
	public function __construct(
		Toolset_Output_Template_Factory $template_factory_di = null,
		Toolset_Constants $constants_di = null
	) {
		$this->template_factory = (
		null === $template_factory_di
			? new Toolset_Output_Template_Factory()
			: $template_factory_di
		);

		$this->constants = (
		null === $constants_di
			? new Toolset_Constants()
			: $constants_di
		);
	}


	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	abstract protected function get_templates();


	/**
	 * Load a template from its name.
	 *
	 * @param string $template_name
	 *
	 * @return IToolset_Output_Template
	 * @throws InvalidArgumentException
	 */
	public function get( $template_name ) {
		$templates = $this->get_templates();
		if( ! array_key_exists( $template_name, $templates ) ) {
			throw new InvalidArgumentException( 'Template is not defined' );
		}

		$template_definition = toolset_ensarr( $templates[ $template_name ] );
		$base_path = toolset_getarr( $template_definition, 'base_path', $this->get_default_base_path() );
		$namespaces = toolset_ensarr( toolset_getarr( $template_definition, 'namespaces' ) );

		return $this->template_factory->create_by_suffix( $base_path, $template_name, $namespaces );
	}


	/**
	 * Get the default base path for templates.
	 *
	 * @return string
	 */
	abstract protected function get_default_base_path();
}