<?php
/**
 *
 * Admin functions
 *
 *
 */
require_once WPCF_ABSPATH.'/marketing.php';
require_once WPCF_ABSPATH.'/includes/classes/class.wpcf.roles.php';
WPCF_Roles::getInstance();
/*
 * This needs to be called after main 'init' hook.
 * Main init hook calls required Types code for frontend.
 * Admin init hook only in admin area.
 *
 */
add_action( 'admin_init', 'wpcf_admin_init_hook', 11 );

add_action( 'init', 'wpcf_init_admin_pages' );

add_action( 'wpcf_admin_page_init', 'wpcf_enqueue_scripts' );

// OMG, why so early? At this point we don't even have embedded Types (with functions.php).
if ( defined( 'DOING_AJAX' ) ) {
    require_once WPCF_INC_ABSPATH . '/ajax.php';
    if ( isset($_REQUEST['action']) ) {
        switch( $_REQUEST['action']){
            /**
             * post edit screen
             */
        case 'wpcf_edit_post_get_child_fields_screen':
        case 'wpcf_edit_post_get_icons_list':
        case 'wpcf_edit_post_save_child_fields':
            require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.edit.post.type.php';
            new Types_Admin_Edit_Post_Type();
            break;
            /**
             * custom fields group edit screen
             */
        case 'wpcf_ajax_filter':
        case 'wpcf_edit_field_choose':
        case 'wpcf_edit_field_insert':
        case 'wpcf_edit_field_select':
        case 'wpcf_edit_field_add_existed': {

	        require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.edit.custom.fields.group.php';

	        // Be careful here. For some AJAX actions we rely on the fact that the page parameter is not set and/or
	        // that post and user fields can use the same handler (which is originally meant for post fields only).

	        // We don't have functions.php at this point, can't use wpcf_getpost().
	        $current_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : Types_Admin_Edit_Custom_Fields_Group::PAGE_NAME;
	        if( in_array( $current_page, array( Types_Admin_Edit_Custom_Fields_Group::PAGE_NAME, 'wpcf-edit-usermeta' ) ) ) {
		        new Types_Admin_Edit_Custom_Fields_Group();
	        }

	        // For other pages, we will initialize during the 'init' hook when the autoloader is already available.
	        // At this point we don't even have access to names of the pages.
	        // See wpcf_init_admin_pages().
	        break;
        }
        case 'wpcf_edit_field_condition_get':
        case 'wpcf_edit_field_condition_get_row':
        case 'wpcf_edit_field_condition_save':
        case 'wpcf_edit_custom_field_group_get':
            require_once WPCF_INC_ABSPATH.'/classes/class.types.fields.conditional.php';
            new Types_Fields_Conditional();
            break;
        case 'wpcf_edit_post_get_fields_box':
            require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.fields.php';
            new Types_Admin_Fields();
            break;
        }
    }
}
include_once WPCF_ABSPATH.'/includes/classes/class.wpcf.marketing.messages.php';
new WPCF_Types_Marketing_Messages();

/**
 * last edit flag
 */
if ( !defined('TOOLSET_EDIT_LAST' )){
    define( 'TOOLSET_EDIT_LAST', '_toolset_edit_last');
}

/**
 * last author
 */
if ( !defined('WPCF_AUTHOR' )){
    define( 'WPCF_AUTHOR', '_wpcf_author_id');
}

/**
 * admin_init hook.
 */
function wpcf_admin_init_hook()
{
    wp_register_style('wpcf-css-embedded', WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(), WPCF_VERSION );
}


/**
 * Initialize admin pages.
 *
 * @todo This, also, needs a review very badly.
 * @since 1.9
 */
function wpcf_init_admin_pages() {

	if( defined( 'DOING_AJAX' ) ) {
		$action = wpcf_getpost( 'action' );
		$current_page = wpcf_getpost( 'page' );

		switch( $action ) {

			case 'wpcf_edit_field_select':
			case 'wpcf_ajax_filter': {
				if( WPCF_Page_Edit_Termmeta::PAGE_NAME == $current_page ) {
					WPCF_Page_Edit_Termmeta::get_instance()->initialize_ajax_handler();
				}
				break;
			}
		}
	}


}


function wpcf_admin_calculate_menu_page_capability( $data ) {
	$capability = array_key_exists( 'capability', $data ) ? $data['capability'] : 'manage_options';
    $wpcf_capability = apply_filters( 'wpcf_capability', $capability, $data, $data['slug'] );
    $wpcf_capability = apply_filters( 'wpcf_capability' . $data['slug'], $capability, $data, $data['slug'] );
    /**
     * allow change capability  by filter
     * full list https://goo.gl/OJYTvl
     */
    if ( isset( $data['capability_filter'] ) ) {
        $wpcf_capability = apply_filters( $data['capability_filter'], $wpcf_capability, $data, $data['slug'] );
    }
	return $wpcf_capability;
}

function wpcf_admin_calculate_menu_page_load_hook( $data ) {
	$load_hook = '';
	if ( array_key_exists( 'load_hook', $data ) ) {
		$load_hook = $data['load_hook'];
	} else if ( 
		array_key_exists( 'callback', $data ) 
		&& is_string( $data['callback' ] ) 
	) {
        $load_hook = sprintf( '%s_hook', $data['callback'] );
    }
	return $load_hook;
}


/**
 * Add legacy menu pages. 
 * 
 * This is indirectly hooked to toolset_filter_register_menu_pages through the Types_Admin_Menu controller.
 * 
 * @param $pages
 * @return mixed
 * @since 2.0
 */
function wpcf_admin_toolset_register_menu_pages( $pages ) {
	if( ! apply_filters( 'types_register_pages', true ) )
		return $pages;
	
	require_once WPCF_ABSPATH . '/help.php';
	
	$current_page = '';
	if ( isset( $_GET['page'] ) ) {
	    $current_page = sanitize_text_field( $_GET['page'] );
	}
	
	$pages['wpcf-cpt'] = array(
		'slug'				=> 'wpcf-cpt',
        'menu_title'		=> __( 'Post Types', 'wpcf' ),
        'page_title'		=> __( 'Post Types', 'wpcf' ),
        'callback'  		=> 'wpcf_admin_menu_summary_cpt',
        'capability_filter'	=> 'wpcf_cpt_view',
        'capability'		=> WPCF_CUSTOM_POST_TYPE_VIEW,
    );
	$pages['wpcf-cpt']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-cpt'] );
	$pages['wpcf-cpt']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-cpt'] );
	$pages['wpcf-cpt']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-cpt' );
	$pages['wpcf-cpt']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'wpcf-edit-type' ) {
		$pages['wpcf-edit-type'] = array(
			'slug'				=> 'wpcf-edit-type',
			'menu_title'		=> isset( $_GET['wpcf-post-type'] ) ? __( 'Edit Post Type', 'wpcf' ) : __( 'Add New Post Type', 'wpcf' ),
			'page_title'		=> isset( $_GET['wpcf-post-type'] ) ? __( 'Edit Post Type', 'wpcf' ) : __( 'Add New Post Type', 'wpcf' ),
			'callback'  		=> 'wpcf_admin_menu_edit_type',
			'capability'		=> WPCF_CUSTOM_FIELD_EDIT,
			'load_hook'			=> 'wpcf_admin_menu_edit_type_hook'
		);
		$pages['wpcf-edit-type']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-edit-type'] );
		$pages['wpcf-edit-type']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-edit-type'] );
		$pages['wpcf-edit-type']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-edit-type' );
		$pages['wpcf-edit-type']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'wpcf-view-type' ) {
		$pages['wpcf-view-type'] = array(
			'slug'				=> 'wpcf-view-type',
			'menu_title'		=> __( 'View Post Type', 'wpcf' ),
			'page_title'		=> __( 'View Post Type', 'wpcf' ),
			'callback'  		=> 'wpcf_admin_menu_edit_type',
			'capability'		=> WPCF_CUSTOM_FIELD_VIEW,
			'load_hook'			=> 'wpcf_admin_menu_edit_type_hook'
		);
		$pages['wpcf-view-type']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-view-type'] );
		$pages['wpcf-view-type']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-view-type'] );
		$pages['wpcf-view-type']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-view-type' );
		$pages['wpcf-view-type']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	$pages['wpcf-ctt'] = array(
		'slug'				=> 'wpcf-ctt',
        'menu_title'		=> __( 'Taxonomies', 'wpcf' ),
        'page_title'		=> __( 'Taxonomies', 'wpcf' ),
        'callback'			=> 'wpcf_admin_menu_summary_ctt',
        'capability_filter' => 'wpcf_ctt_view',
        'capability'		=> WPCF_CUSTOM_TAXONOMY_VIEW,
    );
	$pages['wpcf-ctt']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-ctt'] );
	$pages['wpcf-ctt']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-ctt'] );
	$pages['wpcf-ctt']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-ctt' );
	$pages['wpcf-ctt']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'wpcf-edit-tax' ) {
		$pages['wpcf-edit-tax'] = array(
			'slug'				=> 'wpcf-edit-tax',
			'menu_title'		=> isset( $_GET['wpcf-tax'] ) ? __( 'Edit Taxonomy', 'wpcf' ) : __( 'Add New Taxonomy', 'wpcf' ),
			'page_title'		=> isset( $_GET['wpcf-tax'] ) ? __( 'Edit Taxonomy', 'wpcf' ) : __( 'Add New Taxonomy', 'wpcf' ),
			'callback'  		=> 'wpcf_admin_menu_edit_tax',
			'capability'		=> WPCF_CUSTOM_TAXONOMY_EDIT,
			'load_hook'			=> 'wpcf_admin_menu_edit_tax_hook'
		);
		$pages['wpcf-edit-tax']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-edit-tax'] );
		$pages['wpcf-edit-tax']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-edit-tax'] );
		$pages['wpcf-edit-tax']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-edit-tax' );
		$pages['wpcf-edit-tax']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'wpcf-view-tax' ) {
		$pages['wpcf-view-tax'] = array(
			'slug'				=> 'wpcf-view-tax',
			'menu_title'		=> __( 'View Taxonomy', 'wpcf' ),
			'page_title'		=> __( 'View Taxonomy', 'wpcf' ),
			'callback'  		=> 'wpcf_admin_menu_edit_tax',
			'capability'		=> WPCF_CUSTOM_TAXONOMY_VIEW,
			'load_hook'			=> 'wpcf_admin_menu_edit_tax_hook'
		);
		$pages['wpcf-view-tax']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-view-tax'] );
		$pages['wpcf-view-tax']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-view-tax'] );
		$pages['wpcf-view-tax']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-view-tax' );
		$pages['wpcf-view-tax']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	
	$pages['wpcf-cf'] = array(
		'slug'				=> 'wpcf-cf',
        'menu_title'		=> __( 'Post Fields', 'wpcf' ),
        'page_title'		=> __( 'Post Fields', 'wpcf' ),
        'callback'			=> 'wpcf_admin_menu_summary',
        'capability_filter' => 'wpcf_cf_view',
        'capability'		=> WPCF_CUSTOM_FIELD_VIEW,
    );
	$pages['wpcf-cf']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-cf'] );
	$pages['wpcf-cf']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-cf'] );
	$pages['wpcf-cf']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-cf' );
	$pages['wpcf-cf']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'wpcf-edit' ) {
		$pages['wpcf-edit'] = array(
			'slug'				=> 'wpcf-edit',
			'menu_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit Group', 'wpcf' ) : __( 'Add New Post Field Group', 'wpcf' ),
			'page_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit Group', 'wpcf' ) : __( 'Add New Post Field Group', 'wpcf' ),
			'callback'			=> 'wpcf_admin_menu_edit_fields',
			'capability'		=> WPCF_CUSTOM_FIELD_VIEW,
			'load_hook'			=> 'wpcf_admin_menu_edit_fields_hook'
		);
		$pages['wpcf-edit']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-edit'] );
		$pages['wpcf-edit']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-edit'] );
		$pages['wpcf-edit']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-edit' );
		$pages['wpcf-edit']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'wpcf-view-custom-field' ) {
		$pages['wpcf-view-custom-field'] = array(
			'slug'				=> 'wpcf-view-custom-field',
			'menu_title'		=> __( 'View Post Field Group', 'wpcf' ),
			'page_title'		=> __( 'View Post Field Group', 'wpcf' ),
			'callback'			=> 'wpcf_admin_menu_edit_fields',
			'capability'		=> WPCF_CUSTOM_FIELD_VIEW,
		);
		$pages['wpcf-view-custom-field']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-view-custom-field'] );
		$pages['wpcf-view-custom-field']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-view-custom-field'] );
		$pages['wpcf-view-custom-field']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-view-custom-field' );
		$pages['wpcf-view-custom-field']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}

	$WPCF_Page_Listing_Termmeta = WPCF_Page_Listing_Termmeta::get_instance();
	$pages[WPCF_Page_Listing_Termmeta::PAGE_NAME] = $WPCF_Page_Listing_Termmeta->add_submenu_page();
	
	
	if ( $current_page == WPCF_Page_Edit_Termmeta::PAGE_NAME ) {
		$WPCF_Page_Edit_Termmeta = WPCF_Page_Edit_Termmeta::get_instance();
		$pages[WPCF_Page_Edit_Termmeta::PAGE_NAME] = $WPCF_Page_Edit_Termmeta->add_submenu_page();
	}
	
    $pages['wpcf-um'] = array(
		'slug'				=> 'wpcf-um',
        'menu_title'		=> __( 'User Fields', 'wpcf' ),
        'page_title'		=> __( 'User Fields', 'wpcf' ),
        'callback'			=> 'wpcf_usermeta_summary',
        'capability_filter' => 'wpcf_uf_view',
        'capability'		=> WPCF_USER_META_FIELD_VIEW,
    );
	$pages['wpcf-um']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-um'] );
	$pages['wpcf-um']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-um'] );
	$pages['wpcf-um']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-um' );
	$pages['wpcf-um']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	
	if ( $current_page == 'wpcf-edit-usermeta' ) {
		$pages['wpcf-edit-usermeta'] = array(
			'slug'				=> 'wpcf-edit-usermeta',
			'menu_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit User Field Group', 'wpcf' ) : __( 'Add New User Field Group', 'wpcf' ),
			'page_title'		=> isset( $_GET['group_id'] ) ? __( 'Edit User Field Group', 'wpcf' ) : __( 'Add New User Field Group', 'wpcf' ),
			'callback'			=> 'wpcf_admin_menu_edit_user_fields',
			'capability'		=> WPCF_USER_META_FIELD_EDIT,
		);
		$pages['wpcf-edit-usermeta']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-edit-usermeta'] );
		$pages['wpcf-edit-usermeta']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-edit-usermeta'] );
		$pages['wpcf-edit-usermeta']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-edit-usermeta' );
		$pages['wpcf-edit-usermeta']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	
	if ( $current_page == 'wpcf-view-usermeta' ) {
		$pages['wpcf-view-usermeta'] = array(
			'slug'				=> 'wpcf-view-usermeta',
			'menu_title'		=> __( 'View User Field Group', 'wpcf' ),
			'page_title'		=> __( 'View User Field Group', 'wpcf' ),
			'callback'			=> 'wpcf_admin_menu_edit_user_fields',
			'capability'		=> WPCF_USER_META_FIELD_VIEW,
		);
		$pages['wpcf-view-usermeta']['capability'] = wpcf_admin_calculate_menu_page_capability( $pages['wpcf-view-usermeta'] );
		$pages['wpcf-view-usermeta']['load_hook'] = wpcf_admin_calculate_menu_page_load_hook( $pages['wpcf-view-usermeta'] );
		$pages['wpcf-view-usermeta']['contextual_help_legacy'] = wpcf_admin_help( 'wpcf-view-usermeta' );
		$pages['wpcf-view-usermeta']['contextual_help_hook'] = 'wpcf_admin_help_add_tabs_load_hook';
	}
	

	if ( 'installer' == $current_page ) {
		// @todo Having a page with a slug "installer" is a direct path to a third-party plugin conflict. Just saying. Not to mention the callback funciton "installer_content", for god's sake
		$pages['installer'] = array(
			'slug'				=> 'installer',
			'menu_title'		=> __( 'Installer', 'wpcf' ),
			'page_title'		=> __( 'Installer', 'wpcf' ),
			'callback'			=> 'installer_content',
		);
	}

	/**
	* This used to load the Toolset Access teaser - code has not been removed
	*
	* This also usd to hook the Installer page, added above
	*
	* @todo this is to be deletd IMHO
	*/
    //do_action( 'wpcf_menu_plus' );
	
	return $pages;
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_debug_information()
{
    require_once WPCF_EMBEDDED_TOOLSET_ABSPATH .'/toolset-common/debug/debug-information.php';
}


/**
 * Menu page hook.
 */
function wpcf_usermeta_summary_hook()
{
    do_action( 'wpcf_admin_page_init' );
    wpcf_admin_load_collapsible();
    wpcf_admin_page_add_options('uf',  __( 'User Fields', 'wpcf' ));
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_summary_hook()
{
    do_action( 'wpcf_admin_page_init' );
    wpcf_admin_load_collapsible();
    wpcf_admin_page_add_options('cf',  __( 'Post Fields', 'wpcf' ));
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_summary()
{
    wpcf_add_admin_header( __( 'Post Field Groups', 'wpcf' ), array('page'=>'wpcf-edit'));
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-list.php';
    $to_display = wpcf_admin_fields_get_fields();
    if ( !empty( $to_display ) ) {
        add_action( 'wpcf_groups_list_table_after', 'wpcf_admin_promotional_text' );
    }
    wpcf_admin_fields_list();
    wpcf_add_admin_footer();
}


function wpcf_admin_enqueue_group_edit_page_assets() {
	do_action( 'wpcf_admin_page_init' );

	$asset_manager = Types_Asset_Manager::get_instance();

	// Group filter
	wp_enqueue_script( 'wpcf-filter-js',
		WPCF_EMBEDDED_RES_RELPATH
		. '/js/custom-fields-form-filter.js', array('jquery'), WPCF_VERSION );


	$asset_manager->enqueue_scripts(
		array(
			Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,

            // These scripts are needed only for the Styling editor
            Types_Asset_Manager::SCRIPT_CODEMIRROR,
            Types_Asset_Manager::SCRIPT_CODEMIRROR_CSS,
            Types_Asset_Manager::SCRIPT_CODEMIRROR_XML,
            Types_Asset_Manager::SCRIPT_CODEMIRROR_HTMLMIXED,
            Types_Asset_Manager::SCRIPT_JSCROLLPANE,
            Types_Asset_Manager::SCRIPT_MOUSEWHEEL
		)
	);

	// MAIN
	wp_enqueue_script(
		'wpcf-fields-form',
		WPCF_EMBEDDED_RES_RELPATH.'/js/fields-form.js',
		array( 'wpcf-js' ),
		WPCF_VERSION
	);
	wp_enqueue_script(
		'wpcf-admin-fields-form',
		WPCF_RES_RELPATH.'/js/fields-form.js',
		array(),
		WPCF_VERSION
	);

	$asset_manager->enqueue_styles(
	    array(
		    // These styles are needed only for the Styling editor
	        Types_Asset_Manager::STYLE_CODEMIRROR,
            Types_Asset_Manager::STYLE_EDITOR_ADDON_MENU_SCROLL
        )
    );

	wp_enqueue_script( 'wpcf-form-codemirror-editor-resize' ,
		WPCF_RELPATH . '/resources/js/jquery_ui/jquery.ui.resizable.min.js', array('wpcf-js'));

	wp_enqueue_style( 'wpcf-usermeta', WPCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css' );

	wp_enqueue_style( 'font-awesome' );

	add_action( 'admin_footer', 'wpcf_admin_fields_form_js_validation' );

}


/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_fields_hook() {
	wpcf_admin_enqueue_group_edit_page_assets();

	require_once WPCF_INC_ABSPATH . '/fields.php';
	require_once WPCF_INC_ABSPATH . '/fields-form.php';
	//    $form = wpcf_admin_fields_form();

	$wpcf_admin = new Types_Admin_Edit_Custom_Fields_Group();
	$wpcf_admin->init_admin();
	$form = $wpcf_admin->form();
	wpcf_form( 'wpcf_form_fields', $form );
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_fields()
{
    $add_new = false;
    $post_type = current_filter();
    $title = __('View Post Field Group', 'wpcf');
    if ( isset( $_GET['group_id'] ) ) {
        if ( WPCF_Roles::user_can_edit('custom-field', array('id' => (int) $_GET['group_id']))) {
            $title = __( 'Edit Post Field Group', 'wpcf' );
            $add_new = array(
                'page' => 'wpcf-edit',
            );
        }
    } else if ( WPCF_Roles::user_can_create('custom-field')) {
        $title = __( 'Add New Post Field Group', 'wpcf' );
    }
    wpcf_add_admin_header( $title, $add_new );
    wpcf_wpml_warning();
    $form = wpcf_form( 'wpcf_form_fields' );
    echo '<form method="post" action="" class="wpcf-fields-form wpcf-form-validate js-types-show-modal">';
    wpcf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    wpcf_add_admin_footer();
}


function wpcf_admin_page_add_options( $name, $label)
{
    $option = 'per_page';
    $args = array(
        'label' => $label,
        'default' => 10,
        'option' => sprintf('wpcf_%s_%s', $name, $option),
    );
    add_screen_option( $option, $args );
}


function wpcf_admin_menu_summary_cpt_ctt_hook()
{
    do_action( 'wpcf_admin_page_init' );
    wpcf_admin_load_collapsible();
    require_once WPCF_INC_ABSPATH . '/custom-types.php';
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies.php';
    require_once WPCF_INC_ABSPATH . '/custom-types-taxonomies-list.php';
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_summary_cpt_hook()
{
    wpcf_admin_menu_summary_cpt_ctt_hook();
    wpcf_admin_page_add_options('cpt',  __( 'Post Types', 'wpcf' ));
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_summary_cpt()
{
    wpcf_add_admin_header(
        __( 'Post Types', 'wpcf' ),
        array('page'=>'wpcf-edit-type'),
        __('Add New', 'wpcf')
    );
	$post_type_option = new Types_Utils_Post_Type_Option();
    $to_display_posts = $post_type_option->get_post_types();
    $to_display_tax = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
    if ( !empty( $to_display_posts ) || !empty( $to_display_tax ) ) {
        add_action( 'wpcf_types_tax_list_table_after', 'wpcf_admin_promotional_text' );
    }
    wpcf_admin_custom_post_types_list();
    wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_summary_ctt_hook()
{
    wpcf_admin_menu_summary_cpt_ctt_hook();
    wpcf_admin_page_add_options('ctt',  __( 'Taxonomies', 'wpcf' ));
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_summary_ctt()
{
    wpcf_add_admin_header( __( 'Taxonomies', 'wpcf' ), array('page' => 'wpcf-edit-tax') );
    wpcf_admin_custom_taxonomies_list();
    do_action('wpcf_types_tax_list_table_after');
    wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_type_hook()
{
    require_once WPCF_INC_ABSPATH . '/fields.php';
    do_action( 'wpcf_admin_page_init' );
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/custom-types.php';
    require_once WPCF_INC_ABSPATH . '/custom-types-form.php';
    require_once WPCF_INC_ABSPATH . '/post-relationship.php';
    wp_enqueue_script( 'wpcf-custom-types-form',
            WPCF_RES_RELPATH . '/js/'
            . 'custom-types-form.js', array('jquery', 'jquery-ui-dialog', 'jquery-masonry'), WPCF_VERSION );

    wp_enqueue_style('wp-jquery-ui-dialog');

	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts(
		array(
			Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,
        )
    );

			add_action( 'admin_footer', 'wpcf_admin_types_form_js_validation' );
    wpcf_post_relationship_init();

	// New page controller script.
	$asset_manager->enqueue_scripts( Types_Asset_Manager::SCRIPT_PAGE_EDIT_POST_TYPE );

    /**
     * add form
     */
    //    $form = wpcf_admin_custom_types_form();
    require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.edit.post.type.php';
    $wpcf_admin = new Types_Admin_Edit_Post_Type();
    $wpcf_admin->init_admin();
    $form = $wpcf_admin->form();
    wpcf_form( 'wpcf_form_types', $form );
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_type()
{
    $post_type = current_filter();
    $title = __('View Post Type', 'wpcf');
    if ( WPCF_Roles::user_can_edit('custom-post-type', array()) ) {
        if ( isset( $_GET['wpcf-post-type'] ) ) {
            $title = __( 'Edit Post Type', 'wpcf' );
            /**
             * add new CPT link
             */
            $title .= sprintf(
                '<a href="%s" class="add-new-h2">%s</a>',
                esc_url(add_query_arg( 'page', 'wpcf-edit-type', admin_url('admin.php'))),
                __('Add New', 'wpcf')
            );
        } else {
            $title = __( 'Add New Post Type', 'wpcf' );
        }
    }
    wpcf_add_admin_header( $title );
    wpcf_wpml_warning();
    $form = wpcf_form( 'wpcf_form_types' );
    echo '<form method="post" action="" class="wpcf-types-form wpcf-form-validate js-types-do-not-show-modal">';
    wpcf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_tax_hook()
{
    do_action( 'wpcf_admin_page_init' );

    wp_enqueue_script( 'wpcf-taxonomy-form',
        WPCF_RES_RELPATH . '/js/'
        . 'taxonomy-form.js', array( 'jquery' ), WPCF_VERSION );


	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts(
		array(
			Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,

			// New page controller script.
			Types_Asset_Manager::SCRIPT_PAGE_EDIT_TAXONOMY
		)
	);

    add_action( 'admin_footer', 'wpcf_admin_tax_form_js_validation' );
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/custom-taxonomies.php';
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies-form.php';
//    $form = wpcf_admin_custom_taxonomies_form();
    require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.edit.taxonomy.php';
    $wpcf_admin = new Types_Admin_Edit_Taxonomy();
    $wpcf_admin->init_admin();
    $form = $wpcf_admin->form();
    wpcf_form( 'wpcf_form_tax', $form );
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_tax()
{
    $post_type = current_filter();
    $title = __( 'View Taxonomy', 'wpcf' );
    $add_new = false;
    if ( WPCF_Roles::user_can_create('custom-taxonomy') ) {
        $title = __( 'Add New Taxonomy', 'wpcf' );
        if ( isset( $_GET['wpcf-tax'] ) ) {
            $title = __( 'Edit Taxonomy', 'wpcf' );
            $add_new = array('page' => 'wpcf-edit-tax' );
        }
    }
    wpcf_add_admin_header( $title, $add_new);
    wpcf_wpml_warning();
    $form = wpcf_form( 'wpcf_form_tax' );
    echo '<form method="post" action="" class="wpcf-tax-form wpcf-form-validate js-types-show-modal">';
    wpcf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    wpcf_add_admin_footer();
}

/**
* Export and Import, hooks and admin page tab
*
* This is screaming for a controller class...
*/

add_action( 'wp_loaded', 'wpcf_admin_export_on_form_submit' );

function wpcf_admin_export_on_form_submit() {
	require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/import-export.php';
    if ( 
		extension_loaded( 'simplexml' ) 
		&& isset( $_POST['types_export'] )
		&& isset( $_POST['types_export_wpnonce'] )
        && wp_verify_nonce( $_POST['types_export_wpnonce'], 'wpcf_export' ) 
	) {
        wpcf_admin_export_data();
        die();
    }
}

add_action( 'wp_loaded', 'wpcf_admin_import_on_form_submit' );

function wpcf_admin_import_on_form_submit() {
	require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/import-export.php';
	global $wpcf_import_messages;
	$wpcf_import_messages = array();
    if ( 
		extension_loaded( 'simplexml' ) 
		&& isset( $_POST['types-import-final'] )
		&& isset( $_POST['types_import_wpnonce'] ) 
		&& wp_verify_nonce( $_POST['types_import_wpnonce'], 'wpcf_import' )
	) {
        $wpcf_import_messages = wpcf_admin_import_final_data();
    }
}

add_action( 'admin_notices', 'wpcf_admin_import_admin_notices' );

function wpcf_admin_import_admin_notices() {
	global $wpcf_import_messages;
	if ( count( $wpcf_import_messages ) > 0 ) {
		$success_messages = wp_list_filter( $wpcf_import_messages, array( 'type' => 'success' ) );
		$error_messages = wp_list_filter( $wpcf_import_messages, array( 'type' => 'error' ) );
		if ( count( $success_messages ) > 0 ) {
		?>
		<div class="notice message updated is-dismissible">
				<h3><?php _e( 'Types import summary', 'wpcf' ); ?></h3>
				<ul class="toolset-taglike-list">
				<?php
				foreach ( $success_messages as $message ) {
					?>
					<li><?php echo $message['content']; ?></li>
					<?php
				}
				?>
				</ul>
			</div>
		<?php
		}
		if ( count( $error_messages ) > 0 ) {
		?>
		<div class="notice message error">
				<h3><?php _e( 'Types import errors', 'wpcf' ); ?></h3>
				<ul>
				<?php
				foreach ( $error_messages as $message ) {
					?>
					<li><?php echo $message['content']; ?></li>
					<?php
				}
				?>
				</ul>
			</div>
		<?php
		}
		
	}
}

add_filter( 'toolset_filter_register_export_import_section', 'wpcf_toolset_register_export_import_sections' );

function wpcf_toolset_register_export_import_sections( $sections ) {
	$sections['types'] = array(
		'slug'		=> 'types',
		'title'		=> __( 'Types', 'wpcf' ),
		'icon'		=> '<i class="icon-types-logo ont-icon-16"></i>',
		'items'		=> array(
			'export'	=> array(
							'title'		=> __( 'Export Types data', 'wpcf' ),
							'callback'	=> 'wpcf_render_export_form',
						),
			'import'	=> array(
							'title'		=> __( 'Import Types data', 'wpcf' ),
							'callback'	=> 'wpcf_render_import_form',
						)
		)
	);
	return $sections;
}

add_action( 'toolset_enqueue_scripts', 'wpcf_toolset_shared_pages_enqueue_script' );

function wpcf_toolset_shared_pages_enqueue_script( $current_page ) {
	if ( $current_page == 'toolset-export-import' ) {
		wp_enqueue_script( 'types-export-import' );
	}
	if ( $current_page == 'toolset-settings' ) {
		wp_enqueue_script( 'types-settings' );
	}
}

function wpcf_render_export_form() {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/import-export.php';
	echo '<form method="post" action="' . admin_url('edit.php') . '" class="wpcf-import-export-form '
    . 'wpcf-form-validate" enctype="multipart/form-data">';
    echo wpcf_admin_export_form();
    echo '</form>';
}

function wpcf_render_import_form() {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/import-export.php';
	echo '<form method="post" action="' . admin_url('admin.php') . '?page=toolset-export-import&tab=types" class="wpcf-import-export-form '
    . 'wpcf-form-validate" enctype="multipart/form-data">';
	if ( 
		isset( $_POST['types_import_wpnonce'] ) 
		&& wp_verify_nonce( $_POST['types_import_wpnonce'], 'wpcf_import' ) 
		&& isset( $_POST['types-import-step'] )
	) {
		echo wpcf_admin_import_confirmation_form();
	} else {
		echo wpcf_admin_import_form();
	}
    echo '</form>';
}


add_filter( 'toolset_filter_toolset_register_settings_section', 'wpcf_register_settings_custom_content_section', 20 );

function wpcf_register_settings_custom_content_section( $sections ) {
	$sections['custom-content'] = array(
		'slug'	=> 'custom-content',
		'title'	=> __( 'Custom Content', 'wpcf' )
	);
	return $sections;
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'wpcf_admin_settings_for_images' );

function wpcf_admin_settings_for_images( $sections ) {
	$settings = wpcf_get_settings();
	$section_content = '';
	ob_start();
	$form['images'] = array(
		'#title' => '<h3>' . __('Images resizing', 'wpcf') . '</h3>',
        '#id' => 'add_resized_images_to_library',
        '#name' => 'wpcf_add_resized_images_to_library',
        '#type' => 'checkbox',
        '#label' => __('Add resized images to the media library', 'wpcf'),
        '#description' => __('Types will automatically add the resized images as attachments to the media library.', 'wpcf'),
        '#inline' => true,
        '#default_value' => !empty($settings['add_resized_images_to_library']),
        '#pattern' => '<TITLE><ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote'] = array(
        '#id' => 'images_remote',
        '#name' => 'wpcf_images_remote',
        '#type' => 'checkbox',
        '#label' => __('Allow resizing of remote images', 'wpcf'),
        '#description' => __('Types will try to scale remote images.', 'wpcf'),
        '#inline' => true,
        '#default_value' => !empty($settings['images_remote']),
        '#pattern' => '<ELEMENT><LABEL><DESCRIPTION>',
    );
    $form['images_remote_clear'] = array(
		'#title' => '<h3>' . __('Images caching', 'wpcf') . '</h3>',
        '#id' => 'images_remote_cache_time',
        '#name' => 'wpcf_images_remote_cache_time',
        '#type' => 'select',
        '#pattern' => '<TITLE>' . __('Invalidate cached images that are more than <ELEMENT> hours old.', 'wpcf'),
        '#options' => array(
            __('Never', 'wpcf') => '0',
            '24' => '24',
            '36' => '36',
            '48' => '48',
            '72' => '72',
        ),
        '#inline' => false,
        '#default_value' => intval($settings['images_remote_cache_time']),
    );
    $form['clear_images_cache'] = array(
        '#type' => 'button',
        '#name' => 'clear-cache-images',
        '#id' => 'clear-cache-images',
        '#attributes' => array('id' => 'clear-cache-images','class' => 'button-secondary js-wpcf-settings-clear-cache-images'),
        '#value' => __('Clear Cached Images', 'wpcf'),
        '#inline' => false,
        '#pattern' => '<div class="js-wpcf-settings-clear-cache-images-container"><ELEMENT>',
    );
    $form['clear_images_cache_outdated'] = array(
        '#id' => 'clear-cache-images-outdated',
        '#type' => 'button',
        '#name' => 'clear-cache-images-outdated',
        '#attributes' => array('id' => 'clear-cache-images-outdated','class' => 'button-secondary js-wpcf-settings-clear-cache-images-outdated'),
        '#value' => __('Clear Outdated Cached Images', 'wpcf'),
        '#inline' => false,
        '#pattern' => ' <ELEMENT></div>',
    );
	$section_content = wpcf_form_simple( $form );
		
	$sections['wpcf-images-settings'] = array(
		'slug'		=> 'wpcf-image-settings',
		'title'		=> __( 'Images', 'wpcf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'wp_ajax_wpcf_settings_clear_cache_images', 'wpcf_settings_clear_cache_images' );

function wpcf_settings_clear_cache_images() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields/image.php';
	$cache_dir = wpcf_fields_image_get_cache_directory( true );
	if ( is_wp_error( $cache_dir ) ) {
		$data = array(
			'type' => 'error',
			'message' => $cache_dir->get_error_message()
		);
		wp_send_json_error( $data );
	}
	$posted_settings = isset( $_POST['settings'] ) ? sanitize_text_field( $_POST['settings'] ) : '';
	if ( ! in_array( $posted_settings, array( 'all', 'outdated' ) ) ) {
		$data = array(
			'type' => 'error',
			'message' => __( 'Missing data', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	switch ( $posted_settings ) {
		case 'all':
			wpcf_fields_image_clear_cache( $cache_dir, 'all' );
			break;
		case 'outdated':
			wpcf_fields_image_clear_cache( $cache_dir );
			break;
	}
	wp_send_json_success();
}

add_action( 'wp_ajax_wpcf_settings_save_image_settings', 'wpcf_settings_save_image_settings' );

function wpcf_settings_save_image_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	$settings = wpcf_get_settings();
	$keys_to_check = array( 
		'add_resized_images_to_library'	=> 'esc_html', 
		'images_remote'					=> 'intval', 
		'images_remote_cache_time'		=> 'intval' 
	);
	$posted_settings = isset( $_POST['settings'] ) ? wp_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['wpcf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['wpcf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('wpcf_settings', $settings);
	wp_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'wpcf_admin_settings_for_help_box', 20 );

function wpcf_admin_settings_for_help_box( $sections ) {
	$settings = wpcf_get_settings();
	$section_content = '';
	$form['help-box'] = array(
		'#id' => 'help_box',
		'#name' => 'wpcf_help_box',
		'#type' => 'radios',
		'#options' => array(
			'all' => array(
				'#value' => 'all',
				'#title' => __("Show the help box on all custom post editing screens and on all Types create/edit pages", 'wpcf')
			),
			'by_types' => array(
				'#value' => 'by_types',
				'#title' => __("Show the help box only on post types that were created by Types and on all Types create/edit pages", 'wpcf')
			),
			'no' => array(
				'#value' => 'no',
				'#title' => __("Don't show the help box anywhere", 'wpcf')
			),
		),
		'#inline' => true,
		'#default_value' => $settings['help_box'],
		'#pattern' => '<ELEMENT><DESCRIPTION>',
	);
	$section_content = wpcf_form_simple( $form );
		
	$sections['wpcf-help-box-settings'] = array(
		'slug'		=> 'wpcf-help-box-settings',
		'title'		=> __( 'Help box', 'wpcf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'wp_ajax_wpcf_settings_save_help_box_settings', 'wpcf_settings_save_help_box_settings' );

function wpcf_settings_save_help_box_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	$settings = wpcf_get_settings();
	$keys_to_check = array( 
		'help_box'	=> 'esc_html'
	);
	$posted_settings = isset( $_POST['settings'] ) ? wp_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['wpcf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['wpcf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('wpcf_settings', $settings);
	wp_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'wpcf_admin_settings_for_custom_field_metabox', 30 );

function wpcf_admin_settings_for_custom_field_metabox( $sections ) {
	$settings = wpcf_get_settings();
	$section_content = '';
	$form['hide_standard_custom_fields_metabox'] = array(
		'#id' => 'hide_standard_custom_fields_metabox',
		'#name' => 'wpcf_hide_standard_custom_fields_metabox',
		'#type' => 'radios',
		'#options' => array(
			'all' => array(
				'#value' => 'show',
				'#title' => __('Show standard WordPress Custom Field Metabox', 'wpcf')
			),
			'by_types' => array(
				'#value' => 'hide',
				'#title' => __('Hide standard WordPress Custom Field Metabox', 'wpcf')
			),
		),
		'#inline' => true,
		'#default_value' => preg_match('/^(show|hide)$/', $settings['hide_standard_custom_fields_metabox'])? $settings['hide_standard_custom_fields_metabox']:'show',
		'#pattern' => '<ELEMENT><DESCRIPTION>',
	);
	$section_content = wpcf_form_simple( $form );
		
	$sections['wpcf-custom-field-metabox-settings'] = array(
		'slug'		=> 'wpcf-custom-field-metabox-settings',
		'title'		=> __( 'Custom field metabox', 'wpcf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'wp_ajax_wpcf_settings_save_custom_field_metabox_settings', 'wpcf_settings_save_custom_field_metabox_settings' );

function wpcf_settings_save_custom_field_metabox_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	$settings = wpcf_get_settings();
	$posted_settings = isset( $_POST['settings'] ) ? wp_parse_args( $_POST['settings'] ) : array();
	if ( preg_match('/^(show|hide)$/', $posted_settings['wpcf_hide_standard_custom_fields_metabox'] ) ) {
		$settings['hide_standard_custom_fields_metabox'] = $posted_settings['wpcf_hide_standard_custom_fields_metabox'];
	} else {
		$settings['hide_standard_custom_fields_metabox'] = 'show';
	}
	update_option('wpcf_settings', $settings);
	wp_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_custom-content_section',	'wpcf_admin_settings_for_unfiltered_html', 40 );

function wpcf_admin_settings_for_unfiltered_html( $sections ) {
	$settings = wpcf_get_settings();
	$section_content = '';
	$form['postmeta-unfiltered-html'] = array(
        '#id' => 'postmeta_unfiltered_html',
        '#name' => 'wpcf_postmeta_unfiltered_html',
        '#type' => 'radios',
        '#title' => __('Custom fields - unfiltered HTML', 'wpcf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __('Allow saving unfiltered HTML in Types custom fields for users with higher roles', 'wpcf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __('Disallow saving unfiltered HTML in Types custom fields for all users', 'wpcf'),
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['postmeta_unfiltered_html'],
        '#pattern' => '<TITLE><ELEMENT><DESCRIPTION>',
    );
    $form['usermeta-unfiltered-html'] = array(
        '#id' => 'usermeta_unfiltered_html',
        '#name' => 'wpcf_usermeta_unfiltered_html',
        '#type' => 'radios',
        '#title' => __('Usermeta fields - unfiltered HTML', 'wpcf'),
        '#options' => array(
            'on' => array(
                '#value' => 'on',
                '#title' => __("Allow saving unfiltered HTML in Types usermeta fields for users with higher roles", 'wpcf'),
            ),
            'off' => array(
                '#value' => 'off',
                '#title' => __("Disallow saving unfiltered HTML in Types usermeta fields for all users", 'wpcf')
            ),
        ),
        '#inline' => false,
        '#default_value' => $settings['usermeta_unfiltered_html'],
        '#pattern' => '<TITLE><ELEMENT><DESCRIPTION>',
    );
	$section_content = wpcf_form_simple( $form );
		
	$sections['wpcf-unfiltered-html-settings'] = array(
		'slug'		=> 'wpcf-unfiltered-html-settings',
		'title'		=> __( 'Saving unfiltered HTML for users with higher roles', 'wpcf' ),
		'content'	=> $section_content
	);
	return $sections;
}

add_action( 'wp_ajax_wpcf_settings_save_unfiltered_html_settings', 'wpcf_settings_save_unfiltered_html_settings' );

function wpcf_settings_save_unfiltered_html_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	$settings = wpcf_get_settings();
	$keys_to_check = array( 
		'postmeta_unfiltered_html',
		'usermeta_unfiltered_html'
	);
	$posted_settings = isset( $_POST['settings'] ) ? wp_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key ) {
		if ( isset( $posted_settings['wpcf_' . $key] ) ) {
			if ( preg_match( '/^(on|off)$/', $posted_settings['wpcf_' . $key] ) ) {
				$settings[$key] = $posted_settings['wpcf_' . $key];
			} else {
				$settings[$key] = 'off';
			}
		} else {
			$settings[$key] = 'off';
		}
	}
	update_option('wpcf_settings', $settings);
	wp_send_json_success();
}

add_filter( 'toolset_filter_toolset_register_settings_section', 'wpcf_register_settings_wpml_section', 70 );

function wpcf_register_settings_wpml_section( $sections ) {
	$wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );
	if ( $wpml_installed ) {
		$sections['wpml'] = array(
			'slug'	=> 'wpml',
			'title'	=> __( 'WPML integration', 'wpcf' )
		);
	}
	return $sections;
}

add_action( 'toolset_filter_toolset_register_settings_wpml_section', 'wpcf_wpml_translation_options' );

function wpcf_wpml_translation_options( $sections ) {
	$wpml_installed = apply_filters( 'wpml_setting', false, 'setup_complete' );
	if ( $wpml_installed ) {
		$settings = wpcf_get_settings();
		$form['register_translations_on_import'] = array(
            '#id' => 'register_translations_on_import',
            '#name' => 'wpcf_register_translations_on_import',
            '#type' => 'checkbox',
            '#label' => __("When importing, add texts to WPML's String Translation table", 'wpcf'),
            '#inline' => true,
            '#default_value' => !empty($settings['register_translations_on_import']),
            '#pattern' => '<p><ELEMENT><LABEL><DESCRIPTION></p>',
        );
		
		$section_content = wpcf_form_simple( $form );
			
		$sections['wpml-wpcf'] = array(
			'slug'		=> 'wpml-wpcf',
			'title'		=> __( 'Types and WPML integration', 'wpcf' ),
			'content'	=> $section_content
		);
	}
	return $sections;
}

add_action( 'wp_ajax_wpcf_settings_save_wpml_settings', 'wpcf_settings_save_wpml_settings' );

function wpcf_settings_save_wpml_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$data = array(
			'type' => 'capability',
			'message' => __( 'You do not have permissions for that.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	if ( 
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpcf_settings_nonce' ) 
	) {
		$data = array(
			'type' => 'nonce',
			'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpcf' )
		);
		wp_send_json_error( $data );
	}
	$settings = wpcf_get_settings();
	$keys_to_check = array( 
		'register_translations_on_import' => 'esc_html',
	);
	$posted_settings = isset( $_POST['settings'] ) ? wp_parse_args( $_POST['settings'] ) : array();
	foreach ( $keys_to_check as $key => $validation ) {
		if ( isset( $posted_settings['wpcf_' . $key] ) ) {
			$settings[$key] = call_user_func( $validation, $posted_settings['wpcf_' . $key] );
		} else {
			$settings[$key] = 0;
		}
	}
	update_option('wpcf_settings', $settings);
	wp_send_json_success();
}

/**
 * Adds typical header on admin pages.
 *
 * @param string $title
 * @param string $icon_id Custom icon
 * @return string
 */
function wpcf_add_admin_header($title, $add_new = false, $add_new_title = false)
{
    echo '<div class="wrap">';
    echo '<h1>', $title;
    if ( !$add_new_title ) {
        $add_new_title = __('Add New', 'wpcf');
    }
    if ( is_array($add_new) && isset($add_new['page']) ) {
        $add_button = false;
        /**
         * check user can?
         */
        switch($add_new['page']) {
	        case 'wpcf-edit-type':
		        $add_button = WPCF_Roles::user_can_create( 'custom-post-type' );
		        break;
	        case 'wpcf-edit-tax':
		        $add_button = WPCF_Roles::user_can_create( 'custom-taxonomy' );
		        break;
	        case 'wpcf-edit':
		        $add_button = WPCF_Roles::user_can_create( 'custom-field' );
		        break;
	        case 'wpcf-edit-usermeta':
		        $add_button = WPCF_Roles::user_can_create( 'user-meta-field' );
		        break;
	        case WPCF_Page_Edit_Termmeta::PAGE_NAME:
		        $add_button = WPCF_Roles::user_can_create( 'term-field' );
		        break;
        }
        if ( $add_button ) {
            printf(
                ' <a href="%s" class="add-new-h2">%s</a>',
                esc_url(add_query_arg( $add_new, admin_url('admin.php'))),
                $add_new_title
            );
        }
    }
    echo '</h2>';
    $current_page = sanitize_text_field( $_GET['page'] );
    do_action( 'wpcf_admin_header' );
    do_action( 'wpcf_admin_header_' . $current_page );
}

/**
 * Adds footer on admin pages.
 *
 * <b>Strongly recomended</b> if wpcf_add_admin_header() is called before.
 * Otherwise invalid HTML formatting will occur.
 */
function wpcf_add_admin_footer()
{
    $current_page = sanitize_text_field( $_GET['page'] );
	do_action( 'wpcf_admin_footer_' . $current_page );
    do_action( 'wpcf_admin_footer' );
    echo '</div>';
}

/**
 * Returns HTML formatted 'widefat' table.
 *
 * @param type $ID
 * @param type $header
 * @param type $rows
 * @param type $empty_message
 */
function wpcf_admin_widefat_table( $ID, $header, $rows = array(), $empty_message = 'No results' )
{
    if ( 'No results' == $empty_message ) {
        $empty_message = __('No results', 'wpcf');
    }
    $head = '';
    $footer = '';
    foreach ( $header as $key => $value ) {
        $head .= '<th id="wpcf-table-' . $key . '">' . $value . '</th>' . "\r\n";
        $footer .= '<th>' . $value . '</th>' . "\r\n";
    }
    echo '<table id="' . $ID . '" class="widefat" cellspacing="0">
            <thead>
                <tr>
                  ' . $head . '
                </tr>
            </thead>
            <tfoot>
                <tr>
                  ' . $footer . '
                </tr>
            </tfoot>
            <tbody>
              ';
    $row = '';
    if ( empty( $rows ) ) {
        echo '<tr><td colspan="' . count( $header ) . '">' . $empty_message
        . '</td></tr>';
    } else {
        $i = 0;
        foreach ( $rows as $row ) {
            $classes = array();
            if ( $i++%2 ) {
                $classes[] =  'alternate';
            }
            if ( isset($row['status']) && 'inactive' == $row['status'] ) {
                $classes[] = sprintf('status-%s', $row['status']);
            };
            printf('<tr class="%s">', implode(' ', $classes ));
            foreach ( $row as $column_name => $column_value ) {
                if ( preg_match( '/^(status|raw_name)$/', $column_name )) {
                    continue;
                }
                echo '<td class="wpcf-table-column-' . $column_name . '">';
                echo $column_value;
                echo '</td>' . "\r\n";
            }
            echo '</tr>' . "\r\n";
        }
    }
    echo '
            </tbody>
          </table>' . "\r\n";
}

/**
 * Admin tabs.
 *
 * @param type $tabs
 * @param type $page
 * @param type $default
 * @param type $current
 * @return string
 */
function wpcf_admin_tabs($tabs, $page, $default = '', $current = '')
{
    if ( empty( $current ) && isset( $_GET['tab'] ) ) {
        $current = sanitize_text_field( $_GET['tab'] );
    } else {
        $current = $default;
    }
    $output = '<h2 class="nav-tab-wrapper">';
    foreach ( $tabs as $tab => $name ) {
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        $output .= "<a class='nav-tab$class' href='?page=$page&tab=$tab'>$name</a>";
    }
    $output .= '</h2>';
    return $output;
}

/**
 * Saves open fieldsets.
 *
 * @param type $action
 * @param type $fieldset
 */
function wpcf_admin_form_fieldset_save_toggle($action, $fieldset)
{
    $data = get_user_meta( get_current_user_id(), 'wpcf-form-fieldsets-toggle',
            true );
    if ( $action == 'open' ) {
        $data[$fieldset] = 1;
    } elseif ( $action == 'close' ) {
        unset( $data[$fieldset] );
    }
    update_user_meta( get_current_user_id(), 'wpcf-form-fieldsets-toggle', $data );
}

/**
 * Check if fieldset is saved as open.
 *
 * @param type $fieldset
 */
function wpcf_admin_form_fieldset_is_collapsed($fieldset)
{
    $data = get_user_meta( get_current_user_id(), 'wpcf-form-fieldsets-toggle',
            true );
    if ( empty( $data ) ) {
        return true;
    }
    return array_key_exists( $fieldset, $data ) ? false : true;
}

/**
 * Adds help on admin pages.
 *
 * @param type $contextual_help
 * @param type $screen_id
 * @param type $screen
 * @return type
 */
function wpcf_admin_plugin_help($hook, $page)
{
    global $wp_version;
    $call = false;
    $contextual_help = '';
    $page = $page;
    if ( isset( $page ) && isset( $_GET['page'] ) && $_GET['page'] == $page ) {
        switch ( $page ) {
            case 'wpcf-cf':
                $call = 'custom_fields';
                break;

            case 'wpcf-cpt':
                $call = 'post_types_list';
                break;

            case 'wpcf-ctt':
                $call = 'custom_taxonomies_list';
                break;

            case 'wpcf-edit':
                $call = 'edit_group';
                break;

            case 'wpcf-edit-type':
                $call = 'edit_type';
                break;

            case 'wpcf-edit-tax':
                $call = 'edit_tax';
                break;

            case 'wpcf':
                $call = 'wpcf';
                break;

            case 'wpcf-um':
                $call = 'user_fields_list';
                break;

            case 'wpcf-edit-usermeta':
                $call = 'user_fields_edit';
                break;
        }
    }
    if ( $call ) {
        require_once WPCF_ABSPATH . '/help.php';
        // WP 3.3 changes
        if ( version_compare( $wp_version, '3.2.1', '>' ) ) {
            wpcf_admin_help_add_tabs($call, $hook, $contextual_help);
        } else {
            $contextual_help = wpcf_admin_help( $call, $contextual_help );
            add_contextual_help( $hook, $contextual_help );
        }
    }
}

/**
 * Promo texts
 *
 * @todo Move!
 */
function wpcf_admin_promotional_text()
{
    $promo_tabs = get_option( '_wpcf_promo_tabs', false );
    // random selection every one hour
    if ( $promo_tabs ) {
        $time = time();
        $time_check = intval( $promo_tabs['time'] ) + 60 * 60;
        if ( $time > $time_check ) {
            $selected = mt_rand( 0, 3 );
            $promo_tabs['selected'] = $selected;
            $promo_tabs['time'] = $time;
            update_option( '_wpcf_promo_tabs', $promo_tabs );
        } else {
            $selected = $promo_tabs['selected'];
        }
    } else {
        $promo_tabs = array();
        $selected = mt_rand( 0, 3 );
        $promo_tabs['selected'] = $selected;
        $promo_tabs['time'] = time();
        update_option( '_wpcf_promo_tabs', $promo_tabs );
    }
}

/**
 * Collapsible scripts.
 */
function wpcf_admin_load_collapsible()
{
    wp_enqueue_script( 'wpcf-collapsible',
            WPCF_RES_RELPATH . '/js/collapsible.js', array('jquery'),
            WPCF_VERSION );
    wp_enqueue_style( 'wpcf-collapsible',
            WPCF_RES_RELPATH . '/css/collapsible.css', array(), WPCF_VERSION );
    $option = get_option( 'wpcf_toggle', array() );
    if ( !empty( $option ) ) {
        $setting = 'new Array("' . implode( '", "', array_keys( $option ) ) . '")';
        wpcf_admin_add_js_settings( 'wpcf_collapsed', $setting );
    }
}

/**
 * Various delete/deactivate content actions.
 *
 * @param type $type
 * @param type $arg
 * @param type $action
 */
function wpcf_admin_deactivate_content($type, $arg, $action = 'delete')
{
    switch ( $type ) {
        case 'post_type':
            // Clean tax relations
            if ( $action == 'delete' ) {
                $custom = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
                foreach ( $custom as $post_type => $data ) {
                    if ( empty( $data['supports'] ) ) {
                        continue;
                    }
                    if ( array_key_exists( $arg, $data['supports'] ) ) {
                        unset( $custom[$post_type]['supports'][$arg] );
                        $custom[$post_type][TOOLSET_EDIT_LAST] = time();
                    }
                }
                update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom );
            }
            break;

        case 'taxonomy':
            // Clean post relations
            if ( $action == 'delete' ) {
	            $post_type_option = new Types_Utils_Post_Type_Option();
                $custom = $post_type_option->get_post_types();
                foreach ( $custom as $post_type => $data ) {
                    if ( empty( $data['taxonomies'] ) ) {
                        continue;
                    }
                    if ( array_key_exists( $arg, $data['taxonomies'] ) ) {
                        unset( $custom[$post_type]['taxonomies'][$arg] );
                        $custom[$post_type][TOOLSET_EDIT_LAST] = time();
                    }
                }
                update_option( WPCF_OPTION_NAME_CUSTOM_TYPES, $custom );
            }
            break;

        default:
            break;
    }
}

/**
 * Loads teasers.
 *
 * @param type $teasers
 */
function wpcf_admin_load_teasers($teasers)
{
    foreach ( $teasers as $teaser ) {
        $file = WPCF_ABSPATH . '/plus/' . $teaser;
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}

/**
 * Get temporary directory
 *
 * @return
 */

function wpcf_get_temporary_directory()
{
    $dir = sys_get_temp_dir();
    if ( !empty( $dir ) && is_dir( $dir ) && is_writable( $dir ) ) {
        return $dir;
    }
    $dir = wp_upload_dir();
    $dir = $dir['basedir'];
    return $dir;
}

/**
 * add types configuration to debug
 */

function wpcf_get_extra_debug_info($extra_debug)
{
    $extra_debug['types'] = wpcf_get_settings();
    return $extra_debug;
}

add_filter( 'icl_get_extra_debug_info', 'wpcf_get_extra_debug_info' );

/**
 * sort helper for tables
 */
function wpcf_usort_reorder($a,$b)
{
    $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'title'; //If no sort, default to title
    $order = (!empty($_REQUEST['order'])) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc'; //If no order, default to asc
    if ( ! in_array( $order, array( 'asc', 'desc' ) ) ) {
        $order = 'asc';
    }
    if ('title' == $orderby || !isset($a[$orderby])) {
        $orderby = 'slug';
    }
    /**
     * sort by slug if sort field is the same
     */
    if ( $a[$orderby] == $b[$orderby] ) {
        $orderby = 'slug';
    }
    $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
    return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
}

add_filter('set-screen-option', 'wpcf_table_set_option', 10, 3);
function wpcf_table_set_option($status, $option, $value)
{
    $allowed_options = array(
        'wpcf_cf_per_page',
	    'wpcf_uf_per_page',
	    WPCF_Page_Listing_Termmeta::SCREEN_OPTION_PER_PAGE_NAME,
	    Types_Page_Field_Control::SCREEN_OPTION_PER_PAGE_NAME
    );

	if( in_array( $option, $allowed_options ) ) {
		return $value;
	}

	return $status;
}

function wpcf_admin_screen( $post_type, $form_output = '')
{
?>
<div id="poststuff">
    <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
<?php echo $form_output; ?>
        <div id="postbox-container-1" class="postbox-container">
            <?php do_meta_boxes($post_type, 'side', null); ?>
        </div>
        <div id="postbox-container-2" class="postbox-container">
<?php
    do_meta_boxes($post_type, 'normal', null);
    do_meta_boxes($post_type, 'advanced', null);
?>
        </div>
    </div>
</div>
<?php
}
