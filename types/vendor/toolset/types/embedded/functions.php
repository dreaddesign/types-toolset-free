<?php
/*
 * Basic and init functions.
 * Since Types 1.2 moved from /embedded/types.php
 *
 *
 */

/**
 * Returns the post meta or empty string if not available
 * Since 2.3 the function no longer has its own caching.
 *
 * @param $post_id
 * @param $meta_key
 * @param $is_single
 *
 * @return mixed
 * @deprecated This is no longer needed. For accessing custom fields, use the Toolset_Field* API. For accessing
 * other postmeta, use get_post_meta() directly.
 */
function wpcf_get_post_meta($post_id, $meta_key, $is_single) {

    $post_meta = get_post_meta( $post_id, $meta_key, $is_single );

    if( Toolset_Utils::is_field_value_truly_empty( $post_meta ) ) {
	    // no meta data
	    return '';
    }

	return maybe_unserialize( $post_meta );
}

/**
 * Calculates relative path for given file.
 *
 * @param type $file Absolute path to file
 * @return string Relative path
 */
function wpcf_get_file_relpath($file)
{
    $is_https = isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on';
    $http_protocol = $is_https ? 'https' : 'http';
    $base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
    $base_url = $base_root;
    $dir = rtrim( dirname( $file ), '\/' );
    if ( $dir ) {
        $base_path = $dir;
        $base_url .= $base_path;
        $base_path .= '/';
    } else {
        $base_path = '/';
    }
    $relpath = $base_root
            . str_replace(
                    str_replace( '\\', '/',
                            realpath( $_SERVER['DOCUMENT_ROOT'] ) )
                    , '', str_replace( '\\', '/', dirname( $file ) )
    );
    return $relpath;
}

/**
 * after_setup_theme hook.
 */
function wpcf_embedded_after_setup_theme_hook()
{
	// We need to require this file manually because this runs before the autoloader is registered.
	require_once TYPES_ABSPATH . '/application/controllers/utils/post_type_option.php';
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    if ( !empty( $custom_types ) ) {
        foreach ( $custom_types as $post_type => $data ) {
            if ( !empty( $data['supports']['thumbnail'] ) ) {
                if ( !current_theme_supports( 'post-thumbnails' ) ) {
                    add_theme_support( 'post-thumbnails' );
                    remove_post_type_support( 'post', 'thumbnail' );
                    remove_post_type_support( 'page', 'thumbnail' );
                } else {
                    add_post_type_support( $post_type, 'thumbnail' );
                }
            }
        }
    }
}

/**
 * Inits custom types and taxonomies.
 */
function wpcf_init_custom_types_taxonomies()
{
    // register taxonomies first
	// because custom taxonomies might have rewrite rules that used to have priority over post type rewrite rules, and we keep backwards compatibility
	// for example, a taxonomy "topic" with rewrite "video/topic" assigned to a "video" post type
	// - if "video" is registered first, top-level topic archives will render as 404
	// - if "topic" is registered first, top-level "topic" terms will have archive pages and "video" posts will have single pages
	// note that custom taxonomies on register_taxonomy do not check whether the post types they register to do exist or not
    $custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    if ( !empty( $custom_taxonomies ) ) {
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/custom-taxonomies.php';
        wpcf_custom_taxonomies_init();
    }
	
    // register post types
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    if ( !empty( $custom_types ) ) {
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/custom-types.php';
        wpcf_custom_types_init();
    }

    // and then manage builtin taxonomies registration for post types
	// because we use register_taxonomy_for_object_type(), which checks if the post type is available
    if ( !empty( $custom_taxonomies ) ) {
		wpcf_builtin_taxonomies_init();
    }

}

/**
 * Returns meta_key type for specific field type.
 *
 * @param type $type
 * @return type
 */
function types_get_field_type($type)
{
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    $data = wpcf_fields_type_action( $type );
    if ( !empty( $data['meta_key_type'] ) ) {
        return $data['meta_key_type'];
    }
    return 'CHAR';
}

/**
 * Imports settings.
 *
 * @fixme Are we touching this on EVERY page load?!
 * @since unknown
 */
function wpcf_embedded_check_import()
{

	if( !defined( 'WPCF_EMBEDDED_CONFIG_ABSPATH' ) ) {

		/**
		 * Allow for overriding path to settings.php and settings.xml by a third party.
		 *
		 * Falls back to WPCF_EMBEDDED_ABSPATH if not defined.
		 *
		 * @since 1.9.1
		 */
		define( 'WPCF_EMBEDDED_CONFIG_ABSPATH', WPCF_EMBEDDED_ABSPATH );
	}

	if ( file_exists( WPCF_EMBEDDED_CONFIG_ABSPATH . '/settings.php' ) ) {
        require_once WPCF_EMBEDDED_ABSPATH . '/admin.php';
        require_once WPCF_EMBEDDED_CONFIG_ABSPATH . '/settings.php';
        $dismissed = get_option( 'wpcf_dismissed_messages', array() );
        if ( in_array( $timestamp, $dismissed ) ) {
            return false;
        }
        if ( $timestamp > get_option( 'wpcf-types-embedded-import', 0 ) ) {
            if (
                isset( $_GET['types-embedded-import'] )
                && isset( $_GET['_wpnonce'] )
                && wp_verify_nonce( $_GET['_wpnonce'], 'embedded-import')
            ) {
                if ( file_exists( WPCF_EMBEDDED_CONFIG_ABSPATH . '/settings.xml' ) ) {
                    $_POST['overwrite-groups'] = 1;
                    $_POST['overwrite-fields'] = 1;
                    $_POST['overwrite-types'] = 1;
                    $_POST['overwrite-tax'] = 1;
                    $_POST['post_relationship'] = 1;
                    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                    require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
                    $data = @file_get_contents( WPCF_EMBEDDED_CONFIG_ABSPATH . '/settings.xml' );
                    wpcf_admin_import_data( $data, false, 'types-auto-import' );
                    update_option( 'wpcf-types-embedded-import', $timestamp );
                    wp_safe_redirect( esc_url_raw(admin_url() ));
                } else {
                    $code = __( 'settings.xml file missing', 'wpcf' );
                    wpcf_admin_message( $code, 'error' );
                }
            }
            else {
                $link = "<a href=\"" . admin_url( '?types-embedded-import=1&amp;_wpnonce=' . wp_create_nonce( 'embedded-import' ) ) . "\">";
                $text = sprintf( __( 'You have Types import pending. %sClick here to import.%s %sDismiss message.%s', 'wpcf' ), $link, '</a>',
                    "<a onclick=\"jQuery(this).parent().parent().fadeOut();\" class=\"wpcf-ajax-link\" href=\""
                    . admin_url( 'admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=dismiss_message&amp;id='
                    . $timestamp . '&amp;_wpnonce=' . wp_create_nonce( 'dismiss_message' ) ) . "\">",
                        '</a>' );
                wpcf_admin_message( $text );
            }
        }
    }
}

/**
 * Actions for outside fields control.
 *
 * @param string $action
 * @param array $args
 * @param string $post_type
 * @param string $option_name
 *
 * @return bool|array
 */
function wpcf_types_cf_under_control( $action = 'add', $args = array(),
        $post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, $option_name = 'wpcf-fields' ) {
    global $wpcf_types_under_control;
    $wpcf_types_under_control['errors'] = array();
    switch ( $action ) {
        case 'add':
            $fields = wpcf_admin_fields_get_fields( false, true, false,
                    $option_name, false );
            foreach ( $args['fields'] as $field_id ) {
                $field_type = !empty( $args['type'] ) ? $args['type'] : 'textfield';
                if ( strpos( $field_id, md5( 'wpcf_not_controlled' ) ) !== false ) {
                    $field_id_name = str_replace( '_' . md5( 'wpcf_not_controlled' ), '', $field_id );
                    $field_id_add = preg_replace( '/^wpcf\-/', '', $field_id_name );
                    $adding_field_with_wpcf_prefix = $field_id_add != $field_id_name;

                    // Activating field that previously existed in Types
                    if ( array_key_exists( $field_id_add, $fields ) ) {
                        $fields[$field_id_add]['data']['disabled'] = 0;
                    } else { // Adding from outside
                        $fields[$field_id_add]['id'] = $field_id_add;
                        $fields[$field_id_add]['type'] = $field_type;
                        if ($adding_field_with_wpcf_prefix) {
                            $fields[$field_id_add]['name'] = $field_id_add;
                            $fields[$field_id_add]['slug'] = $field_id_add;
                        } else {
                            $fields[$field_id_add]['name'] = $field_id_name;
                            $fields[$field_id_add]['slug'] = $field_id_name;
                        }
                        $fields[$field_id_add]['description'] = '';
                        $fields[$field_id_add]['data'] = array();
                        if ($adding_field_with_wpcf_prefix) {
                            // This was most probably a previous Types field
                            // let's take full control
                            $fields[$field_id_add]['data']['controlled'] = 0;
                        } else {
                            // @TODO WATCH THIS! MUST NOT BE DROPPED IN ANY CASE
                            $fields[$field_id_add]['data']['controlled'] = 1;
                        }
                    }
                    $unset_key = array_search( $field_id, $args['fields'] );
                    if ( $unset_key !== false ) {
                        unset( $args['fields'][$unset_key] );
                        $args['fields'][$unset_key] = $field_id_add;
                    }
                }
            }
            wpcf_admin_fields_save_fields( $fields, true, $option_name );
            return $args['fields'];
            break;

        case 'check_exists':
            $fields = wpcf_admin_fields_get_fields( false, true, false,
                    $option_name, false );
            $field = $args;
            if ( array_key_exists( $field, $fields ) && empty( $fields[$field]['data']['disabled'] ) ) {
                return true;
            }
            return false;
            break;

        case 'check_outsider':
            $fields = wpcf_admin_fields_get_fields( false, true, false,
                    $option_name, false );
            $field = $args;
            if ( array_key_exists( $field, $fields ) && !empty( $fields[$field]['data']['controlled'] ) ) {
                return true;
            }
            return false;
            break;

        default:
            break;
    }
}

/**
 * Controlls meta prefix.
 *
 * @param array $field
 */
function wpcf_types_get_meta_prefix( $field = array() )
{
    if ( empty( $field ) ) {
        return WPCF_META_PREFIX;
    }
    if ( !empty( $field['data']['controlled'] ) ) {
        return '';
    }
    return WPCF_META_PREFIX;
}

/**
 * Compares WP versions
 * @global type $wp_version
 * @param type $version
 * @param type $operator
 * @return type
 */
function wpcf_compare_wp_version($version = '3.2.1', $operator = '>')
{
    global $wp_version;
    return version_compare( $wp_version, $version, $operator );
}

/**
 * Gets post type with data to which belongs.
 *
 * @param type $post_type
 * @return type
 */
function wpcf_pr_get_belongs($post_type)
{
    require_once WPCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
    return wpcf_pr_admin_get_belongs( $post_type );
}

/**
 * Gets all post types and data that owns.
 *
 * @param type $post_type
 * @return type
 */
function wpcf_pr_get_has($post_type)
{
    require_once WPCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
    return wpcf_pr_admin_get_has( $post_type );
}

/**
 * Gets individual post ID to which queried post belongs.
 *
 * @param type $post_id
 * @param type $post_type Post Type of owner
 * @return type
 */
function wpcf_pr_post_get_belongs($post_id, $post_type)
{
    return get_post_meta( $post_id, '_wpcf_belongs_' . $post_type . '_id', true );
}

/**
 * Gets all posts that belong to queried post, grouped by post type.
 *
 * @param type $post_id
 * @param type $post_type
 * @return type
 */
function wpcf_pr_post_get_has($post_id, $post_type_q = null)
{
    $post_type = get_post_type( $post_id );
    $has = array_keys( wpcf_pr_get_has( $post_type ) );
    $add = is_null( $post_type_q ) ? '&post_type=any' : '&post_type=' . $post_type_q;
    $posts = get_posts( 'numberposts=-1&post_status=null&meta_key=_wpcf_belongs_'
            . $post_type . '_id&meta_value=' . $post_id . $add );

    $results = array();
    foreach ( $posts as $post ) {
        if ( !in_array( $post->post_type, $has ) ) {
            continue;
        }
        $results[$post->post_type][] = $post;
    }
    return is_null( $post_type_q ) ? $results : array_shift( $results );
}

/**
 * Gets settings.
 */
function wpcf_get_settings($specific = false)
{
    $defaults = array(
        'add_resized_images_to_library' => 0,
        'register_translations_on_import' => 1,
        'images_remote' => 0,
        'images_remote_cache_time' => '36',
        'help_box' => 'by_types',
        'hide_standard_custom_fields_metabox' => 'show',
        'postmeta_unfiltered_html' => 'on',
        'usermeta_unfiltered_html' => 'on',
    );
    $settings = wp_parse_args( get_option( 'wpcf_settings', array() ), $defaults );
    $settings = apply_filters( 'types_settings', $settings );
    if ( $specific ) {
        return isset( $settings[$specific] ) ? $settings[$specific] : false;
    }
    return $settings;
}

/**
 * Saves settings.
 */
function wpcf_save_settings($settings)
{
    update_option( 'wpcf_settings', $settings );
}

/**
 * Check if it can be repetitive
 * @param $type
 * @return bool
 */
function wpcf_admin_can_be_repetitive($type)
{
    return !in_array( $type,
                    array('checkbox', 'checkboxes', 'wysiwyg', 'radio', 'select') );
}

/**
 * Check if field is repetitive.
 *
 * @deprecated Use types_is_repetitive instead.
 * @param array $field Field definition array.
 * @return bool
 */
function wpcf_admin_is_repetitive( $field ) {
	$field_type = wpcf_getarr( $field, 'type', '' );
	$is_repetitive = (int) wpcf_getnest( $field, array( 'data', 'repetitive' ), 0 );

	return ( $is_repetitive && ! empty( $field_type ) && wpcf_admin_can_be_repetitive( $field_type ) );
}

/**
 * Returns an unique string identifier every time it is called.
 *
 * @staticvar array $cache
 * @param string $cache_key
 * @return string Unique identifier
 * @since unknown
 */
function wpcf_unique_id($cache_key) {
	$cache_key = md5( strval( $cache_key ) . strval( time() ) . rand() );
	static $cache = array();
	if ( ! isset( $cache[ $cache_key ] ) ) {
		$cache[ $cache_key ] = 1;
	} else {
		$cache[ $cache_key ] += 1;
	}

	return $cache_key . '-' . $cache[ $cache_key ];
}

/**
 * Determine if platform is Win
 *
 * @return type
 */
function wpcf_is_windows()
{
    global $wpcf;
    $is_windows = PHP_OS == "WIN32" || PHP_OS == "WINNT";
    if ( isset( $wpcf->debug ) ) {
        $wpcf->debug->is_windows = $is_windows;
    }
    return $is_windows;
}

/**
 * Parses array as string
 *
 * @param type $array
 */
function wpcf_parse_array_to_string($array)
{
    $s = '';
    foreach ( (array) $array as $param => $value ) {
        $s .= strval( $param ) . '=' . urlencode( strval( $value ) ) . '&';
    }
    return trim( $s, '&' );
}

/**
 * Get main post ID.
 *
 * @param type $context
 * @return type
 */
function wpcf_get_post_id($context = 'group')
{
    if ( !is_admin() ) {
        /*
         *
         * TODO Check if frontend is fine (rendering children).
         * get_post() previously WP 3.5 requires $post_id
         */
        $post_id = null;
        if ( wpcf_compare_wp_version( '3.5', '<' ) ) {
            global $post;
            $post_id = !empty( $post->ID ) ? $post->ID : -1;
        }
        $_post = get_post( $post_id );
        return !empty( $_post->ID ) ? $_post->ID : -1;
    }
    /*
     * TODO Explore possible usage for $context
     */
    $post = wpcf_admin_get_edited_post();
    return empty( $post->ID ) ? -1 : $post->ID;
}

/**
 * Basic scripts
 */
function wpcf_enqueue_scripts()
{
    if( !is_admin() )
        return;
    
    if ( !wpcf_is_embedded() ) {
        /**
         * Basic JS
         */
        wp_register_script(
            'wpcf-js',
            WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'toolset-colorbox'),
            WPCF_VERSION
        );
        wp_localize_script(
            'wpcf-js',
            'wpcf_js',
            array(
                'close' => __('Close', 'wpcf'),
            )
        );
        wp_enqueue_script('wpcf-js');

        if( function_exists( 'wpcf_admin_add_js_settings' ) ) {
            wpcf_admin_add_js_settings( 'wpcf_nonce_toggle_group',
                '\'' . wp_create_nonce( 'group_form_collapsed' ) . '\'' );
        }
    }
    /**
     * Basic JS
     */
    wp_enqueue_script(
        'wpcf-js-embedded',
        WPCF_EMBEDDED_RES_RELPATH . '/js/basic.js',
        array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'toolset_select2'),
        WPCF_VERSION
    );
    wp_localize_script( 'wpcf-js-embedded', 'WPCF_basic', array(
        'field_already_in_use' => sprintf( __( '%s This field is locked because the same field is added multiple times to this post. %s%s%s you can set the value%s', 'wpcf' ),'<small style="display: block;">', '<a href="#" class="focus_correct_field" data-field-slug="##DATA-FIELD-ID##" >','Here', '</a>','</small>'),
    ));
    
    /*
     *
     * Basic CSS
     */
    wp_enqueue_style( 'wpcf-css-embedded' );

    /*
     *
     * Other components
     */
    if ( !defined( 'WPTOOLSET_FORMS_ABSPATH' ) ) {
        // Repetitive
        wp_enqueue_script(
                'wpcf-repeater',
                WPCF_EMBEDDED_RES_RELPATH . '/js/repetitive.js',
                array('wpcf-js-embedded'), WPCF_VERSION
        );
        wp_enqueue_style(
                'wpcf-repeater',
                WPCF_EMBEDDED_RES_RELPATH . '/css/repetitive.css',
                array('wpcf-css-embedded'), WPCF_VERSION
        );
    }

    // Conditional
    wp_enqueue_script( 'types-conditional' );
    // RTL
    if ( is_rtl() ) {
        wp_enqueue_style(
                'wpcf-rtl', WPCF_EMBEDDED_RES_RELPATH . '/css/rtl.css',
                array('wpcf-css-embedded'), WPCF_VERSION
        );
    }

    /**
     * select2
     */
    $select2_version = '4.0.3';
    if ( !wp_script_is('toolset_select2', 'registered') ) {
        wp_register_script(
            'toolset_select2',
            WPCF_EMBEDDED_TOOLSET_RELPATH. '/toolset-common/res/lib/select2/select2.js',
            array( 'jquery' ),
            $select2_version
        );
    }
    if ( !wp_style_is('toolset-select2-css', 'registered') ) {
        wp_register_style(
            'toolset-select2-css',
            WPCF_EMBEDDED_TOOLSET_RELPATH. '/toolset-common/res/lib/select2/select2.css',
            array(),
            $select2_version
        );
    }
	if ( !wp_style_is('toolset-select2-overrides-css', 'registered') ) {
        wp_register_style(
            'toolset-select2-css',
            WPCF_EMBEDDED_TOOLSET_RELPATH. '/toolset-common/res/lib/select2/select2-overrides.css',
            array('toolset-select2-css'),
            $select2_version
        );
    }
    if ( !wp_style_is('toolset-select2-overrides-css') ) {
        wp_enqueue_style('toolset-select2-overrides-css');
    }

    // Add JS settings
    wpcf_admin_add_js_settings( 'wpcfFormUniqueValuesCheckText',
        '\'' . __( 'Warning: same values selected', 'wpcf' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcfFormUniqueNamesCheckText',
        '\'' . __( 'Warning: field name already used', 'wpcf' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcfFormUniqueSlugsCheckText',
        '\'' . __( 'Warning: field slug already used', 'wpcf' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcfFormUsedOrReservedSlug',
        '\'' . __( 'You cannot use this slug because it is already used or a reserved word. Please choose a different slug.', 'wpcf' ) . '\'' );
}

/**
 * Load all scripts required on edit post screen.
 *
 * @since 1.2.1
 */
function wpcf_edit_post_screen_scripts() {
	wpcf_enqueue_scripts();

	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts(
		array(
			Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,
		)
	);

	wp_enqueue_style( 'wpcf-css-embedded',
		WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(), WPCF_VERSION );
	wp_enqueue_style( 'wpcf-fields-post',
		WPCF_EMBEDDED_RES_RELPATH . '/css/fields-post.css',
		array( 'wpcf-css-embedded' ), WPCF_VERSION );
	wp_enqueue_style( 'wpcf-usermeta',
		WPCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css',
		array( 'wpcf-css-embedded' ), WPCF_VERSION );
	wp_enqueue_script( 'toolset-colorbox' );
	wp_enqueue_style( 'toolset-colorbox' );
	wp_enqueue_style( 'font-awesome' );
}

/**
 * Check if running embedded version.
 *
 * @return boolean
 * @deprecated
 */
function wpcf_is_embedded()
{
    return defined( 'WPCF_RUNNING_EMBEDDED' ) && WPCF_RUNNING_EMBEDDED;
}

/**
 * Returns post type settings.
 *
 * @param string [$post_type]
 * @return array
 * @since unknown
 */
function wpcf_get_custom_post_type_settings($item = '')
{
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom = $post_type_option->get_post_types();
    return !empty( $custom[$item] ) ? $custom[$item] : array();
}

/**
 * Returns Taxonomy settings.
 *
 * @param string $item
 * @return array
 * @since unknown
 */
function wpcf_get_custom_taxonomy_settings($item)
{
    $custom = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    return !empty( $custom[$item] ) ? $custom[$item] : array();
}

/**
 * Load JS and CSS for field type.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @staticvar array $cache
 * @param string $type
 * @return string
 */
function wpcf_field_enqueue_scripts($type)
{
    global $wpcf;
    static $cache = array();

    $config = wpcf_fields_type_action( $type );

    if ( !empty( $config ) ) {

        // Check if cached
        if ( isset( $cache[$config['id']] ) ) {
            return $cache[$config['id']];
        }

        // Use field object
        $wpcf->field->enqueue_script( $config );
        $wpcf->field->enqueue_style( $config );

        $cache[$config['id']] = $config;

        return $config;
    } else {
        $wpcf->debug->errors['missing_type_config'][] = $type;
        return array();
    }

}

/**
 * Get file URL.
 *
 * @uses WPCF_Path (functions taken from CRED_Loader)
 *
 * @param string $file
 * @param bool $use_baseurl
 *
 * @return string
 */
function wpcf_get_file_url($file, $use_baseurl = true)
{
    WPCF_Loader::loadClass( 'path' );
    return WPCF_Path::getFileUrl( $file, $use_baseurl );
}

/**
 * Checks if timestamp supports negative values.
 *
 * @return type
 */
function fields_date_timestamp_neg_supported()
{
    return strtotime( 'Fri, 13 Dec 1950 20:45:54 UTC' ) === -601010046;
}

/**
 * Returns media size.
 *
 * @global type $content_width
 * @param type $widescreen
 * @return type
 */
function wpcf_media_size($widescreen = false)
{
    global $content_width;
    if ( !empty( $content_width ) ) {
        $height = $widescreen ? round( $content_width * 9 / 16 ) : round( $content_width * 3 / 4 );
        return array($content_width, $height);
    }
    return $widescreen ? array(450, 253) : array(450, 320);
}

/**
 * Validation wrapper.
 *
 * @param type $method
 * @param type $args
 * @return boolean
 */
function types_validate($method, $args)
{
    WPCF_Loader::loadClass( 'validation-cakephp' );
    if ( is_callable( array('Wpcf_Cake_Validation', $method) ) ) {
        if ( !is_array( $args ) ) {
            $args = array($args);
        }
        return @call_user_func_array( array('Wpcf_Cake_Validation', $method),
                        $args );
    }
    return false;
}

/**
 * Gets post_types supported by specific group.
 *
 * @param int $group_id
 * @return array list of post types belongs to selected group
 */
function wpcf_admin_get_post_types_by_group($group_id)
{
    $post_types = get_post_meta( $group_id, '_wp_types_group_post_types', true );
    if ( $post_types == 'all' ) {
        return array();
    }
    $post_types = trim( $post_types, ',' );
    if ( empty($post_types) ) {
        return array();
    }
    $post_types = explode( ',', trim( $post_types, ',' ) );
    return $post_types;
}

/**
 * Filter return the array of Types fields
 *
 * Filter return the array of Types active fields
 *
 * @since x.x.x
 *
 * @param array fields Unused argument
 */
add_filter('wpcf_get_all_fields_slugs', 'wpcf_get_all_fields_slugs');

/**
 * Function return the array of Types fields.
 *
 * Function return the array of Types active fields slugs.
 *
 * @since x.x.x
 *
 * @return array List of slugs
 */
function wpcf_get_all_fields_slugs($fields)
{
    $post_meta_keys = array();
    foreach (wpcf_admin_fields_get_fields( true, true ) as $key => $data ) {
        $post_meta_keys[] = $data['meta_key'];
    }
    return $post_meta_keys;
}
/**
 * Get buuild in taxonomies.
 *
 * This is a wrapper for WordPress get_taxonomies() function. It gets public 
 * build-in taxonomies.
 *
 * @since 1.9.0
 *
 * @param string $output The type of output to return, either taxonomy 'names'
 * or 'objects'. Default: 'names'
 *
 * @return array Array of taxonomies.
 *
 * @deprecated Use Types_Utils::get_builtin_taxonomies() instead.
 */
function wpcf_get_builtin_in_taxonomies($output = 'names')
{
    static $taxonomies = array();
    if ( empty( $taxonomies ) ) {
        $taxonomies = get_taxonomies(array('public' => true, '_builtin' => true), $output);
    }
    /**
     * remove post_format
     */
    if ( isset( $taxonomies['post_format'] ) ) {
        unset($taxonomies['post_format']);
    }
    return $taxonomies;
}

/**
 * Check is a build-in taxonomy
 *
 * Check is that build-in taxonomy?
 *
 * @since 1.9.0
 *
 * @parem string taxonomy slug
 * @return boolean is this build-in taxonomy
 */
function wpcf_is_builtin_taxonomy($taxonomy)
{
    switch($taxonomy) {
    case 'post_tag':
    case 'category':
        return true;
    }
    return in_array($taxonomy, wpcf_get_builtin_in_taxonomies());
}

function wpcf_get_builtin_in_post_types()
{
    static $post_types = array();
    if ( empty( $post_types ) ) {
        $post_types = get_post_types(array('public' => true, '_builtin' => true));
    }
    return $post_types;
}

function wpcf_is_builtin_post_types($post_type)
{
    $post_types = wpcf_get_builtin_in_post_types();
    return in_array($post_type, $post_types);
}

/**
 * Check is a build-in taxonomy
 *
 * Check is that build-in taxonomy?
 *
 * @since 1.9.0
 *
 * @parem string taxonomy slug
 * @return boolean is this build-in taxonomy
 */
function wpcf_builtin_preview_only($ct, $form)
{
    if ( isset($ct['_builtin']) && $ct['_builtin'] ) {

        foreach( $form as $key => $data ) {
            if ( !isset($data['#type'] ) ) {
                continue;
            }
            if ( isset($data['_builtin']) ) {
                switch( $data['#type'] ) {
                case 'textfield':
                case 'textarea':
                    $form[$key]['#attributes']['readonly'] = 'readonly';
                    break;
                default:
                }
                continue;
            }
            unset($form[$key]);
        }
    }
    return $form;
}


/**
 * Adds JS settings.
 *
 * @static array $settings
 * @param type $id
 * @param type $setting
 * @return string
 */
function wpcf_admin_add_js_settings( $id, $setting = '' )
{
    static $settings = array();
    $settings['wpcf_nonce_ajax_callback'] = '\'' . wp_create_nonce( 'execute' ) . '\'';
    $settings['wpcf_cookiedomain'] = '\'' . COOKIE_DOMAIN . '\'';
    $settings['wpcf_cookiepath'] = '\'' . COOKIEPATH . '\'';
    if ( $id == 'get' ) {
        $temp = $settings;
        $settings = array();
        return $temp;
    }
    $settings[$id] = $setting;
}

/**
 * Use sanitize_text_field recursively.
 *
 * @since 1.9.0
 *
 * @param mixed $data data to sanitize_text_field
 * @return mixed sanitized input
 */
function sanitize_text_field_recursively($data)
{
    if ( empty($data) ) {
        return $data;
    }
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = sanitize_text_field_recursively($value);
            } else {
               $value = sanitize_text_field($value);
            }
            $data[$key] = $value;
        }
        return $data;
    }
    return sanitize_text_field($data);
}

/**
 * Gets user roles supported by specific group.
 *
 * @param type $group_id
 * @return type
 */
function wpcf_admin_get_groups_showfor_by_group($group_id) {
    $for_users = get_post_meta($group_id, '_wp_types_group_showfor', true);
    if (empty($for_users) || $for_users == 'all') {
        return array();
    }
    $for_users = explode(',', trim($for_users, ','));
    return $for_users;
}
