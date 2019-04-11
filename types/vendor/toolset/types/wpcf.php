<?php

// make sure that WPCF_VERSION in embedded/bootstrap.php is the same!
if ( ! defined( 'WPCF_VERSION' ) )
    define( 'WPCF_VERSION', TYPES_VERSION );

define( 'WPCF_REPOSITORY', 'http://api.wp-types.com/' );

define( 'WPCF_ABSPATH', dirname( __FILE__ ) );

if( ! defined( 'WPCF_RELPATH' ) )
    define( 'WPCF_RELPATH', plugins_url() . '/' . basename( WPCF_ABSPATH ) );

define( 'WPCF_INC_ABSPATH', WPCF_ABSPATH . '/includes' );
define( 'WPCF_INC_RELPATH', WPCF_RELPATH . '/includes' );
define( 'WPCF_RES_ABSPATH', WPCF_ABSPATH . '/resources' );
define( 'WPCF_RES_RELPATH', WPCF_RELPATH . '/resources' );

if( ! defined( 'WPCF_EMBEDDED_TOOLSET_ABSPATH' ) )
    define( 'WPCF_EMBEDDED_TOOLSET_ABSPATH' , WPCF_EMBEDDED_ABSPATH . '/toolset' );

if( ! defined( 'WPCF_EMBEDDED_TOOLSET_RELPATH'))
    define( 'WPCF_EMBEDDED_TOOLSET_RELPATH', WPCF_EMBEDDED_RELPATH . '/toolset' );


require_once WPCF_INC_ABSPATH . '/constants.php';
/*
 * Since Types 1.2 we load all embedded code without conflicts
 */
require_once WPCF_ABSPATH . '/embedded/types.php';

require_once WPCF_EMBEDDED_TOOLSET_ABSPATH . '/onthego-resources/loader.php';
onthego_initialize( WPCF_EMBEDDED_TOOLSET_ABSPATH . '/onthego-resources/',
    WPCF_EMBEDDED_TOOLSET_RELPATH . '/onthego-resources/' );
	
require WPCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/loader.php';
toolset_common_initialize( WPCF_EMBEDDED_TOOLSET_ABSPATH . '/toolset-common/', 
	WPCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/' );

// Plugin mode only hooks
add_action( 'plugins_loaded', 'wpcf_init' );

// init hook for module manager
add_action( 'init', 'wpcf_wp_init' );


/**
 * Deactivation hook.
 *
 * Reset some of data.
 */
function wpcf_deactivation_hook()
{
    // Delete messages
    delete_option( 'wpcf-messages' );
    delete_option( 'WPCF_VERSION' );
    /**
     * check site kind and if do not exist, delete types_show_on_activate
     */
    if ( !get_option('types-site-kind') ) {
        delete_option('types_show_on_activate');
    }
}

/**
 * Activation hook.
 *
 * Reset some of data.
 * 
 * @deprecated
 */
function wpcf_activation_hook()
{
    $version = get_option('WPCF_VERSION');
    if ( empty($version) ) {
        $version = 0;
        add_option('WPCF_VERSION', 0, null, 'no');
    }
    if ( version_compare($version, WPCF_VERSION) < 0 ) {
        update_option('WPCF_VERSION', WPCF_VERSION);
    }
    if( 0 == version_compare(WPCF_VERSION, '1.6.5')) {
        add_option('types_show_on_activate', 'show', null, 'no');
        if ( get_option('types-site-kind') ) {
            update_option('types_show_on_activate', 'hide');
        }
    }
}

/**
 * Main init hook.
 */
function wpcf_init()
{
    if ( !defined( 'EDITOR_ADDON_RELPATH' ) ) {
        define( 'EDITOR_ADDON_RELPATH', WPCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/visual-editor' );
    }

    if ( is_admin() ) {
        require_once WPCF_ABSPATH . '/admin.php';
    }
    /**
     * remove unused option
     */
    $version_from_db = get_option('wpcf-version', 0);
    if ( version_compare(WPCF_VERSION, $version_from_db) > 0 ) {
        delete_option('wpcf-survey-2014-09');
        update_option('wpcf-version', WPCF_VERSION);
    }
}

//Render Installer packages
function installer_content()
{
    echo '<div class="wrap">';
    $config['repository'] = array(); // required
    WP_Installer_Show_Products($config);
    echo "</div>";
}

/**
 * WP Main init hook.
 */
function wpcf_wp_init()
{
    if ( is_admin() ) {
        require_once WPCF_ABSPATH . '/admin.php';
    }
}



function ajax_wpcf_is_reserved_name() {

    // slug
    $name = isset( $_POST['slug'] )
        ? sanitize_text_field( $_POST['slug'] )
        : '';

    // context
    $context = isset( $_POST['context'] )
        ? sanitize_text_field( $_POST['context'] )
        : false;

    // check also page slugs
    $check_pages = isset( $_POST['check_pages'] ) && $_POST['check_pages'] == false
        ? false
        : true;

    // slug pre save
    if( isset( $_POST['slugPreSave'] )
        && $_POST['slugPreSave'] !== 0 ) {

        // for taxonomy
        if( $context == 'taxonomy' )
            $_POST['ct']['wpcf-tax'] = sanitize_text_field( $_POST['slugPreSave'] );

        // for post_type
        if( $context == 'post_type' )
            $_POST['ct']['wpcf-post-type'] = sanitize_text_field( $_POST['slugPreSave'] );
    }

    if( $context == 'post_type' || $context == 'taxonomy' ) {
        $used_reserved = wpcf_is_reserved_name( $name, $context, $check_pages );

        if( $used_reserved ) {
            die( json_encode( array( 'already_in_use' => 1 ) ) );
        }
    }

    // die( json_encode( $_POST ) );
    die( json_encode( array( 'already_in_use' => 0 ) ) );
}

add_action( 'wp_ajax_wpcf_get_forbidden_names', 'ajax_wpcf_is_reserved_name' );

/**
 * Checks if name is reserved.
 *
 * @param type $name
 * @return type
 */
function wpcf_is_reserved_name($name, $context, $check_pages = true)
{
    $name = strval( $name );
    /*
     *
     * If name is empty string skip page cause there might be some pages without name
     */
    if ( $check_pages && !empty( $name ) ) {
        global $wpdb;
        $page = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='page'",
                sanitize_title( $name )
            )
        );
        if ( !empty( $page ) ) {
            return new WP_Error( 'wpcf_reserved_name', __( 'You cannot use this slug because there is already a page by that name. Please choose a different slug.',
                                    'wpcf' ) );
        }
    }

    // Add custom types
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    $post_types = get_post_types();
    if ( !empty( $custom_types ) ) {
        $custom_types = array_keys( $custom_types );
        $post_types = array_merge( array_combine( $custom_types, $custom_types ),
                $post_types );
    }
    // Unset to avoid checking itself
    /* Note: This will unset any post type with the same slug, so it's possible to overwrite it
    if ( $context == 'post_type' && isset( $post_types[$name] ) ) {
        unset( $post_types[$name] );
    }
    */
    // abort test...
    if( $context == 'post_type' // ... for post type ...
        && isset( $_POST['ct']['wpcf-post-type'] ) // ... if it's an already saved taxonomy ...
        && $_POST['ct']['wpcf-post-type'] == $name // ... and the slug didn't changed.
    ) {
        return false;
    }

    // Add taxonomies
    $custom_taxonomies = (array) get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    $taxonomies = get_taxonomies();
    if ( !empty( $custom_taxonomies ) ) {
        $custom_taxonomies = array_keys( $custom_taxonomies );
        $taxonomies = array_merge( array_combine( $custom_taxonomies,
                        $custom_taxonomies ), $taxonomies );
    }

    // Unset to avoid checking itself
    /* Note: This will unset any taxonomy with the same slug, so it's possible to overwrite it
    if ( $context == 'taxonomy' && isset( $taxonomies[$name] ) ) {
        unset( $taxonomies[$name] );
    }
    */

    // abort test...
    if( $context == 'taxonomy' // ... for taxonomy ...
        && isset( $_POST['ct']['wpcf-tax'] ) // ... if it's an already saved taxonomy ...
        && $_POST['ct']['wpcf-tax'] == $name // ... and the slug didn't changed.
    ) {
        return false;
    }

    $reserved_names = wpcf_reserved_names();
    $reserved = array_merge( array_combine( $reserved_names, $reserved_names ),
            array_merge( $post_types, $taxonomies ) );

    return in_array( $name, $reserved ) ? new WP_Error( 'wpcf_reserved_name', __( 'You cannot use this slug because it is a reserved word, used by WordPress. Please choose a different slug.',
                            'wpcf' ) ) : false;
}

/**
 * Reserved names.
 *
 * @return type
 */
function wpcf_reserved_names()
{
    $toolset_naming_helper = Toolset_Naming_Helper::get_instance();
	$reserved = $toolset_naming_helper->get_reserved_terms();
	$reserved[] = 'action';

    return apply_filters( 'wpcf_reserved_names', $reserved );
}

add_action( 'icl_pro_translation_saved', 'wpcf_fix_translated_post_relationships' );

function wpcf_fix_translated_post_relationships($post_id)
{
    require_once WPCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
    wpcf_post_relationship_set_translated_parent( $post_id );
    wpcf_post_relationship_set_translated_children( $post_id );
}

// this is for testing promotional message
// set WPCF_PAYED true in your wp-config
if ( !defined( 'WPCF_PAYED' ) )
    define( 'WPCF_PAYED', true );

if( ! function_exists( 'wpcf_is_client' ) ) {
    /**
     * Check if user is a client, who bought Toolset
     * @return bool
     */
    function wpcf_is_client() {

        // for testing
        if( ! WPCF_PAYED )
            return false;

        // check db stored value
        if( get_option( 'wpcf-is-client' ) ) {
            $settings = wpcf_get_settings( 'help_box' );

            // prioritise settings if available
            if( $settings ) {
                switch( $settings ) {
                    case 'by_types':
                    case 'all':
                        return false;
                    case 'no':
                        return true;
                }
            }

            $is_client = get_option( 'wpcf-is-client' );

            // client
            if( $is_client === 'yes' )
                return true;

            // user
            return false;
        }

        // no db stored value
        // make sure get_plugins() is available
        if ( ! function_exists( 'get_plugins' ) )
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // all plugins
        $plugins = get_plugins();

        // check each plugin
        foreach( $plugins as $plugin ) {
            // skip plugin that is not created by us
            if( $plugin['Author'] != 'OnTheGoSystems' )
                continue;

            // check for toolset plugin and not embedded = user bought toolset
            if( preg_match( "#(access|cred|layouts|module manager|views)#i", $plugin['Name'] )
                && ! preg_match( '#embedded#i', $plugin['Name'] ) ) {
                add_option( 'wpcf-is-client', 'yes' );

                // set settings "help box" ounce to none
                $settings = get_option( 'wpcf_settings', array() );
                $settings['help_box'] = 'no';
                update_option( 'wpcf_settings', $settings );

                return true;
            }
        }

        // if script comes to this point we have no option "wpcf-is-client" set
        // and also no bought toolset plugin
        add_option( 'wpcf-is-client', 'no' );
        return false;
    }
}

/**
 * On plugin activation clear option "wpcf-is-client"
 */
if( ! function_exists( 'wpcf_clear_option_is_client' ) ) {
    function wpcf_clear_option_is_client() {
        $option_is_client = get_option( 'wpcf-is-client' );
        if( $option_is_client == 'no' ) {
            delete_option( 'wpcf-is-client' );
        }

    }
}

add_action( 'activated_plugin', 'wpcf_clear_option_is_client' );


// Make sure this runs after wpcf_init_custom_types_taxonomies() so that our custom taxonomies and post types are
// already registered at that point. See types-676.
add_action( 'init', 'wpcf_upgrade_stored_taxonomies_with_builtin', apply_filters( 'wpcf_init_custom_types_taxonomies', 10 ) + 100 );

/**
 * Make sure in built taxonomies are stored.
 *
 * This is an upgrade routine for Types older than 1.9. The code will run only once.
 *
 * @since 1.9
 */
function wpcf_upgrade_stored_taxonomies_with_builtin() {
	$stored_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

	if( empty( $stored_taxonomies ) || !isset( $stored_taxonomies['category'] ) || !isset( $stored_taxonomies['post_tag'] ) ) {
		
		$taxonomies = Types_Utils::object_to_array_deep( get_taxonomies( array( 'public' => true, '_builtin' => true ), 'objects' ) );

		if( isset( $taxonomies['post_format'] ) )
			unset( $taxonomies['post_format'] );

		foreach( $taxonomies as $slug => $settings ) {
			if( isset( $stored_taxonomies[$slug] ) )
				continue;

			$taxonomies[$slug]['slug'] = $slug;
			foreach( $settings['object_type'] as $support ) {
				$taxonomies[$slug]['supports'][$support] = 1;
			}

			$stored_taxonomies[$slug] = $taxonomies[$slug];
		}

		update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $stored_taxonomies );
	}
}

/* Plugin Meta */
add_filter( 'plugin_row_meta', 'types_plugin_plugin_row_meta', 10, 4 );

function types_plugin_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
    $this_plugin = basename( WPCF_ABSPATH ) . '/wpcf.php';
    if ( $plugin_file == $this_plugin ) {
        $plugin_meta[] = '<a href="' . TYPES_RELEASE_NOTES . '" target="_blank">'
        . sprintf( __( 'Types %s release notes', 'wpcf' ), TYPES_VERSION ) . '</a>';
    }
    return $plugin_meta;
}

/**
 * Feedback
 */
function types_plugin_action_links ( $links ) {
	$feedback = array(
		'<a id="types-leave-feedback-trigger" href="https://www.surveymonkey.com/r/types-uninstall" target="_blank">' . __( 'Leave feedback', 'wpcf' ) . '</a>',
	);
	return array_merge( $links, $feedback );
}

add_action( 'load-plugins.php', 'types_ask_for_feedback_on_deactivation' );

function types_ask_for_feedback_on_deactivation() {
    // abort if message was shown in the last 90 days
    $user_dismissed_notices = get_user_meta( get_current_user_id(), '_types_feedback_dont_show_until', true );
    if( $user_dismissed_notices && current_time( 'timestamp' ) < $user_dismissed_notices )
        return;

    add_action( 'admin_footer', 'types_feedback_on_deactivation_dialog' );
    add_action( 'admin_enqueue_scripts', 'types_feedback_on_deactivation_scripts' );

    function types_feedback_on_deactivation_dialog() { ?>
        <div id="types-feedback" style="display:none;width:500px;">
        <div class="types-message-icon" style="float: left; margin: 2px 0 0 0; padding: 0 15px 0 0;">
            <?php //<span class="icon-toolset-logo"></span> ?>
            <span class="icon-types-logo ont-icon-64" style="color: #f05a29;""></span>
        </div>

        <div style="margin-top: 8px;">
            <p>
                <?php _e( "Do you have a minute to tell us why you're removing Types?", 'wpcf' ); ?>
            </p>

            <a id="types-leave-feedback-dialog-survey-link" class="button-primary types-button types-external-link" style="margin-right: 8px;" target="_blank"
               href="https://www.surveymonkey.com/r/types-uninstall">
                <?php _e( 'Leave feedback', 'wpcf' ); ?>
            </a>
            <a id="types-leave-feedback-dialog-survey-link-cancel" class="button-secondary"
               href="javascript:void(0);">
                <?php _e( 'Skip feedback', 'wpcf' ); ?>
            </a>
        </div>

        <br style="clear:both;" />
        </div>
    <?php }
    function types_feedback_on_deactivation_scripts() {
        wp_enqueue_script(
            'types-feedback-on-deactivation',
            TYPES_RELPATH . '/public/js/feedback-on-deactivation.js',
            array( 'jquery-ui-dialog' ),
            TYPES_VERSION,
            true
        );

        wp_enqueue_style(
            'types-information',
            TYPES_RELPATH . '/public/css/information.css',
            array( 'wp-jquery-ui-dialog' ),
            TYPES_VERSION
        );
    }
}

add_action( 'wp_ajax_types_feedback_dont_show_for_90_days', 'types_feedback_dont_show_for_90_days' );

function types_feedback_dont_show_for_90_days() {
    $in_90_days = strtotime( '+90 days', current_time( 'timestamp' ) );
    update_user_meta( get_current_user_id(), '_types_feedback_dont_show_until', $in_90_days );
}