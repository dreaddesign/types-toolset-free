<?php

/**
 * Repository for templates in Toolset Common.
 *
 * See Toolset_Renderer for a detailed usage instructions.
 *
 * @since 2.5.9
 */
class Toolset_Output_Template_Repository extends Toolset_Output_Template_Repository_Abstract {

	// Names of the templates go here and to $templates
	//
	//

	const FAUX_TEMPLATE = 'faux_template.twig';
	const MAINTENANCE_FILE = 'maintenance.twig';


	/**
	 * @var array Template definitions.
	 */
	private $templates = array();


	/** @var Toolset_Output_Template_Repository */
	private static $instance;


	/**
	 * @return Toolset_Output_Template_Repository
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function __construct(
		Toolset_Output_Template_Factory $template_factory_di = null,
		Toolset_Constants $constants_di = null
	) {
		parent::__construct( $template_factory_di, $constants_di );

		$this->templates = array(
			self::FAUX_TEMPLATE => array(
				'base_path' => null,
				'namespaces' => array()
			),
			self::MAINTENANCE_FILE => array(
				'base_path' => $this->get_templates_dir_base_path()
			)
		);
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_default_base_path() {
		return $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/utility/gui-base/twig-templates';
	}


	private function get_templates_dir_base_path() {
		return $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/templates';
	}



	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	protected function get_templates() {
		return $this->templates;
	}
}