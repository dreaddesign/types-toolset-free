<?php

if ( defined( 'WPT_ASSETS_MANAGER' ) ) {
    return; 
}

define( 'WPT_ASSETS_MANAGER', true );

class Toolset_Style {

	public function __construct( $handle, $path = 'wordpress_default', $deps = array(), $ver = false, $media = 'screen' ) {
		$this->handle = $handle;
		$this->path   = $path;
		$this->deps   = $deps;
		$this->ver    = $ver;
		$this->media  = $media;

		if (
			$this->compare_versions()
			&& $this->path != 'wordpress_default'
		) {
			wp_register_style( $this->handle, $this->path, $this->deps, $this->ver, $this->media );
		}
	}

	public function enqueue() {
		if ( $this->is_enqueued() === false ) {
			wp_enqueue_style( $this->handle );
		}
	}

	protected function compare_versions() {
		global $wp_styles;

		if ( isset( $wp_styles->registered ) ) {
			$wpt_registered_styles = $wp_styles->registered;
			if ( isset( $wpt_registered_styles[ $this->handle ] ) ) {
				$registered = $wpt_registered_styles[ $this->handle ];
				if ( (float) $registered->ver < (float) $this->ver ) {
					$wp_styles->remove( $this->handle );

					return true;
				} else {
					return false;
				}
			}
		}

		return $this->is_registered() === false;
	}

	public function deregister() {
		if ( $this->is_registered() !== false ) {
			wp_deregister_style( $this->handle );
		}
	}


	protected function is_registered() {
		return wp_style_is( $this->handle, 'registered' );
	}

	protected function is_enqueued() {
		return wp_style_is( $this->handle, 'enqueued' );
	}
}

class Toolset_Script {

	public function __construct( $handle, $path = 'wordpress_default', $deps = array(), $ver = false, $in_footer = false ) {
		$this->handle    = $handle;
		$this->path      = $path;
		$this->deps      = $deps;
		$this->ver       = $ver;
		$this->in_footer = $in_footer;

		if (
			$this->compare_versions()
			&& $this->path != 'wordpress_default'
		) {
			wp_register_script( $this->handle, $this->path, $this->deps, $this->ver, $this->in_footer );
		}
	}

	public function enqueue() {
		if ( $this->is_enqueued() === false ) {
			wp_enqueue_script( $this->handle );
		}
	}

	protected function compare_versions() {
		global $wp_scripts;

		if ( isset( $wp_scripts->registered ) ) {
			$wpt_registered_scripts = $wp_scripts->registered;
			if ( isset( $wpt_registered_scripts[ $this->handle ] ) ) {
				$registered = $wpt_registered_scripts[ $this->handle ];
				if ( (float) $registered->ver < (float) $this->ver ) {
					$wp_scripts->remove( $this->handle );

					return true;
				} else {
					return false;
				}
			}
		}

		return $this->is_registered() === false;
	}

	public function localize( $object, $args ) {
		if ( $this->is_registered() ) {
			wp_localize_script( $this->handle, $object, $args );
		}
	}

	public function deregister() {
		if ( $this->is_registered() !== false ) {
			wp_deregister_script( $this->handle );
		}
	}

	protected function is_registered() {
		return wp_script_is( $this->handle, 'registered' );
	}

	protected function is_enqueued() {
		return wp_script_is( $this->handle, 'enqueued' );
	}
}



class Toolset_Assets_Manager {


	private static $instance;


	protected $styles = array();


	protected $scripts = array();


	/*
	 * All asset handles should be defined here as constants.
	 *
	 * NEVER EVER use handles defined here as hardcoded strings, they may change at any time.
	 */


	// Scripts
	//
	//
	const SCRIPT_BOOTSTRAP = 'toolset_bootstrap';

	const SCRIPT_CODEMIRROR = 'toolset-codemirror-script';
	const SCRIPT_CODEMIRROR_CSS = 'toolset-meta-html-codemirror-css-script';
	const SCRIPT_CODEMIRROR_XML = 'toolset-meta-html-codemirror-xml-script';
	const SCRIPT_CODEMIRROR_HTMLMIXED = 'toolset-meta-html-codemirror-htmlmixed-script';
	const SCRIPT_CODEMIRROR_JS = 'toolset-meta-html-codemirror-js-script';
	const SCRIPT_CODEMIRROR_OVERLAY = 'toolset-meta-html-codemirror-overlay-script';
	const SCRIPT_CODEMIRROR_UTILS_SEARCH = 'toolset-meta-html-codemirror-utils-search';
	const SCRIPT_CODEMIRROR_UTILS_SEARCH_CURSOR = 'toolset-meta-html-codemirror-utils-search-cursor';
	const SCRIPT_CODEMIRROR_UTILS_PANEL = 'toolset-meta-html-codemirror-utils-panel';
	const SCRIPT_CODEMIRROR_UTILS_HINT = 'toolset-meta-html-codemirror-utils-hint';
	const SCRIPT_CODEMIRROR_UTILS_HINT_CSS = 'toolset-meta-html-codemirror-utils-hint-css';

	const SCRIPT_COLORBOX = 'toolset-colorbox';
	const SCRIPT_HEADJS = 'headjs';

	const SCRIPT_ICL_EDITOR = 'icl_editor-script';
	const SCRIPT_ICL_MEDIA_MANAGER = 'icl_media-manager-js';
	
	const SCRIPT_TOOLSET_MEDIA_MANAGER = 'toolset-media-manager-js';

	const SCRIPT_KNOCKOUT = 'knockout';
	const SCRIPT_KNOCKOUT_MAPPING = 'knockout-mapping';
	const SCRIPT_JSCROLLPANE = 'toolset-jscrollpane';
	const SCRIPT_JSTORAGE = 'jstorage';
	const SCRIPT_MOUSEWHEEL = 'toolset-mousewheel';
	const SCRIPT_SELECT2 = 'toolset_select2';
	const SCRIPT_SELECT2_COMPATIBILITY = 'toolset-select2-compatibility';

	const SCRIPT_TOOLSET_EVENT_MANAGER = 'toolset-event-manager';
	const SCRIPT_TOOLSET_PARSER = 'wptoolset-parser';
	const SCRIPT_TOOLSET_PROMOTION = 'toolset-promotion';
	const SCRIPT_TOOLSET_SETTINGS = 'toolset-settings';
	const SCRIPT_TOOLSET_EXPORT_IMPORT = 'toolset-export-import';

	const SCRIPT_UTILS = 'toolset-utils';

	// chosen lib
	const SCRIPT_CHOSEN = 'toolset-chosen';
	const SCRIPT_CHOSEN_WRAPPER = 'toolset-chosen-wrapper';

	// parsley lib for field validation
	const SCRIPT_PARSLEY = 'toolset-parsley';


	/**
	 * For compatibility with ACF Plugin that's not using the right handle for this module (wp-event-manager)
	 * we are using ACF handle to prevent unwanted overrides of window.wp.hooks namespace (******!)
	 */
	const SCRIPT_WP_EVENT_MANAGER = 'acf-input';
	
	const SCRIPT_TOOLSET_SHORTCODE = 'toolset-shortcode';

	// Styles
	//
	//

	const STYLE_PARSLEY = 'toolset-parsley-style';

	const STYLE_CODEMIRROR = 'toolset-meta-html-codemirror-css';
	const STYLE_CODEMIRROR_CSS_HINT = 'toolset-meta-html-codemirror-css-hint-css';

	const STYLE_COLORBOX = 'toolset-colorbox';

	const STYLE_DDL_DIALOGS_FORMS = 'ddl-dialogs-forms-css';
	const STYLE_DDL_DIALOGS_GENERAL = 'ddl-dialogs-general-css';
	const STYLE_DDL_DIALOGS = 'ddl-dialogs-css';

	const STYLE_EDITOR_ADDON_MENU = 'editor_addon_menu';
	const STYLE_EDITOR_ADDON_MENU_SCROLL = 'editor_addon_menu_scroll';

	const STYLE_FONT_AWESOME = 'font-awesome';
	const STYLE_NOTIFICATIONS = 'toolset-notifications-css';

	const STYLE_SELECT2_CSS = 'toolset-select2-css';

	// These two are identical
	const STYLE_SELECT2_CSS_LAYOUTS_OVERRIDES = 'layouts-select2-overrides-css';
	const STYLE_SELECT2_CSS_OVERRIDES = 'toolset-select2-overrides-css';

	const STYLE_TOOLSET_BOOTSTRAP = 'toolset_bootstrap_styles';
	const STYLE_TOOLSET_COMMON = 'toolset-common';
	const STYLE_TOOLSET_DIALOGS_OVERRIDES = 'toolset-dialogs-overrides-css';
	const STYLE_TOOLSET_FORMS_BACKEND = 'wpt-toolset-backend';
	const STYLE_TOOLSET_PROMOTION = 'toolset-promotion';



	// chosen lib
	const STYLE_TOOLSET_CHOSEN = 'toolset-chosen-styles';

	// WordPress Core handles
	//
	//
	const STYLE_JQUERY_UI_DIALOG = 'wp-jquery-ui-dialog';

	const STYLE_ONTHEGOSYSTEMS_ICONS = 'onthegosystems-icons';


	/**
	 * assets_url
	 *
	 * Base URL for the Toolset Common instance.
	 *
	 * @note Does not have a trailing slash due to untrailingslashit, add it when registering each asset.
	 * @since 2.0
	 */
	protected $assets_url = '';

	private $did_initialize_scripts = false;
	private $did_initialize_styles = false;


	protected function __construct() {

		if ( is_admin() ) {
			$this->assets_url = TOOLSET_COMMON_URL;
		} else {
			$this->assets_url = TOOLSET_COMMON_FRONTEND_URL;
		}

		$this->assets_url = untrailingslashit( $this->assets_url );

		add_action( 'init', array( $this, 'init' ), 99 );

		// Backend
		add_action( 'admin_enqueue_scripts', array( $this, 'get_rid_of_default_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'get_rid_of_default_styles' ) );

		// Front-end
		add_action( 'wp_enqueue_scripts', array( $this, 'get_rid_of_default_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'get_rid_of_default_styles' ) );

		add_action( 'toolset_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 1 );
		add_action( 'toolset_enqueue_styles', array( $this, 'enqueue_styles' ), 10, 1 );
		add_action( 'toolset_localize_script', array( $this, 'localize_script' ), 10, 3 );
	}

	private static $instances = array();

	/**
	 * @return Toolset_Assets_Manager
	 * @deprecated Use get_instance instead().
	 */
	final public static function getInstance() {
		$called_class = get_called_class();

		if ( isset( self::$instances[ $called_class ] ) ) {
			return self::$instances[ $called_class ];
		} else {
			if ( class_exists( $called_class ) ) {
				self::$instances[ $called_class ] = new $called_class();

				return self::$instances[ $called_class ];
			} else {
				// This can unfortunately happen when the get_called_class() workaround for PHP 5.2 misbehaves.
				return false;
			}
		}
	}


	/**
	 * Note: This *can not* be directly used in subclasses.
	 *
	 * @return Toolset_Assets_Manager
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			$called_class = get_called_class();
			if( $called_class === 'Toolset_Assets_Manager' && isset( self::$instances[ $called_class ] ) ) {
				// Make sure that we don't re-instantiate the Toolset_Assets_Manager class even if it was instantiated
				// before through the legacy getInstance() method.
				//
				// @refactoring Stop subclassing Toolset_Assets_Manager
				self::$instance = self::$instances[ $called_class ];
			} else {
				self::$instance = new self();
			}
		}

		return self::$instance;
	}

	/**
	 * Backward compatibility
	 * For PHP 7 we renamed the method __initialize_styles() and __initialize_scripts() to initialize_styles() and initialize_scripts().
	 * As both are public methods we apply this fallback for the case someone calls the old methods.
	 *
	 * @param $method
	 * @param $arguments
	 */
	public function __call( $method, $arguments ) {
		switch( $method ) {
			case '__initialize_styles':
				$this->initialize_styles();
				break;
			case '__initialize_scripts':
				$this->initialize_scripts();
				break;
		}
	}


	public function init() {
		$this->initialize_styles();
		$this->initialize_scripts();
	}


	public function get_assets_url() {
		return $this->assets_url;
	}


	/**
	 * Pushes to our scripts array other scripts so we can enqueue using our methods
	 *
	 * @return void
	 */
	public function get_rid_of_default_scripts() {
		global $wp_scripts;
		if ( is_array( $wp_scripts->registered ) ) {
			foreach ( $wp_scripts->registered as $registered ) {
				$this->scripts[ $registered->handle ] = new Toolset_Script( $registered->handle );
			}
		}
	}

	/**
	 * Pushes to our scripts array other scripts so we can enqueue using our methods
	 *
	 * @return void
	 */
	public function get_rid_of_default_styles() {
		global $wp_styles;

		if ( is_array( $wp_styles->registered ) ) {
			foreach ( $wp_styles->registered as $registered ) {
				$this->styles[ $registered->handle ] = new Toolset_Style( $registered->handle );
			}
		}
	}


	protected function initialize_styles() {

		// Prevent an infinite recursion in case we have a subclass of this that has:
		//
		// function __initialize_styles() {
		//     // ...
		//     return parent::__initialize_styles();
		// }
		if( $this->did_initialize_styles ) {
			return null;
		}
		$this->did_initialize_styles = true;

		if( method_exists( $this, '__initialize_styles' ) ) {
			// Support for subclasses overwriting the previous __initialize_styles function.
			//
			// This will cause the common assets never to be registered in these subclasses,
			// but we don't mind - these assets are already registered by Toolset_Assets_Manager
			// on every request.
			return $this->__initialize_styles();
		}

		// Libraries
		//
		//

		$this->register_style(
			self::STYLE_SELECT2_CSS,
			$this->assets_url . '/res/lib/select2/select2.css'
		);

		$this->register_style(
			self::STYLE_SELECT2_CSS_LAYOUTS_OVERRIDES,
			$this->assets_url . '/res/lib/select2/select2-overrides.css'
		);

		$this->register_style(
			self::STYLE_FONT_AWESOME,
			$this->assets_url . '/res/lib/font-awesome/css/font-awesome.min.css',
			array(),
			'4.7.0',
			'screen'
		);

		$this->register_style(
			self::STYLE_CODEMIRROR,
			$this->assets_url . '/visual-editor/res/js/codemirror/lib/codemirror.css',
			array(),
			"5.5.0"
		);

		$this->register_style(
			self::STYLE_CODEMIRROR_CSS_HINT,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/hint/show-hint.css',
			array(),
			"5.5.0"
		);

		$this->register_style(
			self::STYLE_PARSLEY,
			$this->assets_url . '/res/lib/parsley/parsley.css',
			array(),
			'2.8.0'
		);

		$this->register_style(
			self::STYLE_COLORBOX,
			$this->assets_url . '/res/lib/colorbox/colorbox.css',
			array(),
			'1.4.31'
		);

		$this->register_style(
			self::STYLE_TOOLSET_CHOSEN,
			$this->assets_url . '/res/lib/chosen/chosen.css',
			array(),
			'1.6.2'
		);



		// Custom styles
		//
		//

		$this->register_style(
			self::STYLE_SELECT2_CSS_OVERRIDES,
			$this->assets_url . '/res/lib/select2/select2-overrides.css',
			array( 'toolset-select2-css' )
		);

		$this->register_style(
			self::STYLE_TOOLSET_FORMS_BACKEND,
			$this->assets_url . '/toolset-forms/css/wpt-toolset-backend.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_NOTIFICATIONS,
			$this->assets_url . '/res/css/toolset-notifications.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_TOOLSET_COMMON,
			$this->assets_url . '/res/css/toolset-common.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_TOOLSET_PROMOTION,
			$this->assets_url . '/res/css/toolset-promotion.css',
			array( self::STYLE_COLORBOX, 'onthego-admin-styles' ),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_EDITOR_ADDON_MENU,
			$this->assets_url . '/visual-editor/res/css/pro_dropdown_2.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_EDITOR_ADDON_MENU_SCROLL,
			$this->assets_url . '/visual-editor/res/css/scroll.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_TOOLSET_BOOTSTRAP,
			$this->assets_url . '/res/lib/bootstrap/css/bootstrap.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_DDL_DIALOGS_FORMS,
			$this->assets_url . '/utility/dialogs/css/dd-dialogs-forms.css',
			TOOLSET_VERSION
		);

		$this->register_style(
			self::STYLE_DDL_DIALOGS_GENERAL,
			$this->assets_url . '/utility/dialogs/css/dd-dialogs-general.css',
			array( self::STYLE_JQUERY_UI_DIALOG ),
			TOOLSET_VERSION
		);

		$this->register_style(
			self::STYLE_DDL_DIALOGS,
			$this->assets_url . '/utility/dialogs/css/dd-dialogs.css',
			array( self::STYLE_DDL_DIALOGS_GENERAL ),
			TOOLSET_VERSION
		);

		$this->register_style(
			self::STYLE_TOOLSET_DIALOGS_OVERRIDES,
			$this->assets_url . '/res/css/toolset-dialogs.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$this->register_style(
			self::STYLE_ONTHEGOSYSTEMS_ICONS,
			ON_THE_GO_SYSTEMS_BRANDING_REL_PATH . 'onthegosystems-icons/css/onthegosystems-icons.css',
			array( self::STYLE_TOOLSET_COMMON ),
			TOOLSET_COMMON_VERSION
		);

		return apply_filters( 'toolset_add_registered_styles', $this->styles );
	}


	protected function initialize_scripts() {

		// Prevent an infinite recursion in case we have a subclass of this that has:
		//
		// function __initialize_scripts() {
		//     // ...
		//     return parent::__initialize_scripts();
		// }
		if( $this->did_initialize_scripts ) {
			return null;
		}
		$this->did_initialize_scripts = true;

		if( method_exists( $this, '__initialize_scripts' ) ) {
			// Support for subclasses overwriting the previous __initialize_scripts function.
			//
			// This will cause the common assets never to be registered in these subclasses,
			// but we don't mind - these assets are already registered by Toolset_Assets_Manager
			// on every request.
			return $this->__initialize_scripts();
		}

		// Libraries
		//
		//

		$this->register_script(
			self::SCRIPT_HEADJS,
			$this->assets_url . "/res/lib/head.min.js",
			array(),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_JSTORAGE,
			$this->assets_url . "/res/lib/jstorage.min.js",
			array(),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_SELECT2_COMPATIBILITY,
			$this->assets_url . "/res/js/toolset-select2-compatibility.js",
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_SELECT2,
			$this->assets_url . "/res/lib/select2/select2.js",
			array( 'jquery', self::SCRIPT_SELECT2_COMPATIBILITY ),
			'4.0.3',
			true
		);

		$this->register_script(
			self::SCRIPT_CHOSEN_WRAPPER,
			$this->assets_url . "/res/js/toolset-chosen-wrapper.js",
			array( 'jquery', self::SCRIPT_CHOSEN ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_PARSLEY,
			$this->assets_url . '/res/lib/parsley/parsley.js',
			array('jquery'),
			'2.8.0',
			true
		);

		$this->register_script(
			self::SCRIPT_CHOSEN,
			$this->assets_url . "/res/lib/chosen/chosen.jquery.js",
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			'1.6.2',
			true
		);

		$this->register_script(
			self::SCRIPT_COLORBOX,
			$this->assets_url . "/res/lib/colorbox/jquery.colorbox-min.js",
			array( 'jquery' ),
			'1.4.31',
			true
		);

		$this->register_script(
			self::SCRIPT_BOOTSTRAP,
			$this->assets_url . '/res/lib/bootstrap/js/' . $this->choose_script_version( 'bootstrap.min.js', 'bootstrap.js' ),
			array( 'jquery' ),
			'3.3.7',
			true
		);

		$this->register_script(
			self::SCRIPT_KNOCKOUT,
			$this->assets_url . '/res/lib/knockout/' . $this->choose_script_version( 'knockout-3.4.0.js', 'knockout-3.4.0.debug.js' ),
			array(),
			'3.4.0'
		);

		$this->register_script(
			self::SCRIPT_KNOCKOUT_MAPPING,
			$this->assets_url . '/res/lib/knockout/knockout.mapping.js',
			array( self::SCRIPT_KNOCKOUT ),
			'1.0.0'
		);

		$this->register_script(
			self::SCRIPT_WP_EVENT_MANAGER,
			$this->assets_url . "/res/lib/events-manager/event-manager.min.js",
			array(),
			'1.0',
			true
		);

		$this->register_script(
			self::SCRIPT_TOOLSET_EVENT_MANAGER,
			$this->assets_url . "/res/lib/toolset-event-manager/toolset-event-manager.min.js",
			array(),
			'1.0',
			true
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR,
			$this->assets_url . '/visual-editor/res/js/codemirror/lib/codemirror.js',
			array( 'jquery' ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_OVERLAY,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/mode/overlay.js',
			array( self::SCRIPT_CODEMIRROR ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_XML,
			$this->assets_url . '/visual-editor/res/js/codemirror/mode/xml/xml.js',
			array( self::SCRIPT_CODEMIRROR_OVERLAY ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_CSS,
			$this->assets_url . '/visual-editor/res/js/codemirror/mode/css/css.js',
			array( self::SCRIPT_CODEMIRROR_OVERLAY ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_HTMLMIXED,
			$this->assets_url . '/visual-editor/res/js/codemirror/mode/htmlmixed/htmlmixed.js',
			array( self::SCRIPT_CODEMIRROR_OVERLAY ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_JS,
			$this->assets_url . '/visual-editor/res/js/codemirror/mode/javascript/javascript.js',
			array( self::SCRIPT_CODEMIRROR_OVERLAY ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_UTILS_SEARCH,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/search/search.js',
			array( self::SCRIPT_CODEMIRROR ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_UTILS_SEARCH_CURSOR,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/search/searchcursor.js',
			array( self::SCRIPT_CODEMIRROR_UTILS_SEARCH ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_UTILS_HINT,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/hint/show-hint.js',
			array( self::SCRIPT_CODEMIRROR ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_UTILS_HINT_CSS,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/hint/css-hint.js',
			array( self::SCRIPT_CODEMIRROR_UTILS_HINT ),
			"5.5.0"
		);

		$this->register_script(
			self::SCRIPT_CODEMIRROR_UTILS_PANEL,
			$this->assets_url . '/visual-editor/res/js/codemirror/addon/display/panel.js',
			array( self::SCRIPT_CODEMIRROR ),
			"5.5.0"
		);

		// Custom scripts
		//
		//

		$this->register_script(
			self::SCRIPT_UTILS,
			$this->assets_url . "/utility/js/utils.js",
			array( 'jquery', 'underscore', 'backbone', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-dialog' ),
			'1.2.2',
			true
		);

		$this->register_script(
			self::SCRIPT_ICL_EDITOR,
			$this->assets_url . '/visual-editor/res/js/icl_editor_addon_plugin.js',
			array( 'jquery', 'quicktags', 'wplink', self::SCRIPT_CODEMIRROR ),
			TOOLSET_COMMON_VERSION
		);

		$this->register_script(
			self::SCRIPT_ICL_MEDIA_MANAGER,
			$this->assets_url . '/visual-editor/res/js/icl_media_manager.js',
			array( self::SCRIPT_ICL_EDITOR ),
			TOOLSET_COMMON_VERSION
		);
		
		$this->register_script(
			self::SCRIPT_TOOLSET_MEDIA_MANAGER,
			$this->assets_url . "/res/js/toolset-media-manager.js",
			array( self::SCRIPT_ICL_EDITOR, self::SCRIPT_TOOLSET_EVENT_MANAGER ),
			TOOLSET_COMMON_VERSION
		);

		$this->register_script(
			self::SCRIPT_JSCROLLPANE,
			"{$this->assets_url}/visual-editor/res/js/scrollbar.js",
			array('jquery'),
			'2.0.0'
		);


		$this->register_script(
			self::SCRIPT_MOUSEWHEEL,
			"{$this->assets_url}/visual-editor/res/js/mousewheel.js",
			array( self::SCRIPT_JSCROLLPANE ),
			TOOLSET_COMMON_VERSION
		);


		$this->register_script(
			self::SCRIPT_TOOLSET_PARSER,
			$this->assets_url . '/res/js/toolset-parser.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_TOOLSET_PROMOTION,
			$this->assets_url . "/res/js/toolset-promotion.js",
			array( 'underscore', self::SCRIPT_COLORBOX ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_TOOLSET_SETTINGS,
			$this->assets_url . "/res/js/toolset-settings.js",
			array( 'jquery', 'underscore', self::SCRIPT_UTILS ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->register_script(
			self::SCRIPT_TOOLSET_EXPORT_IMPORT,
			$this->assets_url . "/res/js/toolset-export-import.js",
			array( 'jquery', 'underscore' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$this->localize_script(
			self::SCRIPT_UTILS,
			'toolset_utils_texts',
			array(
				'wpv_dont_show_it_again' => __( "Got it! Don't show this message again", 'wpv-views' ),
				'wpv_close' => __( 'Close', 'wpv-views' )
			)
		);

		$this->localize_script(
			self::SCRIPT_ICL_EDITOR,
			'icl_editor_localization_texts',
			array(
				'wpv_insert_conditional_shortcode' => __( 'Insert conditional shortcode', 'wpv-views' ),
				'wpv_conditional_button' => __( 'conditional output', 'wpv-views' ),
				'wpv_editor_callback_nonce' => wp_create_nonce( 'wpv_editor_callback' )
			)
		);

		$this->localize_script(
			self::SCRIPT_ICL_MEDIA_MANAGER,
			'icl_media_manager',
			array(
				'only_img_allowed_here' => __( "You can only use an image file here", 'wpv-views' )
			)
		);

		Toolset_Settings::get_instance();

		$this->localize_script(
			self::SCRIPT_TOOLSET_SETTINGS,
			'toolset_settings_texts',
			array(
				'autosave_saving' => '<i class="fa fa-refresh fa-spin"></i>' . __( 'Saving...', 'wpv-views' ),
				'autosave_saved' => '<i class="fa fa-check"></i>' . __( 'All changes saved', 'wpv-views' ),
				'autosave_failed' => '<i class="fa fa-exclamation-triangle"></i>' . __( 'Saving failed. Please reload the page and try again.', 'wpv-views' ),
				'toolset_bootstrap_version_filter' => apply_filters( 'toolset-toolset_bootstrap_version_filter', false ),
				'toolset_bootstrap_version_selected' => apply_filters( 'toolset-toolset_bootstrap_version_manually_selected', false ),
				'toolset_theme_loads_own_bs' => __( 'This theme loads its own version of Bootstrap. You should select this option to avoid loading Bootstrap twice and causing display problems on the site\'s front-end', 'wpv-views' )
			)
		);
		
		$this->register_script(
			self::SCRIPT_TOOLSET_SHORTCODE,
			$this->assets_url . "/res/js/toolset-shortcode.js",
			array( 
				'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'suggest', 'shortcode', 'underscore', 'wp-util', 'wp-pointer', 
				self::SCRIPT_SELECT2, self::SCRIPT_ICL_EDITOR, self::SCRIPT_UTILS, self::SCRIPT_TOOLSET_EVENT_MANAGER 
			),
			TOOLSET_COMMON_VERSION,
			true
		);
		
		global $pagenow;
		$toolset_shortcode_i18n = array(
			'action' => array(
				'insert'  => __( 'Insert shortcode', 'wpv-views' ),
				'create'  => __( 'Create shortcode', 'wpv-views' ),
				'update'  => __( 'Update shortcode', 'wpv-views' ),
				'close'   => __( 'Close', 'wpv-views' ),
				'cancel'  => __( 'Cancel', 'wpv-views' ),
				'back'    => __( 'Back', 'wpv-views' ),
				'save'    => __( 'Save settings', 'wpv-views' ),
				'loading' => __( 'Loading...', 'wpv-views' ),
			),
			'title' => array(
				'generated' => __( 'Generated shortcode', 'wpv-views' ),
			),
			'validation' => array(
				'mandatory'  => __( 'This option is mandatory ', 'wpv-views' ),
				'number'     => __( 'Please enter a valid number', 'wpv-views' ),
				'numberlist' => __( 'Please enter a valid comma separated number list', 'wpv-views' ),
				'url'        => __( 'Please enter a valid URL', 'wpv-views' ),
				
			),
			'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' )  ),
			'pagenow' => $pagenow
		);
		
		$toolset_shortcode_i18n = apply_filters( 'toolset_filter_shortcode_script_i18n', $toolset_shortcode_i18n );
		
		$this->localize_script(
			self::SCRIPT_TOOLSET_SHORTCODE,
			'toolset_shortcode_i18n',
			$toolset_shortcode_i18n
		);

		return apply_filters( 'toolset_add_registered_script', $this->scripts );
	}


	public function enqueue_scripts( $handles ) {
		if ( is_array( $handles ) ) {
			foreach ( $handles as $handle ) {
				if ( isset( $this->scripts[ $handle ] ) ) {
					$this->scripts[ $handle ]->enqueue();
				}
			}
		} else if ( is_string( $handles ) ) {
			if ( isset( $this->scripts[ $handles ] ) ) {
				$this->scripts[ $handles ]->enqueue();
			}
		}
	}


	public function enqueue_styles( $handles ) {
		if ( is_array( $handles ) ) {
			foreach ( $handles as $handle ) {
				if ( isset( $this->styles[ $handle ] ) ) {
					$this->styles[ $handle ]->enqueue();
				}
			}
		} else if ( is_string( $handles ) ) {
			if ( isset( $this->styles[ $handles ] ) ) {
				$this->styles[ $handles ]->enqueue();
			}
		}
	}


	public function deregister_scripts( $handles ) {
		if ( is_array( $handles ) ) {
			foreach ( $handles as $handle ) {
				if ( isset( $this->scripts[ $handle ] ) ) {
					$this->scripts[ $handle ]->deregister();
					unset( $this->scripts[ $handle ] );
				}
			}
		} else if ( is_string( $handles ) ) {
			if ( isset( $this->scripts[ $handles ] ) ) {
				$this->scripts[ $handles ]->deregister();
				unset( $this->scripts[ $handles ] );
			}
		}
	}


	public function deregister_styles( $handles ) {
		if ( is_array( $handles ) ) {
			foreach ( $handles as $handle ) {
				if ( isset( $this->styles[ $handle ] ) ) {
					$this->styles[ $handle ]->deregister();
					unset( $this->styles[ $handle ] );
				}
			}
		} else if ( is_string( $handles ) ) {
			if ( isset( $this->styles[ $handles ] ) ) {
				$this->styles[ $handles ]->deregister();
				unset( $this->styles[ $handles ] );
			}
		}
	}


	public function add_script( Toolset_Script $script ) {
		if( isset( $this->scripts[ $script->handle ] ) ) {
			return;
		}

		$this->scripts[ $script->handle ] = $script;
	}


	public function register_script( $handle, $path = '', $deps = array(), $ver = false, $in_footer = false ) {
		if ( ! isset( $this->scripts[ $handle ] ) ) {
			$this->scripts[ $handle ] = new Toolset_Script( $handle, $path, $deps, $ver, $in_footer );
		}
	}


	public function register_style( $handle, $path = '', $deps = array(), $ver = false, $media = 'screen' ) {
		if ( ! isset( $this->styles[ $handle ] ) ) {
			$this->styles[ $handle ] = new Toolset_Style( $handle, $path, $deps, $ver, $media );
		}
	}


	public function localize_script( $handle, $object, $args ) {
		if ( isset( $this->scripts[ $handle ] ) ) {
			$this->scripts[ $handle ]->localize( $object, $args );
		}
	}


	/**
	 * Decide if WordPress is in the "script debug" mode where it includes all its script in a non-minified form.
	 *
	 * If it is, we should do the same.
	 *
	 * @return bool
	 * @since 2.3
	 */
	public function is_script_debug_mode_on() {
		return ( defined( 'SCRIPT_DEBUG' ) && true == SCRIPT_DEBUG );
	}


	/**
	 * Choose a production (usually minified) or debugging (non-minified) version of
	 * a script depending on the script debugging mode.
	 *
	 * See SCRIPT_DEBUG constant
	 *
	 * @param string $production_version File name of the production script version.
	 * @param string $debugging_version File name of the debugging script version.
	 *
	 * @return string
	 * @since 2.2.7
	 */
	private function choose_script_version( $production_version, $debugging_version ) {
		return ( $this->is_script_debug_mode_on() ? $debugging_version : $production_version );
	}

}
