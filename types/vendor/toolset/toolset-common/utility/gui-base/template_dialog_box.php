<?php

/**
 * Represents a single dialog box whose template will be rendered on the page when an instance of this class is
 * initialized.
 *
 * It accepts an IToolset_Output_Template instance for the content of the dialog.
 *
 * On the client side, it is recommended to use in conjunction with
 * the Toolset_Gui_Base::SCRIPT_GUI_MIXIN_CREATE_DIALOG asset.
 *
 * Initialize the Toolset_Gui_Base first if you want to use this.
 *
 * @since 2.5.11
 */
class Toolset_Template_Dialog_Box extends Toolset_DialogBoxes {


	/** @var string */
	private $dialog_id;


	/** @var IToolset_Output_Template */
	private $template;


	/** @var Toolset_Renderer */
	private $renderer;


	/** @var array */
	private $context;


	/**
	 * Toolset_Template_Dialog_Box constructor.
	 *
	 * Note: Be careful about instantiating this too early, because we need the current screen to already return a value
	 * at this moment (this is a limitation caused by the superclass).
	 *
	 * @param string $dialog_id Unique ID (at least within the page) used to reference the dialog in JS.
	 * @param IToolset_Output_Template $template Template for the content of the dialog.
	 * @param array $context Template context that will be used for rendering.
	 * @param Toolset_Renderer|null $renderer_di
	 */
	public function __construct(
		$dialog_id,
		IToolset_Output_Template $template,
		$context = array(),
		Toolset_Renderer $renderer_di = null
	) {
		$current_screen = get_current_screen();
		parent::__construct( array( $current_screen->id ) );

		$this->dialog_id = $dialog_id;
		$this->template = $template;
		$this->renderer = $renderer_di ?: Toolset_Renderer::get_instance();
		$this->context = toolset_ensarr( $context );
	}


	/**
	 * Initialize the dialog box. This needs to be called before admin_enqueue_scripts.
	 */
	public function initialize() {
		$this->late_register_assets();
		$this->init_screen_render();
	}


	/**
	 * Manually register dialog assets in Toolset_Assets_Manager because by now we have already missed the
	 * toolset_add_registered_styles and toolset_add_registered_scripts filters (but there is still enough time
	 * to enqueue).
	 */
	protected function late_register_assets() {
		$asset_manager = Toolset_Assets_Manager::get_instance();

		$scripts = $this->register_scripts( array() );
		foreach( $scripts as $script ) {
			$asset_manager->add_script( $script );
		}
	}


	/**
	 * Render a predefined Twig template.
	 *
	 * @since 2.0
	 * @throws Twig_Error_Loader In case of incorrect Twig configuration.
	 * @throws Twig_Error_Runtime In case of incorrect Twig configuration.
	 * @throws Twig_Error_Syntax In case of incorrect Twig configuration.
	 */
	public function template() {
		printf(
			'<script type="text/html" id="%s">%s</script>',
			esc_attr( $this->dialog_id ),
			$this->renderer->render( $this->template, $this->context, false )
		);
	}


}