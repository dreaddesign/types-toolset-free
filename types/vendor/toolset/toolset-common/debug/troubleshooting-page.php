<?php

/**
 * Toolset Troubleshooting page controller.
 * 
 * For adding custom troubleshooting sections, please refer to the toolset_get_troubleshooting_sections filter
 * documentation.
 *
 * @since m2m
 */
class Toolset_Page_Troubleshooting {

	const SCRIPT_NAME = 'toolset-troubleshooting-page-controller';


	private static $instance;


	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	
	private function __construct() { }
	
	private function __clone() { }
	
	
	public function prepare() {
		
		$bootstrap = Toolset_Common_Bootstrap::get_instance();
		$bootstrap->register_gui_base();
		
		Toolset_Gui_Base::get_instance()->init();

		add_action( 'admin_enqueue_scripts', array( $this, 'on_enqueue_scripts' ) );
	}

	
	public function render() {

		$context = $this->build_page_context();

		$twig = $this->get_twig();

		echo $twig->render( '@troubleshooting/main.twig', $context );
		
	}


	public function on_enqueue_scripts() {
		
		$asset_manager = Toolset_Assets_Manager::getInstance();
		
		$asset_manager->enqueue_styles( Toolset_Gui_Base::STYLE_GUI_BASE );

		$asset_manager->register_script(
			self::SCRIPT_NAME,
			TOOLSET_COMMON_URL . '/debug/main.js',
			array( Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER ),
			TOOLSET_VERSION
		);

		$asset_manager->enqueue_scripts( self::SCRIPT_NAME );
	}


	private $twig = null;


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Twig_Environment
	 * @since m2m
	 */
	private function get_twig() {
		if( null == $this->twig ) {

			$gui_base = Toolset_Gui_Base::get_instance();

			$this->twig = $gui_base->create_twig_environment(
				array( 'troubleshooting' => TOOLSET_COMMON_PATH . '/debug/' ),
				true,
				true
			);

		}
		return $this->twig;
	}


	private function build_page_context() {

		$troubleshooting_sections = $this->get_section_data();
		$js_model_data = array(
			'sections' => $troubleshooting_sections,
			'strings' => array(
				'confirmUnload' => __( 'There is an action in progress. Please do not leave or reload this page until it finishes.', 'wpcf' )
			)
		);
		$gui_base = Toolset_Gui_Base::get_instance();
		$base_context = $gui_base->get_twig_context_base( Toolset_Gui_Base::TEMPLATE_BASE, $js_model_data );

		$context = array(
			'debugInformation' => $this->build_debug_information(),
			'sections' => $troubleshooting_sections
		);
		
		return toolset_array_merge_recursive_distinct( $base_context, $context );
	}
	
	
	private function build_debug_information() {
		
		include_once dirname(__FILE__) . '/functions_debug_information.php';
		$debug_information = new ICL_Debug_Information();
		$debug_data = $debug_information->get_debug_info();

		return esc_html( $debug_information->do_json_encode( $debug_data ) );
	}


	private function get_section_data() {

		/**
		 * Retrieves an array of section definitions for the Toolset Troubleshooting page.
		 * 
		 * Each section is an array with following keys:
		 *
		 * - string $title: Title of the metabox.
		 * - string $description: Main text inside the metabox, explaining the action. No markup allowed.
		 * - string $button_label: Label of the "start" button.
		 * - bool $is_dangerous: If this is true, user will have to confirm they have a backup and understand the risks.
		 * - string $action_name: Name of the AJAX action that will be called when user starts the action. Details below.
		 * - string $nonce: A nonce that is recognized by the AJAX action.
		 * - array $ajax_arguments: Optional. Additional arguments to be passed to the AJAX action.
		 *
		 * There is a few requirements on the AJAX action:
		 *
		 * 1. The name passed should be Toolset_Ajax::get_action_js_name( Toolset_Ajax::CALLBACK_SOME_ACTION ).
		 * 2. It needs to recognize the passed nonce as $_POST['wpnonce'].
		 * 3. It should return a standard response with following properties:
		 *     - string $message: A string (a single row one, ideally) to be displayed to the user.
		 *     - bool $continue: If this is defined and true, a next call will be performed afterwards (with the same
		 *       $action_name and $nonce).
		 *     - array $ajax_arguments: Additional arguments for the next call if $continue is true. Otherwise it
		 *       doesn't have to be defined.
		 *
		 * This way it is possible to safely implement lengthy processes without the risk of running into timeouts on
		 * cheap hostings, and keeping the user informed about the progress.
		 *
		 * @since m2m
		 */
		$sections = apply_filters( 'toolset_get_troubleshooting_sections', array() );
		
		if( !is_array( $sections ) ) {
			$sections = array();
		}
		
		return $sections;
	}
}