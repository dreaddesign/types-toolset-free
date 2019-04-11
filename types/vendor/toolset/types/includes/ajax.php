<?php

/**
 * With Toolset Dashboard we need the ability to redirect back to the Dashboard if the user came from the Dashboard
 * for this we introduced the wpcf_ref parameter
 *
 * @return array
 * @since 2.1
 */
function wpcf_ajax_group_delete_redirect() {

    // on group edit page
    if( isset( $_GET['wpcf_ref'] ) ) {

        $group_type = get_post_type( $_GET['group_id'] );

        if( $_GET['wpcf_ref'] == 'dashboard' ) {
            $redirect = admin_url( 'admin.php?page=toolset-dashboard' );
        } else {
            switch( $group_type ) {
                case 'wp-types-group':
                    $redirect = admin_url( 'admin.php?page=wpcf-cf' );
                    break;
                case 'wp-types-term-group':
                    $redirect = admin_url( 'admin.php?page=wpcf-termmeta-listing' );
                    break;
                case 'wp-types-user-group':
                    $redirect = admin_url( 'admin.php?page=wpcf-um' );
                    break;
            }
        }

        return array(
                'output'                   => '',
                'execute'                  => 'redirect',
                'wpcf_redirect'            => $redirect,
                'wpcf_nonce_ajax_callback' => wp_create_nonce( 'execute' ),
            );
    // on listing page
    } else {
        return array(
            'output' => '',
            'execute' => 'reload',
            'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
        );
    }
}

/**
 * All AJAX calls go here.
 *
 * @global object $wpdb
 * @param bool $fallthrough If this is true, we won't die() at the end of this method.
 *
 * @return bool Fallthrough value
 */
function wpcf_ajax( $fallthrough )
{
    /**
     * check nounce
     */
    if ( !(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], $_REQUEST['wpcf_action']))) {
        if( $fallthrough ) {
        	return true;
		} else {
			die();
		}
    }
    require_once WPCF_INC_ABSPATH.'/classes/class.wpcf.roles.php';

	// We need to require this file manually because this runs before the autoloader is registered.
	require_once TYPES_ABSPATH . '/application/controllers/utils/post_type_option.php';
	$post_type_option = new Types_Utils_Post_Type_Option();

    /**
     * check permissions
     */
    switch ($_REQUEST['wpcf_action']) {
    case 'deactivate_post_type':
    case 'activate_post_type':
    case 'delete_post_type':
    case 'duplicate_post_type':
        $post_type = wpcf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        if ( !WPCF_Roles::user_can_edit_custom_post_by_slug($post_type)) {
            wpcf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'taxonomy_duplicate':
    case 'deactivate_taxonomy':
    case 'activate_taxonomy':
    case 'delete_taxonomy':
        $custom_taxonomy = wpcf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        if ( !WPCF_Roles::user_can_edit_custom_taxonomy_by_slug($custom_taxonomy)) {
            wpcf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_group':
    case 'activate_group':
    case 'delete_group':
        if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
            wpcf_ajax_helper_print_error_and_die();
        }
        if ( !WPCF_Roles::user_can_edit_custom_field_group_by_id($_GET['group_id'])) {
            wpcf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_user_group':
    case 'activate_user_group':
    case 'delete_usermeta_group':
        if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
            wpcf_ajax_helper_print_error_and_die();
        }
        if ( !WPCF_Roles::user_can_edit_usermeta_field_group_by_id($_GET['group_id'])) {
            wpcf_ajax_helper_verification_failed_and_die();
        }
        break;
    case 'deactivate_term_group':
    case 'activate_term_group':
    case 'delete_term_group':
	    if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
		    wpcf_ajax_helper_print_error_and_die();
	    }
	    if ( !WPCF_Roles::user_can_edit_term_field_group_by_id($_GET['group_id'])) {
		    wpcf_ajax_helper_verification_failed_and_die();
	    }
	    break;
    case 'usermeta_delete':
    case 'delete_usermeta':
    case 'remove_from_history2':
    case 'usermeta_insert_existing':
    case 'fields_insert':
    case 'fields_insert_existing':
    case 'remove_field_from_group':
    case 'add_radio_option':
    case 'add_select_option':
    case 'add_checkboxes_option':
    case 'group_form_collapsed':
    case 'form_fieldset_toggle':
    case 'fields_delete':
    case 'delete_field':
    case 'remove_from_history':
    case 'add_condition':
    case 'pt_edit_fields':
    case 'toggle':
    case 'cb_save_empty_migrate':
        if ( !current_user_can('manage_options') ) {
            wpcf_ajax_helper_verification_failed_and_die();
        }
        /**
         * do not check actions from other places
         */
        break;
    default:
        return;
    }

    /**
     * do actions
     */
    switch ($_REQUEST['wpcf_action']) {
        /* User meta actions*/

    case 'usermeta_delete':
    case 'delete_usermeta':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['field_id'])) {
            $field_id = sanitize_text_field( $_GET['field_id'] );
            wpcf_admin_fields_delete_field($field_id,TYPES_USER_META_FIELD_GROUP_CPT_NAME,'wpcf-usermeta');
        }
        if (isset($_GET['field'])) {
            $field = sanitize_text_field( $_GET['field'] );
            wpcf_admin_fields_delete_field($field,TYPES_USER_META_FIELD_GROUP_CPT_NAME,'wpcf-usermeta');
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'remove_from_history2':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        $fields = wpcf_admin_fields_get_fields( true, true,false,'wpcf-usermeta');
        if (isset($_GET['field_id']) && isset($fields[$_GET['field_id']])) {
            $fields[$_GET['field_id']]['data']['removed_from_history'] = 1;
            wpcf_admin_fields_save_fields($fields, true, 'wpcf-usermeta');
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'deactivate_user_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/usermeta.php';
        $success = wpcf_admin_fields_deactivate_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group deactivated', 'wpcf'),
                    'execute' => 'reload',
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
        } else {
            wpcf_ajax_helper_print_error_and_die();
            die;
        }
        break;

    case 'activate_user_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/usermeta.php';
        $success = wpcf_admin_fields_activate_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group activated', 'wpcf'),
                    'execute' => 'reload',
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
        } else {
            wpcf_ajax_helper_print_error_and_die();
            die;
        }
        break;

    case 'delete_usermeta_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/usermeta.php';

        $redirect = wpcf_ajax_group_delete_redirect();

        wpcf_admin_fields_delete_group(intval($_GET['group_id']), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
        echo json_encode( $redirect );

        break;

    case 'usermeta_insert_existing':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/fields-form.php';
        require_once WPCF_INC_ABSPATH . '/usermeta-form.php';
        wpcf_usermeta_insert_existing_ajax();
        wpcf_form_render_js_validation();
        break;
        /* End Usertmeta actions*/

    case 'fields_insert':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/fields-form.php';
        wpcf_fields_insert_ajax();
        wpcf_form_render_js_validation();
        break;

    case 'fields_insert_existing':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_INC_ABSPATH . '/fields-form.php';
        wpcf_fields_insert_existing_ajax();
        wpcf_form_render_js_validation();
        break;

    case 'remove_field_from_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['group_id']) && isset($_GET['field_id'])) {
            wpcf_admin_fields_remove_field_from_group(intval($_GET['group_id']),
                sanitize_text_field($_GET['field_id']));
        }
        break;

    case 'deactivate_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        $success = wpcf_admin_fields_deactivate_group(intval($_GET['group_id']));
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group deactivated', 'wpcf'),
                    'execute' => 'reload',
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
        } else {
            wpcf_ajax_helper_print_error_and_die();
        }
        break;

    case 'activate_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        $success = wpcf_admin_fields_activate_group(intval($_GET['group_id']));
        if ($success) {
            echo json_encode(
                array(
                    'output' => __('Group activated', 'wpcf'),
                    'execute' => 'reload',
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
        } else {
            wpcf_ajax_helper_print_error_and_die();
        }
        break;

    case 'delete_group':
        require_once WPCF_INC_ABSPATH . '/fields.php';

        $redirect = wpcf_ajax_group_delete_redirect();
        wpcf_admin_fields_delete_group(intval($_GET['group_id']));

        echo json_encode( $redirect );
        break;

	    case 'deactivate_term_group':
		    require_once WPCF_INC_ABSPATH . '/fields.php';
		    $success = wpcf_admin_fields_deactivate_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);
		    if ($success) {
			    echo json_encode(
				    array(
					    'output' => __('Group deactivated', 'wpcf'),
					    'execute' => 'reload',
					    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
				    )
			    );
		    } else {
			    wpcf_ajax_helper_print_error_and_die();
		    }
		    break;

	    case 'activate_term_group':
		    require_once WPCF_INC_ABSPATH . '/fields.php';
		    $success = wpcf_admin_fields_activate_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);
		    if ($success) {
			    echo json_encode(
				    array(
					    'output' => __('Group activated', 'wpcf'),
					    'execute' => 'reload',
					    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
				    )
			    );
		    } else {
			    wpcf_ajax_helper_print_error_and_die();
		    }
		    break;

	    case 'delete_term_group':
		    require_once WPCF_INC_ABSPATH . '/fields.php';

            $redirect = wpcf_ajax_group_delete_redirect();
		    wpcf_admin_fields_delete_group(intval($_GET['group_id']), Types_Field_Group_Term::POST_TYPE);

            echo json_encode( $redirect );

		    break;

	    case 'deactivate_post_type':
        $post_type = wpcf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_types = $post_type_option->get_post_types();
        $custom_types[$post_type]['disabled'] = 1;
        $custom_types[$post_type][TOOLSET_EDIT_LAST] = time();
        update_option(WPCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
        echo json_encode(
            array(
                'output' => __('Post Type deactivated', 'wpcf'),
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'activate_post_type':
        $post_type = wpcf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_types = $post_type_option->get_post_types();
        unset($custom_types[$post_type]['disabled']);
        $custom_types[$post_type][TOOLSET_EDIT_LAST] = time();
        update_option(WPCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
        echo json_encode(
            array (
                'output' => __('Post Type activated', 'wpcf'),
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'delete_post_type':
        $post_type = wpcf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $post_types = $post_type_option->get_post_types();

        /**
         * Delete relation between custom posts types
         *
         * Filter allow to delete all custom fields used to make
         * a relation between posts.
         *
         * @since 1.6.4
         *
         * @param bool   $delete True or false flag to delete relationships.
         * @param string $var Currently deleted post type.
         */
        if ( apply_filters('wpcf_delete_relation_meta', false, $post_type) ) {
            global $wpdb;
            $wpdb->delete(
                $wpdb->postmeta,
                array( 'meta_key' => sprintf( '_wpcf_belongs_%s_id', $post_type ) ),
                array( '%s' )
            );
        }

        unset($post_types[$post_type]);
        /**
         * remove post relation
         */
        foreach ( array_keys($post_types) as $post_type ) {
            if ( array_key_exists( 'post_relationship', $post_types[$post_type] ) ) {
                /**
                 * remove "has" relation
                 */
                if (
                    array_key_exists( 'has', $post_types[$post_type]['post_relationship'] )
                    && array_key_exists( $post_type, $post_types[$post_type]['post_relationship']['has'] )
                ) {
                    unset($post_types[$post_type]['post_relationship']['has'][$post_type]);
                    $post_types[$post_type][TOOLSET_EDIT_LAST] = time();
                }
                /**
                 * remove "belongs" relation
                 */
                if (
                    array_key_exists( 'belongs', $post_types[$post_type]['post_relationship'] )
                    && array_key_exists( $post_type, $post_types[$post_type]['post_relationship']['belongs'] )
                ) {
                    unset($post_types[$post_type]['post_relationship']['belongs'][$post_type]);
                    $post_types[$post_type][TOOLSET_EDIT_LAST] = time();
                }
            }
        }
        update_option(WPCF_OPTION_NAME_CUSTOM_TYPES, $post_types);
        wpcf_admin_deactivate_content('post_type', $post_type);
        echo json_encode(
            array(
                'output' => '',
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'duplicate_post_type':
        $post_type = wpcf_ajax_helper_get_post_type();
        if ( empty($post_type) ) {
            wpcf_ajax_helper_print_error_and_die();
        }

        $post_types = $post_type_option->get_post_types();

        $i = 0;
        $key = false;
        do {
            $key = sprintf($post_type.'-%d',++$i);
        } while( isset($post_types[$key]) );
        if ( $key ) {
            /**
             * duplicate post type
             */
            $post_types[$key] = $post_types[$post_type];
            /**
             * update some options
             */
            $post_types[$key]['labels']['name'] .= sprintf(' (%d)', $i);
            $post_types[$key]['labels']['singular_name'] .= sprintf(' (%d)', $i);
            $post_types[$key]['slug'] = $key;
            $post_types[$key]['__types_id'] = $key;

            /**
             * update post types
             */
            update_option(WPCF_OPTION_NAME_CUSTOM_TYPES, $post_types);

            /**
             * update custom taxonomies too
             */
            $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
            foreach( $custom_taxonomies as $taxonomy_key => $taxonomy_data) {
                if (
                    isset( $taxonomy_data['supports']) 
                    && isset( $taxonomy_data['supports'][$post_type])
                ) {
                    $custom_taxonomies[$taxonomy_key]['supports'][$key] = 1;
                }
            }
            update_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);

            echo json_encode(array(
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            ));
        }
        break;

    case 'taxonomy_duplicate':
        $custom_taxonomy = wpcf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        $i = 0;
        $key = false;
        do {
            $key = sprintf($custom_taxonomy.'-%d',++$i);
        } while( isset($custom_taxonomies[$key]) );
        if ( $key ) {
            /**
             * duplicate custom taxonomies
             */
            $custom_taxonomies[$key] = $custom_taxonomies[$custom_taxonomy];

            /**
             * update some options
             */
            $custom_taxonomies[$key]['labels']['name'] .= sprintf(' (%d)', $i);
            $custom_taxonomies[$key]['labels']['singular_name'] .= sprintf(' (%d)', $i);
            $custom_taxonomies[$key]['slug'] = $key;
            $custom_taxonomies[$key]['id'] = $key;
            $custom_taxonomies[$key]['__types_id'] = $key;

            /**
             * update custom taxonomies
             */
            update_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);

            /**
             * update post types
             */
            if (
                isset( $custom_taxonomies[$key]['supports'] )
                && is_array($custom_taxonomies[$key]['supports'])
                && !empty($custom_taxonomies[$key]['supports'])
            ) {
                $custom_types = $post_type_option->get_post_types();
                foreach( array_keys($custom_taxonomies[$key]['supports']) as $custom_type ) {
                    /**
                     * avoid to create fake CPT from old data
                     */
                    if ( !isset($custom_types[$custom_type])) {
                        continue;
                    }
                    if ( !isset($custom_types[$custom_type]['taxonomies']) ) {
                        $custom_types[$custom_type]['taxonomies'] = array();
                    }
                    $custom_types[$custom_type]['taxonomies'][$key] = 1;
                }

                /**
                 * update post types
                 */
                update_option(WPCF_OPTION_NAME_CUSTOM_TYPES, $custom_types);
            }
            echo json_encode(array(
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            ));
        }
        break;

    case 'deactivate_taxonomy':
        $custom_taxonomy = wpcf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if (!isset($custom_taxonomies[$custom_taxonomy]) || !isset( $custom_taxonomies[$custom_taxonomy]['supports'] ) ) {
            $taxonomy = get_taxonomy($custom_taxonomy);
            $supports = array();
            if (is_object($taxonomy )) {
                if( ! empty( $taxonomy->object_type ) ) {
                    foreach( $taxonomy->object_type as $support_post ) {
                        $supports[$support_post] = 1;
                    }
                }
                $custom_taxonomies[$custom_taxonomy] = array(
                    'slug' => $taxonomy->name,
                    'labels' => array(
                        'singular_name' => $taxonomy->labels->singular_name,
                        'name' => $taxonomy->labels->name,
                    ),
                    'supports' => $supports,
                    'type' => '_builtin',
                    '_builtin' => true,
                );
            } else {
                wpcf_ajax_helper_print_error_and_die();
            }
        }
        if ( wpcf_is_builtin_taxonomy($custom_taxonomy) ) {
            $custom_taxonomies[$custom_taxonomy]['type'] = '_builtin';
            $custom_taxonomies[$custom_taxonomy]['_builtin'] = true;
        }
        $custom_taxonomies[$custom_taxonomy]['disabled'] = 1;
        $custom_taxonomies[$custom_taxonomy][TOOLSET_EDIT_LAST] = time();
        update_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        echo json_encode(
            array(
                'output' => __('Taxonomy deactivated', 'wpcf'),
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'activate_taxonomy':
        $custom_taxonomy = wpcf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if (!isset($custom_taxonomies[$custom_taxonomy])) {
            $taxonomy = get_taxonomy($custom_taxonomy);
            if (is_object($taxonomy )) {
                $custom_taxonomies[$custom_taxonomy] = array(
                    'slug' => $taxonomy->name,
                    'labels' => array(
                        'singular_name' => $taxonomy->labels->singular_name,
                    ),
                    'type' => '_builtin',
                    '_builtin' => true,
                );
            } else {
                wpcf_ajax_helper_print_error_and_die();
            }
        }
        if ( wpcf_is_builtin_taxonomy($custom_taxonomy) ) {
            $custom_taxonomies[$custom_taxonomy]['type'] = '_builtin';
            $custom_taxonomies[$custom_taxonomy]['_builtin'] = true;
        }
        $custom_taxonomies[$custom_taxonomy]['disabled'] = 0;
        $custom_taxonomies[$custom_taxonomy][TOOLSET_EDIT_LAST] = time();
        update_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        echo json_encode(
            array (
                'output' => __('Taxonomy activated', 'wpcf'),
                'execute' => 'reload',
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'delete_taxonomy':
        $custom_taxonomy = wpcf_ajax_helper_get_taxonomy();
        if ( empty($custom_taxonomy) ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        unset($custom_taxonomies[$custom_taxonomy]);
        update_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        wpcf_admin_deactivate_content('taxonomy', $custom_taxonomy);

        // on group edit page
        if( isset( $_GET['wpcf_ref'] ) ) {

            $redirect = $_GET['wpcf_ref'] == 'dashboard'
                ? admin_url( 'admin.php?page=toolset-dashboard' )
                : admin_url( 'admin.php?page=wpcf-ctt' );

            echo json_encode(
                array(
                    'output' => '',
                    'execute' => 'redirect',
                    'wpcf_redirect' => $redirect,
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
            // on listing page
        } else {
            echo json_encode(
                array(
                    'output' => '',
                    'execute' => 'reload',
                    'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
                )
            );
        }


        break;

    case 'add_radio_option':
        /**
         * check required data
         */
        if (
            !isset($_GET['parent_name'] )
            || !isset($_GET['wpcf_ajax_update_add'] )
        ) {
            wpcf_ajax_helper_print_error_and_die();
        }
        require_once WPCF_INC_ABSPATH . '/fields/radio.php';
        $value = esc_html(urldecode($_GET['parent_name']));
        $element = wpcf_fields_radio_get_option($value);
        $id = array_shift($element);
        $element_txt = wpcf_fields_radio_get_option_alt_text($id, $value);
        echo json_encode(
            array(
                'output' => wpcf_form_simple($element),
                'execute' => 'append',
                'append_target' => '#wpcf-form-groups-radio-ajax-response-'. esc_html(urldecode($_GET['wpcf_ajax_update_add'])),
                'append_value' => trim(preg_replace('/[\r\n]+/', '', wpcf_form_simple($element_txt))),
                'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
            )
        );
        break;

    case 'add_select_option':
        /**
         * check required data
         */
        if ( !isset($_GET['parent_name'] )) {
            wpcf_ajax_helper_print_error_and_die();
        }
        require_once WPCF_INC_ABSPATH . '/fields/select.php';
        $element = wpcf_fields_select_get_option(esc_html(urldecode($_GET['parent_name'])));
        echo json_encode(array(
            'output' => wpcf_form_simple($element)
        ));
        break;

    case 'add_checkboxes_option':
        /**
         * check required data
         */
        if ( !isset($_GET['parent_name'] )) {
            wpcf_ajax_helper_print_error_and_die();
        }
        require_once WPCF_INC_ABSPATH . '/fields/checkboxes.php';
        $value = esc_html(urldecode($_GET['parent_name']));
        $element = wpcf_fields_checkboxes_get_option($value);
        $id = array_shift($element);
        $element_txt = wpcf_fields_checkboxes_get_option_alt_text($id, $value);
        echo json_encode(array(
            'output' => wpcf_form_simple($element),
            'wpcf_nonce_ajax_callback' => wp_create_nonce('execute'),
        ));
        break;

    case 'group_form_collapsed':
        require_once WPCF_INC_ABSPATH . '/fields-form.php';
        $group_id = sanitize_text_field($_GET['group_id']);
        $action = sanitize_text_field($_GET['toggle']);
        $fieldset = sanitize_text_field($_GET['id']);
        wpcf_admin_fields_form_save_open_fieldset($action, $fieldset,
            $group_id);
        break;

    case 'form_fieldset_toggle':
        $action = sanitize_text_field($_GET['toggle']);
        $fieldset = sanitize_text_field($_GET['id']);
        wpcf_admin_form_fieldset_save_toggle($action, $fieldset);
        break;

    case 'fields_delete':
    case 'delete_field':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        if (isset($_GET['field_id'])) {
            wpcf_admin_fields_delete_field(sanitize_text_field($_GET['field_id']));
        }
        if (isset($_GET['field'])) {
            wpcf_admin_fields_delete_field(sanitize_text_field($_GET['field']));
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'remove_from_history':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        $fields = wpcf_admin_fields_get_fields();
        if (isset($_GET['field_id']) && isset($fields[$_GET['field_id']])) {
            $fields[$_GET['field_id']]['data']['removed_from_history'] = 1;
            wpcf_admin_fields_save_fields($fields, true);
        }
        echo json_encode(array(
            'output' => ''
        ));
        break;

    case 'add_condition':
        require_once WPCF_INC_ABSPATH . '/fields.php';
        require_once WPCF_ABSPATH . '/includes/conditional-display.php';
        if (!empty($_GET['field']) || !empty($_GET['group'])) {
            $data = array();
            if (isset($_GET['group'])) {
                $output = wpcf_form_simple(wpcf_cd_admin_form_single_filter(array(),
                    array(), null, true));
                echo json_encode(array(
                    'output' => $output,
                ));
            } else {
                $data['id'] = str_replace('_conditional_display', '',
                    sanitize_text_field($_GET['field']));
                $output = wpcf_form_simple(wpcf_cd_admin_form_single_filter($data,
                    array(), null, false));
                if (!empty($data['id'])) {
                    echo json_encode(array(
                        'output' => $output,
                    ));
                } else {
                    wpcf_ajax_helper_print_error_and_die();
                }
            }
        } else {
            wpcf_ajax_helper_print_error_and_die();
        }
        break;

    case 'pt_edit_fields':
        if (!empty($_GET['parent']) && !empty($_GET['child'])) {
            require_once WPCF_INC_ABSPATH . '/fields.php';
            require_once WPCF_INC_ABSPATH . '/post-relationship.php';
            wpcf_pr_admin_edit_fields(sanitize_text_field($_GET['parent']), sanitize_text_field($_GET['child']));
        }
        break;

    case 'toggle':
        $option = get_option('wpcf_toggle', array());
        $hidden = isset($_GET['hidden']) ? (bool) $_GET['hidden'] : 1;
        $div = esc_html(strval($_GET['div']));
        if (!$hidden) {
            unset($option[$div]);
        } else {
            $option[$div] = 1;
        }
        update_option('wpcf_toggle', $option);
        break;

    case 'cb_save_empty_migrate':
        $output = sprintf(
            '<span style="color:red;">%s</div>',
            __('Migration process is not yet finished - please save group first, then change settings of this field.', 'wpcf')
        );
        if (isset($_GET['field']) && isset($_GET['subaction'])) {
            require_once WPCF_INC_ABSPATH . '/fields.php';
            $option = $_GET['meta_type'] == 'usermeta' ? 'wpcf-usermeta' : 'wpcf-fields';
            $meta_type = sanitize_text_field($_GET['meta_type']);
            $field = wpcf_admin_fields_get_field(sanitize_text_field($_GET['field']), false, false,
                false, $option);

            $_txt_updates = $meta_type == 'usermeta' ? __( '%d users require update', 'wpcf' ) : __( '%d posts require update', 'wpcf' );
            $_txt_no_updates = $meta_type == 'usermeta' ? __('No users require update', 'wpcf') : __('No posts require update', 'wpcf');
            $_txt_updated = $meta_type == 'usermeta' ? __('Users updated', 'wpcf') : __('Posts updated', 'wpcf');

            if (!empty($field)) {
                if ($_GET['subaction'] == 'save_check'
                    || $_GET['subaction'] == 'do_not_save_check') {
                        if ($field['type'] == 'checkbox') {
                            $posts = wpcf_admin_fields_checkbox_migrate_empty_check($field,
                                $_GET['subaction']);
                        } else if ($field['type'] == 'checkboxes') {
                            $posts = wpcf_admin_fields_checkboxes_migrate_empty_check($field,
                                $_GET['subaction']);
                        }
                        if (!empty($posts)) {
                            $output = '<div class="message updated"><p>'
                                . sprintf($_txt_updates, count($posts)) . '&nbsp;'
                                . '<a href="javascript:void(0);" class="button-primary" onclick="'
                                . 'wpcfCbSaveEmptyMigrate(jQuery(this).parent().parent().parent(), \''
                                . sanitize_text_field($_GET['field']) . '\', '
                                . count($posts) . ', \''
                                . wp_create_nonce('cb_save_empty_migrate') . '\', \'';
                            $output .= $_GET['subaction'] == 'save_check' ? 'save' : 'do_not_save';
                            $output .= '\', \'' . $meta_type . '\');'
                                . '">'
                                . __('Update', 'wpcf') . '</a>' . '</p></div>';
                        } else {
                            $output = '<div class="message updated"><p><em>'
                                . $_txt_no_updates . '</em></p></div>';
                        }
                    } else if ($_GET['subaction'] == 'save'
                        || $_GET['subaction'] == 'do_not_save') {
                            if ($field['type'] == 'checkbox') {
                                $posts = wpcf_admin_fields_checkbox_migrate_empty($field,
                                    $_GET['subaction']);
                            } else if ($field['type'] == 'checkboxes') {
                                $posts = wpcf_admin_fields_checkboxes_migrate_empty($field,
                                    $_GET['subaction']);
                            }
                            if (isset($posts['offset'])) {
                                if (!isset($_GET['total'])) {
                                    $output = '<span style="color:red;">'.__('Error occured', 'wpcf').'</div>';
                                } else {
                                    $output = '<script type="text/javascript">wpcfCbMigrateStep('
                                        . intval($_GET['total']) . ','
                                        . $posts['offset'] . ','
                                        . '\'' . sanitize_text_field($_GET['field']) . '\','
                                        . '\'' . wp_create_nonce('cb_save_empty_migrate')
                                        . '\', \'' . $meta_type . '\');</script>'
                                        . number_format($posts['offset'])
                                        . '/' . number_format(intval($_GET['total']))
                                        . '<div class="wpcf-ajax-loading-small"></div>';
                                }
                            } else {
                                $output = sprintf(
                                    '<div class="message updated"><p>%s</p></div>',
                                    $_txt_updated
                                );
                            }
                        }
            }
        }
        echo json_encode(array(
            'output' => $output,
        ));
        break;

    default:
    	$fallthrough = true;
        break;
    }

    // We need this to be able to catch some (partially refactored) AJAX callback handlers at the proper time
	// (in Types_Ajax).
    if( ! $fallthrough ) {
		die();
	}

	return $fallthrough;
}

function wpcf_ajax_helper_get_taxonomy()
{
    if (!isset($_GET['wpcf-tax']) || empty($_GET['wpcf-tax'])) {
        return false;
    }
    $taxonomy = $_GET['wpcf-tax'];
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies.php';
    $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
    if (
        isset($custom_taxonomies[$taxonomy])
        && isset($custom_taxonomies[$taxonomy]['slug'])
    ) {
        return $custom_taxonomies[$taxonomy]['slug'];
    }
    $taxonomy = get_taxonomy($taxonomy);
    if ( is_object($taxonomy) ) {
        return $taxonomy->name;
    }
    return false;
}

function wpcf_ajax_helper_get_post_type()
{
    if (!isset($_REQUEST['wpcf-post-type']) || empty($_REQUEST['wpcf-post-type'])) {
        return false;
    }
    require_once WPCF_INC_ABSPATH . '/custom-types.php';
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    if (
        isset($custom_types[$_REQUEST['wpcf-post-type']])
        && isset($custom_types[$_REQUEST['wpcf-post-type']]['slug'])
    ) {
        return $custom_types[$_REQUEST['wpcf-post-type']]['slug'];
    }
    return false;
}

function wpcf_ajax_helper_print_error_and_die()
{
    echo json_encode(array(
        'output' => __('Missing required data.', 'wpcf'),
    ));
    die;
}

function wpcf_ajax_helper_verification_failed_and_die()
{
    echo json_encode(array(
        'output' => __('Verification failed.', 'wpcf'),
    ));
    die;
}
