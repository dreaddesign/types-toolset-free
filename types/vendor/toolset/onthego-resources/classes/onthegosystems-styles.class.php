<?php

define('ON_THE_GO_SYSTEMS_BRANDING_STYLES_CLASS_PATH', dirname(__FILE__) );

class OnTheGoSystemsStyles_Class{

    private static $instance;

    /**
     * Class is singleton
     */
    private function __construct( )
    {
		// Register on wp_loaded:10 because this is instantiated after init
		add_action( 'wp_loaded', array( &$this, 'register_styles' ) );
        // Load in wp-admin
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
        // Load in front-end
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
		// Load on demand
		add_action( 'otg_action_otg_enforce_styles', array( &$this, 'enforce_enqueue_styles' ) );
    }
	
	public function register_styles() {
		wp_register_style('onthego-admin-styles', ON_THE_GO_SYSTEMS_BRANDING_REL_PATH .'onthego-styles/onthego-styles.css');
	}

    public function enqueue_styles()
    {
        if ( 
			is_admin() 
			|| defined('WPDDL_VERSION') 
		) {
            wp_enqueue_style( 'onthego-admin-styles' );
        }
    }
	
	public function enforce_enqueue_styles() {
		if ( ! wp_style_is( 'onthego-admin-styles' ) ) {
			wp_enqueue_style( 'onthego-admin-styles' );
		}
	}

    public static function getInstance( )
    {
        if (!self::$instance)
        {
            self::$instance = new OnTheGoSystemsStyles_Class();
        }

        return self::$instance;
    }
};
