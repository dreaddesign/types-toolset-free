<?php

/**
 * Toolset_Admin_Notices_Manager
 * This class is more a namespace than a class
 *
 * @since 2.3.0 First release of Toolset_Admin_Notices_Manager
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notices_Manager {
	// always good to have an id
	const ID = 'toolset_admin_notices_manager';

	// js variables
	const JS_VARNAME_NONCE     = 'toolset-admin-notice-nonce';
	const JS_VARNAME_ACTION    = 'toolset-admin-notice-action';
	const JS_VARNAME_NOTICE_ID = 'notice-id';
	const JS_VARNAME_DISMISS_SIMILAR_NOTICES = 'dismiss-similar-notices';

	// js triggers
	const JS_TRIGGER_NOTICE_DISMISSIBLE = 'toolset-dismissible';

	// option fields
    const OPTION_FIELD_DISMISSED_NOTICE = 'dismissed-notices';
    const OPTION_FIELD_DISMISSED_SIMILAR_NOTICES = 'dismissed-similar-notices';

    // const similar notices keys
	const SIMILAR_NOTICES_FREE_PLUGIN_SHOWS_PAID_FEATURES = 'free-plugin-shows-new-paid-features';

	/**
	 * @var Toolset_Admin_Notice_Interface[]
	 */
	private static $notices = array();

	/**
	 * Singleton
	 */
	private function __construct(){}

	/**
	 * Init
	 */
	public static function init(){
		if( ! is_admin() ) {
			return;
		}

		add_action( 'admin_notices', array( 'Toolset_Admin_Notices_Manager', 'show_notices' ) );
		add_action( 'wp_ajax_' . self::ID, array( 'Toolset_Admin_Notices_Manager', 'ajax' ) );
		add_action( 'admin_enqueue_scripts', array( 'Toolset_Admin_Notices_Manager', 'public_resources' ) );
	}

	/**
	 * This creates a Toolset_Admin_Notice() with the given $id
	 *
	 * @param string|Toolset_Admin_Notice_Interface $notice
	 *
	 * @param string $content
	 *
	 * @return Toolset_Admin_Notice_Interface|bool
	 */
	public static function add_notice( $notice, $content = '' ) {
		if( ! is_admin() ) {
			// no frontend support for ADMIN notices
			return false;
		}

		if( ! is_string( $notice ) && ! $notice instanceof Toolset_Admin_Notice_Interface ) {
			// no valid parameter
			error_log( 'Toolset_Admin_Notices_Manager Error: $id must be a ' .
			           'string or a object of Toolset_Admin_Notice_Interface' );
			return false;
		}

		if( is_string( $notice ) ) {
			// use Toolset_Admin_Notice_Success if no object is given
			try {
				$notice = new Toolset_Admin_Notice_Success( $notice );
			} catch( Exception $e ) {
				error_log( 'Toolset_Admin_Notices_Manager Error: Could not create object of notice. ' .
				           $e->getMessage() );
				return false;
			}
		}

		if( ! empty( $content ) ) {
			// user sets a message for the notice
			$notice->set_content( $content );
		}

		if( array_key_exists( $notice->get_id(), self::$notices ) ) {
			// abort, key already exists
			//error_log( 'Toolset_Admin_Notices_Manager Info: Notices with key "' . $notice->get_id() . '" already exists.' );

			return false;
		}

		// collect notice
		self::$notices[$notice->get_id()] = $notice;

		return $notice;
	}


	/**
	 * called on WordPress hook 'admin_notices'
	 */
	public static function show_notices() {

		/**
		 * toolset-admin-notices-manager-show-notices
		 *
		 * @deprecated Use toolset_admin_notices_manager_show_notices instead.
		 */
		$notices = apply_filters( 'toolset-admin-notices-manager-show-notices', self::$notices );

		$notices = apply_filters( 'toolset_admin_notices_manager_show_notices', $notices );

		if( empty( $notices ) ) {
			return;
		}

		foreach( $notices as $notice ) {
			if( ! $notice->conditions_met()
			    || self::is_notice_dismissed( $notice )
			    || self::are_similar_notices_dismissed( $notice ) ) {
				// visitor don't want to see the message anymore
				continue;
			}

            $notice->render();
		}
	}

	/**
	 * Ajax Handler
	 */
	public static function ajax() {

		if( ! array_key_exists( self::JS_VARNAME_ACTION, $_REQUEST )
		    || ! array_key_exists( self::JS_VARNAME_NOTICE_ID, $_REQUEST ) ) {
			error_log( 'Toolset_Admin_Notices_Manager Ajax Error: missing js variables.' );
			die();
		}

		if( ! array_key_exists( self::JS_VARNAME_NONCE, $_REQUEST ) ) {
			error_log( 'Toolset_Admin_Notices_Manager Ajax Error: nonce is not set.' );
			die();
		}

		if ( ! wp_verify_nonce( $_REQUEST[ self::JS_VARNAME_NONCE ], self::ID ) ) {
			error_log( 'Toolset_Admin_Notices_Manager Ajax Error: nonce is not valid.' );
			die();
		}

		switch( $_REQUEST[ self::JS_VARNAME_ACTION ] ) {
			case self::JS_TRIGGER_NOTICE_DISMISSIBLE:
				$dismiss_globally = substr( $_REQUEST[ self::JS_VARNAME_NOTICE_ID ], -1) == '$'
					? true
					: false;

			    self::dismiss_notice_by_id( rtrim( $_REQUEST[ self::JS_VARNAME_NOTICE_ID ], '$' ), $dismiss_globally );

			    if( ! empty( $_REQUEST[ self::JS_VARNAME_DISMISS_SIMILAR_NOTICES ] ) ) {
			    	foreach( $_REQUEST[ self::JS_VARNAME_DISMISS_SIMILAR_NOTICES ] as $key ) {
					    self::dismiss_similar_notices( $key );
				    }
			    }

				die( $_REQUEST[ self::JS_VARNAME_NOTICE_ID ] );
				break;

            default:
                die( 'action not found' );
		}
	}

	/**
	 * CSS / JS
	 */
	public static function public_resources() {
		if( ! class_exists( 'Toolset_Assets_Manager' ) ) {
			error_log( 'Toolset_Admin_Notices_Manager Error: Dependency "Toolset_Assets_Manager" could not be found.' );
			return;
		}

		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		$toolset_assets_manager->register_style(
			'toolset-admin-notices',
			TOOLSET_COMMON_URL . '/res/css/toolset-admin-notices.css',
			array(),
			TOOLSET_COMMON_VERSION
		);
		$toolset_assets_manager->enqueue_styles( 'toolset-admin-notices' );

		$toolset_assets_manager->register_script(
			'toolset-admin-notices',
			TOOLSET_COMMON_URL . '/res/js/toolset-admin-notices.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);
		$toolset_assets_manager->localize_script(
			'toolset-admin-notices',
			'toolset_admin_notices',
			array(
				'action'          => self::ID,
				'nonce'           => wp_create_nonce( self::ID ),
				'varnameNonce'    => self::JS_VARNAME_NONCE,
				'varnameAction'   => self::JS_VARNAME_ACTION,
				'varnameNoticeId' => self::JS_VARNAME_NOTICE_ID,
				'varnameDismissSimilarNotices' => self::JS_VARNAME_DISMISS_SIMILAR_NOTICES,
				'triggerNoticeDismissible' => self::JS_TRIGGER_NOTICE_DISMISSIBLE
			)
		);

		$toolset_assets_manager->enqueue_scripts( 'toolset-admin-notices' );
	}

	/**
	 *
	 * @param Toolset_Admin_Notice_Interface $notice
	 *
	 * @return bool
	 */
	public static function is_notice_dismissed( Toolset_Admin_Notice_Interface $notice ) {
        if( ! $notice->is_dismissible_permanent() ) {
            // notice is not dismissible
            return false;
        }

		return self::is_notice_dismissed_by_notice_id( $notice->get_id() );
    }

	/**
	 * This function can be used by using the "id" of the notice instead of Toolset_Admin_Notice_Interface
	 * "ID" is in this case a unique string, which describes the notice.
	 *
	 * @param $notice_id
	 *
	 * @return bool
	 */
	public static function is_notice_dismissed_by_notice_id( $notice_id ) {
		$user_id = get_current_user_id();

		if( $user_id == 0 ) {
			return false;
		}

		// user settings
		$user_settings = get_user_meta( $user_id, self::ID, true );

		if( is_array( $user_settings )
		    && array_key_exists( self::OPTION_FIELD_DISMISSED_NOTICE, $user_settings )
		    && array_key_exists( $notice_id, $user_settings[ self::OPTION_FIELD_DISMISSED_NOTICE ] )
		) {
			// user dimissed the message
			return true;
		}

		// globally dismissed
		$settings = get_option( self::ID );

		if( is_array( $settings )
		    && array_key_exists( self::OPTION_FIELD_DISMISSED_NOTICE, $settings )
		    && array_key_exists( $notice_id, $settings[ self::OPTION_FIELD_DISMISSED_NOTICE ] )
		) {
			// notice globally dismissed
			return true;
		}

		return false;
	}

	/**
	 * Store that the current user don't want to see the notice with id anymore
	 * Notice: We don't check if an notice with that id really exists,
	 *         this way we don't have to initialize notices on ajax calls
	 *
	 * @param $notice_id
	 *
	 * @param bool $globally
	 *
	 * @return bool
	 */
    public static function dismiss_notice_by_id( $notice_id, $globally = false ) {
    	if( $globally ) {
    		// store globally
		    $settings = get_option( self::ID, array() );
		    $settings[ self::OPTION_FIELD_DISMISSED_NOTICE ][ $notice_id ] = true;
		    update_option( self::ID, $settings );
		    return;
	    }

	    $user_id = get_current_user_id();

	    if( $user_id == 0 ) {
	        return false;
        }

        $user_settings = get_user_meta( $user_id, self::ID, true );
	    $user_settings = empty( $user_settings ) ? array() : $user_settings;
	    $user_settings[ self::OPTION_FIELD_DISMISSED_NOTICE ][ $notice_id ] = true;
	    update_user_meta( $user_id, self::ID, $user_settings );
    }

	/**
	 * Set stop showing similiar notices by $key_for_similar_notices
	 *
	 * @param $key_for_similar_notices
	 *
	 * @return bool
	 */
    public static function dismiss_similar_notices( $key_for_similar_notices ) {
	    $user_id = get_current_user_id();

	    if( $user_id == 0 ) {
		    return false;
	    }

	    $user_settings = get_user_meta( $user_id, self::ID, true );
	    $user_settings = empty( $user_settings ) ? array() : $user_settings;
	    $user_settings[ self::OPTION_FIELD_DISMISSED_SIMILAR_NOTICES ][ $key_for_similar_notices ] = true;
	    update_user_meta( $user_id, self::ID, $user_settings );
    }

	/**
	 * Check if current user has used the option to stop similar notices
	 *
	 * @param Toolset_Admin_Notice_Interface $notice
	 *
	 * @return bool
	 */
    public static function are_similar_notices_dismissed( $notice ) {
    	if( ! $notice instanceof Toolset_Admin_Notice_Interface ) {
    		// no proper $notice (we have a filter for it, so probably that's the reason)
    		return false;
	    }

	    $similar_notices_key = $notice->get_similar_notices_key();
	    if( empty( $similar_notices_key ) ) {
    		// no similar notices key used for this message
		    return false;
	    }

	    $user_id = get_current_user_id();

	    if( $user_id == 0 ) {
		    return false;
	    }

	    // user settings
	    $user_settings = get_user_meta( $user_id, self::ID, true );

	    if( is_array( $user_settings )
	        && array_key_exists( self::OPTION_FIELD_DISMISSED_SIMILAR_NOTICES, $user_settings )
	        && array_key_exists( $notice->get_similar_notices_key(), $user_settings[ self::OPTION_FIELD_DISMISSED_SIMILAR_NOTICES ] )
	    ) {
		    // user dimissed similar notices
		    return true;
	    }

	    return false;
    }


	/**
	 * Create a button. Use of tpl_button_primary() or tpl_button_secondary() is preferred.
	 *
	 * @param string $title
	 * @param string $href
	 * @param string $class
	 * @param bool $external
	 *
	 * @return string TPL of the link
	 * @since 2.8
	 */
    public static function tpl_button( $title, $href, $class, $external = false ) {
	    $title    = esc_attr( $title );
	    $href     = esc_url( $href );
		$class    = 'toolset-button ' . $class;
		$target   = '';

	    if( $external === true ) {
	    	// external link
	    	$class .= ' toolset-button-external-link';
		    $target = ' target="_blank"';
	    }

	    // html link
	    return '<a href="' . $href . '" class="' . $class . '"'. $target .'>' . $title . '</a>';
    }


	/**
	 * Create a primary button
	 *
	 * @param string $title
	 * @param string $href
	 * @param bool $external
	 * @param string $extra_class
	 *
	 * @return string HTML markup of the link.
	 */
	public static function tpl_button_primary( $title, $href, $external = false, $extra_class = '' ) {
		return self::tpl_button( $title, $href, 'toolset-button-primary ' . $extra_class, $external );
	}


	/**
	 * Create a secondary button
	 *
	 * @param string $title
	 * @param string $href
	 * @param bool $external
	 * @param string $extra_class
	 *
	 * @return string HTML markup of the link.
	 * @since 2.8
	 */
	public static function tpl_button_secondary( $title, $href, $external = false, $extra_class = '' ) {
		return self::tpl_button( $title, $href, 'toolset-button-secondary ' . $extra_class, $external );
	}


	/**
	 * Create a link
	 *
	 * @param $title
	 * @param string $href
	 * @param bool $external
	 *
	 * @return string TPL of the link
	 */
	public static function tpl_link( $title, $href, $external = false ) {
		$title    = esc_attr( $title );
		$href     = esc_url( $href );
		$class    = '';
		$target   = '';

		if( $external === true ) {
			// external link
			$class .= ' toolset-button-external-link';
			$target = ' target="_blank"';
		}

		// html link
		return '<a href="' . $href . '" class="' . $class . '"'. $target .'>' . $title . '</a>';
	}
}