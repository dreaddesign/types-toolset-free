<?php

/**
 * Represents a single dialog box whose template will be rendered on the page when an instance of this class is
 * created. Enqueues all the assets needed for displaying it.
 *
 * Initialize the Toolset_Gui_Base if you want to use this.
 * 
 * @since 2.2
 */
class Toolset_Twig_Dialog_Box extends Toolset_DialogBoxes {


	/** @var array */
	private $context;
	
	/** @var Twig_Environment */
	private $twig;
	
	/** @var string */
	private $template_name;

	/** @var string */
	private $dialog_id;


	/**
	 * Toolset_Twig_Dialog_Box constructor.
	 *
	 * Should be called during the 'current_screen' action, not sooner or later.
	 *
	 * @param string $dialog_id Unique ID (at least within the page) used to reference the dialog in JS.
	 * @param Twig_Environment $twig Prepared Twig environment.
	 * @param array $context Twig context for the dialog template.
	 * @param string $template_name Twig template name that will be recognized by the provided environment.
	 * @param bool $late_register_assets Whether to run late_register_assets() or not.
	 *
	 * @since 2.0
	 */
	public function __construct( $dialog_id, $twig, $context, $template_name, $late_register_assets = true ) {

		$current_screen = get_current_screen();
		parent::__construct( array( $current_screen->id ) );

		$this->dialog_id = $dialog_id;
		$this->twig = $twig;
		$this->context = $context;
		$this->template_name = $template_name;

		if( $late_register_assets ) {
			$this->late_register_assets();
		}

		// We're going to render on the page we're creating this instance.
		$this->init_screen_render();
	}


	/**
	 * Render a predefined Twig template.
	 *
	 * @since 2.0
	 * @throws Twig_Error_Loader  When the template cannot be found
	 * @throws Twig_Error_Syntax  When an error occurred during compilation
	 * @throws Twig_Error_Runtime
	 */
	public function template() {
		printf(
			'<script type="text/html" id="%s">%s</script>',
			esc_attr( $this->dialog_id ),
			$this->twig->render( $this->template_name, $this->context )
		);
	}


	/**
	 * Manually register dialog assets in Toolset_Assets_Manager because by now we have already missed the
	 * toolset_add_registered_styles and toolset_add_registered_scripts filters (but there is still enough time
	 * to enqueue).
	 *
	 * @since 2.0
	 */
	protected function late_register_assets() {
		/*
		 * Toolset_DialogBoxes::register_styles() no longer exists
		 * https://git.onthegosystems.com/toolset/toolset-common/commit/cb176128ac8382cebbad46a39848b4c76fdcc7a7
		 *
		// Get script and styles from parent methods, and register them manually.
		$styles = $this->register_styles( array() );
		foreach( $styles as $style ) {
			Types_Asset_Manager::get_instance()->register_toolset_style( $style );
		}
		*/

		$asset_manager = Toolset_Assets_Manager::get_instance();

		$scripts = $this->register_scripts( array() );
		foreach( $scripts as $script ) {
			$asset_manager->add_script( $script );
		}
	}
}