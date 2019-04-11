<?php

/**
 * Basic support for Toolset GUI in the admin.
 *
 * This prepares the Twig templating engine in a safe and convenient way, and registers (some) generic Toolset GUI assets.
 * Before you start using it, make sure to call the initialize() method and wait until init:10. Trying to utilize Twig
 * sooner will have undefined results.
 *
 * For creating a generic page based on this, you need to do the following:
 *
 * 0. Initialize this module of the common library through Toolset_Common_Bootstrap::register_gui_base().
 * 1. In the PHP page controller:
 *    1. Toolset_Gui_Base::initialize().
 *    2. Enqueue your customized JS page controller, adding Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER
 *       as a dependency.
 *    3. Enqueue your customised stylesheet, adding Toolset_Gui_Base::STYLE_GUI_BASE as a dependency
 * 2. Create a Twig template for your page based on base.twig.
 * 3. Create a JS page controller based on Toolset.Gui.AbstractPage.
 * 4. When rendering the page, you need to create a context for Twig via Toolset_Gui_Base::get_twig_context_base().
 *    1. There, you need to provide a correct template name and the data to be passed to JS (modelData) as a second
 *       parameter. There are no further requirements for modelData.
 *    2. For adding more data to the context, create a new associative array and then merge the two with
 *       toolset_array_merge_recursive_distinct().
 *    3. Since 2.5.7, you can use the Toolset_Renderer API to render the template.
 * 5. If your page will have any dialogs, implement them via Toolset_Twig_Dialog_Box (PHP)
 *    and Toolset.Gui.AbstractPage.createDialog() (JS).
 *    1. If your dialogs use undescore templates, you should pass them via 'tempates' array in modelData (see step 4).
 *    2. An example of this can be seen in Types_Page_Field_Control: build_templates(), prepare_dialogs().
 *
 * For creating a listing page, all steps are the same as above, with little changes:
 *
 * - Instead of enqueuing the Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER script, enqueue the
 *   Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER one. It already has all the dependencies set.
 * - Instead of Toolset.Gui.AbstractPage use Toolset.Gui.ListingPage
 * - Use listing.twig as a source template of your page.
 * - Your PHP page controller should make sure that there is a "toolset_fields_per_page" screen option. Again,
 *   you can see Types_Page_Field_Control as an example.
 * - Extend Toolset.Gui.ListingViewModel and Toolset.Gui.ItemViewModel to implement the page-specific behaviour.
 *
 * @since 2.2
 */
class Toolset_Gui_Base {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private $is_initialized = false;


	/**
	 * Initialize the GUI base.
	 *
	 * Triggers the Twig initialization.
	 *
	 * @since 2.2
	 * @deprecated Use get_instance() or dependency injection and then init() instead.
	 */
	public static function initialize() {
		$instance = self::get_instance();
		$instance->init();
	}


	private function __construct() { }

	private function __clone() { }


	/**
	 * Initialize the GUI base.
	 *
	 * Triggers the Twig initialization.
	 *
	 * @since m2m
	 */
	public function init() {
		if( $this->is_initialized ) {
			return;
		}

		$this->initialize_autoloader();
		$this->initialize_twig();
		$this->register_assets();

		$this->is_initialized = true;
	}


	/**
	 * Get the absolute path to the GUI base directory.
	 *
	 * @param string $relpath A relative path to be appended. Must begin with a forward slash.
	 * @return string
	 * @since 2.2
	 */
	private function get_gui_base_path( $relpath = '' ) {
		return TOOLSET_COMMON_PATH . '/utility/gui-base' . $relpath;
	}


	/**
	 * Get the URL to the GUI base directory.
	 *
	 * @param string $relurl A relative path to be appended. Must begin with a forward slash.
	 * @return string
	 * @since 2.2
	 */
	private function get_gui_base_url( $relurl = '' ) {
		return TOOLSET_COMMON_URL . '/utility/gui-base' . $relurl;
	}


	/**
	 * Load Twig by registering its autoloader.
	 *
	 * This is a bit hacky way to do it, see the Toolset_Twig_Autoloader class for explanation.
	 *
	 * After calling this, Twig is guaranteed to be available from init:10 on.
	 *
	 * @since 2.2
	 */
	private function initialize_twig() {

		// Backwards compatibility for PHP 5.2
		if ( ! defined( 'E_DEPRECATED' ) ) {
			define( 'E_DEPRECATED', 8192 );
		}

		if ( ! defined( 'E_USER_DEPRECATED' ) ) {
			define( 'E_USER_DEPRECATED', 16384 );
		}

		$was_init_fired = ( 0 != did_action( 'init' ) );

		if( $was_init_fired ) {
			// Hurry.
			Toolset_Twig_Autoloader::register();
		} else {
			// Wait until init because of the reasons described in the autoloader.
			//
			// Priority is set to 9 to make sure Twig is ready before standard initialization - it should not
			// harm anything.
			add_action( 'init', array( 'Toolset_Twig_Autoloader', 'register' ), 9 );
		}
	}


	private function initialize_autoloader() {

		$autoloader = Toolset_Common_Autoloader::get_instance();

		$base_path = $this->get_gui_base_path();
		$gui_base_classmap = array(
			'Toolset_Twig_Dialog_Box_Factory' => "$base_path/twig_dialog_box_factory.php",
			'Toolset_Twig_Dialog_Box' => "$base_path/twig_dialog_box.php",
			'Toolset_Twig_Autoloader' => "$base_path/twig_autoloader.php",
			'Toolset_Twig_Extensions' => "$base_path/twig_extensions.php",
			'Toolset_Template_Dialog_Box' => "$base_path/template_dialog_box.php"
		);

		$autoloader->register_classmap( $gui_base_classmap );
	}



	/**
	 * Create a new Twig environment.
	 *
	 * Doesn't use caching.
	 *
	 * @param string[] $paths Associative array where keys are namespaces and elements are full paths to Twig templates.
	 * @param bool $add_toolset_namespace Define whether the namespace with generic Toolset templates should be
	 *     automatically added.
	 * @param bool $add_toolset_extensions Define whether the Twig environment should get standard Toolset extensions.
	 *
	 * @return Twig_Environment
	 * @throws Twig_Error_Loader
	 * @since 2.2
	 */
	public function create_twig_environment( $paths, $add_toolset_namespace = true, $add_toolset_extensions = true ) {

		$loader = new Twig_Loader_Filesystem();

		foreach( $paths as $namespace => $path ) {
			$loader->addPath( $path, $namespace );
		}

		if( $add_toolset_namespace ) {
			$loader->addPath( $this->get_gui_base_path( '/twig-templates' ), 'toolset' );
		}

		$twig = new Twig_Environment( $loader );

		if( $add_toolset_extensions ) {
			$twig = $this->add_toolset_extensions_to_twig( $twig );
		}

		return $twig;
	}



	/** Identifier of the generic page template. */
	const TEMPLATE_BASE = 'base';


	/** Identifier of the listing page template. */
	const TEMPLATE_LISTING = 'listing';


	/**
	 * Create a standardized base for a Twig context.
	 *
	 * @param string $template Valid template identifier.
	 * @param array $js_model_data A (possibly nested) associative array of strings that will be passed as
	 *     a JSON modelData to the JS page controller.
	 *
	 * @return array An (incomplete) Twig context for given template.
	 *
	 * @since 2.2
	 */
	public function get_twig_context_base( $template, $js_model_data ) {

		switch( $template ) {
			case self::TEMPLATE_BASE:
				return array(
					'js_model_data' => $this->encode_js_model_data( $js_model_data ),
					'assets' => array(
						'loaderOverlay' => TOOLSET_COMMON_URL . '/res/images/ajax-loader-overlay.gif'
					),
				);

			case self::TEMPLATE_LISTING:

				$base_context = $this->get_twig_context_base( self::TEMPLATE_BASE, $js_model_data );

				$listing_context = array(
					'strings' => array(
						'misc' => array(
							'searchPlaceholder' => __( 'Search', 'wpcf' ),
							'noItemsFound' => __( 'No items found.', 'wpcf' ),
							'applyBulkAction' => __( 'Apply', 'wpcf' ),
							'items' => __( 'items', 'wpcf' ),
							'of' => __( 'of', 'wpcf' ),
						)
					),
					'bulkAction' => array(
						'select' => __( 'Bulk action', 'wpcf' )
					)
				);

				return toolset_array_merge_recursive_distinct(
					$base_context,
					$listing_context
				);

			default:
				return array();
		}

	}


	/**
	 * Encode JS model data in a standard way so it can be passed to JS safely.
	 *
	 * @param array $js_model_data
	 * @return string base64-encoded string.
	 * @since 2.2
	 */
	private function encode_js_model_data( $js_model_data ) {
		return base64_encode( wp_json_encode( $js_model_data ) );
	}


	/**
	 * Add standard Toolset extensions to a Twig environment.
	 *
	 * @param Twig_Environment $twig
	 * @return Twig_Environment
	 * @since 2.2
	 */
	private function add_toolset_extensions_to_twig( $twig ) {

		$twig_extensions = Toolset_Twig_Extensions::get_instance();

		$twig = $twig_extensions->extend_twig( $twig );

		return $twig;
	}


	// Names of registered assets.
	const SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER = 'toolset-gui-abstract-page-controller';
	const SCRIPT_GUI_LISTING_PAGE_CONTROLLER = 'toolset-gui-listing-page-controller';
	const SCRIPT_GUI_LISTING_VIEWMODEL = 'toolset-gui-listing-viewmodel';
	const SCRIPT_GUI_ITEM_VIEWMODEL = 'toolset-gui-item-viewmodel';
	const SCRIPT_GUI_JQUERY_COLLAPSIBLE = 'toolset-gui-jquery-collapsible';
	const SCRIPT_GUI_MIXIN_CREATE_DIALOG = 'toolset-gui-mixin-create-dialog';
	const SCRIPT_GUI_MIXIN_KNOCKOUT_EXTENSIONS = 'toolset-gui-mixin-knockout-extensions';

	const STYLE_GUI_BASE = 'toolset-gui-base';


	/**
	 * Register Toolset GUI base assets via the Toolset_Assets_Manager.
	 *
	 * @since 2.2
	 */
	private function register_assets() {

		/** @var Toolset_Assets_Manager $asset_manager */
		$asset_manager = Toolset_Assets_Manager::get_instance();

		$asset_manager->register_script(
			self::SCRIPT_GUI_MIXIN_CREATE_DIALOG,
			$this->get_gui_base_url( '/js/mixins/CreateDialog.js' ),
			array(
				// This one will be registered and enqueued only if
				// Toolset_DialogBoxes or Toolset_Twig_Dialog_Box is actually used:
				//Toolset_DialogBoxes::SCRIPT_DIALOG_BOXES,
				'underscore',
				'backbone'
			),
			TOOLSET_VERSION
		);

		$asset_manager->register_script(
			self::SCRIPT_GUI_MIXIN_KNOCKOUT_EXTENSIONS,
			self::get_gui_base_url( '/js/mixins/KnockoutExtensions.js' ),
			array(
				'knockout', 'jquery'
			),
			TOOLSET_VERSION
		);

		$asset_manager->register_script(
			self::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER,
			$this->get_gui_base_url( '/js/AbstractPageController.js' ),
			array(
				'jquery', 'backbone', 'underscore',
				Toolset_Assets_Manager::SCRIPT_UTILS,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				self::SCRIPT_GUI_MIXIN_CREATE_DIALOG,
				self::SCRIPT_GUI_MIXIN_KNOCKOUT_EXTENSIONS,
			),
			TOOLSET_VERSION
		);


		$asset_manager->register_script(
			self::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
			$this->get_gui_base_url( '/js/ListingPageController.js' ),
			array(
				self::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER,
				self::SCRIPT_GUI_LISTING_VIEWMODEL
			),
			TOOLSET_VERSION
		);


		$asset_manager->register_script(
			self::SCRIPT_GUI_LISTING_VIEWMODEL,
			$this->get_gui_base_url( '/js/ListingViewModel.js' ),
			array(
				'jquery', 'backbone', 'underscore',
				Toolset_Assets_Manager::SCRIPT_UTILS,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				self::SCRIPT_GUI_ITEM_VIEWMODEL
			),
			TOOLSET_VERSION
		);


		$asset_manager->register_script(
			self::SCRIPT_GUI_ITEM_VIEWMODEL,
			$this->get_gui_base_url( '/js/ItemViewModel.js' ),
			array(
				'jquery', 'backbone', 'underscore',
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Toolset_Assets_Manager::SCRIPT_UTILS,
			),
			TOOLSET_VERSION
		);

		$asset_manager->register_script(
			self::SCRIPT_GUI_JQUERY_COLLAPSIBLE,
			$this->get_gui_base_url( '/js/jquery/collapsible.js' ),
			array(
				'jquery'
			),
			TOOLSET_VERSION
		);
		
		$asset_manager->register_style(
			self::STYLE_GUI_BASE,
			$this->get_gui_base_url( '/toolset-gui-base.css' ),
			array(),
			TOOLSET_VERSION
		);


	}

}
