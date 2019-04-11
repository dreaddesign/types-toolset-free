<?php

if ( ! class_exists( 'Toolset_Bootstrap_Loader' ) ) {

    Class Toolset_Bootstrap_Loader
    {
        private static $instance;

        function __construct() {
            $settings = Toolset_Settings::get_instance();
            if( $settings->toolset_bootstrap_version === "3.toolset" ){
                add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_bootstrap_in_front_end') );
            }

        }

        public static function getInstance() {
            if( !self::$instance ) {
                self::$instance = new Toolset_Bootstrap_Loader();
            }

            return self::$instance;
        }


        public function enqueue_bootstrap_in_front_end()
        {

            do_action( 'toolset_enqueue_styles', array(
                'toolset_bootstrap_styles',
            ) );

            do_action( 'toolset_enqueue_scripts', array(
                'toolset_bootstrap',
            ) );

        }
    }
}
