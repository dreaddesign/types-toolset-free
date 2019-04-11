<?php

if ( ! defined( 'WPT_PROMOTION' ) ) {
    define( 'WPT_PROMOTION', true );
}

/**
* Toolset_Promotion
*
* As of now, displays promotional messages on embedded versions of Toolset plugins.
*
* @since unknown
*/

if ( ! class_exists( 'Toolset_Promotion', false ) ) {

    /**
     * Class to show promotion message.
     *
     * @since 1.5
     * @access  public
     */
    class Toolset_Promotion
    {

        public function __construct()
        {
            add_action( 'admin_init',				array( $this, 'admin_init' ) );
            add_action( 'admin_footer',				array( $this, 'admin_footer' ) );
            add_action( 'admin_enqueue_scripts',	array( $this, 'admin_enqueue_scripts' ) );
			// @todo wow, this needs and action on the onthego-resources dependency, not here...
            add_action( 'plugins_loaded',			'on_the_go_systems_branding_plugins_loaded' );
        }

        public function admin_init() {
            
        }

        /**
         * Enqueue scripts & styles
         *
         * After check is a correct place, this function enqueue scripts & styles
         * for toolset promotion box.
         *
         * @since 1.5
         *
         */
        public function admin_enqueue_scripts() {
            if (
				! is_admin() 
				|| ! function_exists( 'get_current_screen' )
			) {
                return;
            }
            /**
             * List of admin page id
             *
             * Filter allow to add or change list of admin screen id for checking
             * where we need enqueue toolset promotion assets.
             *
             * @since 1.5
             *
             * @param array $screen_ids List of admin page screen ids.
             *
             */
            $screen_ids = apply_filters( 'toolset_promotion_screen_ids', array() );
            if ( empty( $screen_ids ) ) {
                return;
            }
            $screen = get_current_screen();
            if ( ! in_array( $screen->id, $screen_ids ) ) {
                return;
            }
            wp_enqueue_style( 'toolset-promotion' );
            wp_enqueue_script( 'toolset-promotion' );
        }

        /**
         * Print in footer
         *
         * Print nessary elemnt in admin footer
         *
         * @since 1.5
         *
         */
        public function admin_footer() {
			$link_button_args = array(
				'hash'	=>	'buy-toolset'
			);
			$link_learn =	$this->get_affiliate_promotional_link( 'https://toolset.com/' );
			$link_button =	$this->get_affiliate_promotional_link( 'https://toolset.com/', $link_button_args );

            ob_start();
            ?>

            <div class="ddl-dialogs-container">
                <div id="js-buy-toolset-embedded-message-wrap"></div>
            </div>
            <script type="text/html" id="js-buy-toolset-embedded-message">
                <div class="toolset-modal">
                    <h2><?php _e('Want to edit Views, CRED forms and Layouts? Get the full <em>Toolset</em> package!', 'wpcf'); ?></h2>

                    <div class="content">
                        <p class="full"><?php _e('The full <em>Toolset</em> package allows you to develop and customize themes without touching PHP. You will be able to:', 'wpcf'); ?></p>

                        <div class="icons">
                            <ul>
                                <li class="template"><?php _e('Create templates', 'wpcf'); ?></li>
                                <li class="layout"><?php _e('Design page layouts using drag-and-drop', 'wpcf'); ?></li>
                                <li class="toolset-search"><?php _e('Build custom searches', 'wpcf'); ?></li>
                            </ul>
                            <ul>
                                <li class="list"><?php _e('Display lists of content', 'wpcf'); ?></li>
                                <li class="form"><?php _e('Create front-end content editing forms', 'wpcf'); ?></li>
                                <li class="more"><?php _e('and more…', 'wpcf'); ?></li>
                            </ul>
                        </div>

                        <p class="description"><?php _e('Once you buy the full Toolset, you will be able to edit Views, CRED forms and Layouts in your site, as well as build new ones.', 'wpcf'); ?></p>

                        <a href="<?php echo $link_button; ?>"
                           class="button"><?php _e('<em>Toolset</em> Package Options', 'wpcf'); ?></a>
                        <a href="<?php echo $link_learn; ?>"
                           class="learn"><?php _e('Learn more about <em>Toolset</em>', 'wpcf'); ?></a>

                    </div>
                    <span class="icon-toolset-logo"></span>
                    <span class="js-close-promotional-message"></span>
                </div>
            </script>
            <?php
            echo ob_get_clean();
        }
		
		/**
		* get_affiliate_promotional_link
		*
		* @param $url	string
		* @param $args	array
		* 		@param query	array
		* 		@param anchor	string
		*
		* @return string
		*
		* @since 1.9
		*/
		
		private function get_affiliate_promotional_link( $url, $args = array() ) {
			if ( function_exists( 'installer_ep_get_configuration' ) === false ) {
                return $url;
            }

            $info = installer_ep_get_configuration( wp_get_theme()->Name );

            if ( 
				! isset( $info['repositories'] ) &&
                ! isset( $info['repositories']['toolset'] )
            ) {
                return $url;
            } else if (
                isset( $info['repositories']['toolset']['affiliate_id'] ) &&
                isset( $info['repositories']['toolset']['affiliate_key'] )
            ) {
                $id = $info['repositories']['toolset']['affiliate_id'];
                $key = $info['repositories']['toolset']['affiliate_key'];

				if ( ! isset( $args['query'] ) ) {
					$args['query'] = array();
				}
				$args['query']['aid']			= $id;
				$args['query']['affiliate_key']	= $key;
				
				$url = esc_url( add_query_arg( $args['query'], $url ) );
				
				if ( isset( $args['anchor'] ) ) {
					$url .= '#' . esc_attr( $args['anchor'] );
				}

                return $url;
            }

            return $url;
			
		}

    }

}
