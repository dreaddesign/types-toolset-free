<?php
/**
 *
 * Custom types form
 *
 *
 */

/**
 * Adds JS validation script.
 */
function wpcf_admin_types_form_js_validation()
{
    wpcf_form_render_js_validation();
}

/**
 * Submit function
 *
 * @global object $wpdb
 *
 */
function wpcf_admin_custom_types_form_submit($form)
{
    global $wpcf;

    if ( !isset( $_POST['ct'] ) ) {
        return false;
    }
    $data = $_POST['ct'];
    $update = false;

    // Sanitize data
    if ( isset( $data['wpcf-post-type'] ) ) {
        $update = true;
        $data['wpcf-post-type'] = sanitize_title( $data['wpcf-post-type'] );
    } else {
        $data['wpcf-post-type'] = null;
    }
    if ( isset( $data['slug'] ) ) {
        $data['slug'] = sanitize_title( $data['slug'] );
    } else {
        $data['slug'] = null;
    }
    if ( isset( $data['rewrite']['slug'] ) ) {
        $data['rewrite']['slug'] = remove_accents( $data['rewrite']['slug'] );
        $data['rewrite']['slug'] = strtolower( $data['rewrite']['slug'] );
        $data['rewrite']['slug'] = trim( $data['rewrite']['slug'] );
    }
    $data['_builtin'] = false;


    // Set post type name
    $post_type = null;
    if ( !empty( $data['slug'] ) ) {
        $post_type = $data['slug'];
    } elseif ( !empty( $data['wpcf-post-type'] ) ) {
        $post_type = $data['wpcf-post-type'];
    } elseif ( !empty( $data['labels']['singular_name'] ) ) {
        $post_type = sanitize_title( $data['labels']['singular_name'] );
    }

    if ( empty( $post_type ) ) {
        wpcf_admin_message( __( 'Please set post type name', 'wpcf' ), 'error' );
        return false;
    }

    $data['slug'] = $post_type;
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    $protected_data_check = array();

    if ( wpcf_is_builtin_post_types($data['slug']) ) {
        $data['_builtin'] = true;
    } else {
        // Check reserved name
        $reserved = wpcf_is_reserved_name( $post_type, 'post_type' );
        if ( is_wp_error( $reserved ) ) {
            wpcf_admin_message( $reserved->get_error_message(), 'error' );
            return false;
        }

        // Check overwriting
        if ( ( !array_key_exists( 'wpcf-post-type', $data ) || $data['wpcf-post-type'] != $post_type ) && array_key_exists( $post_type, $custom_types ) ) {
            wpcf_admin_message( __( 'Post Type already exists', 'wpcf' ), 'error' );
            return false;
        }

        /*
         * Since Types 1.2
         * We do not allow plural and singular names to be same.
         */
        if ( $wpcf->post_types->check_singular_plural_match( $data ) ) {
            wpcf_admin_message( $wpcf->post_types->message( 'warning_singular_plural_match' ), 'error' );
            return false;
        }

        // Check if renaming then rename all post entries and delete old type
        if ( !empty( $data['wpcf-post-type'] )
            && $data['wpcf-post-type'] != $post_type ) {
                global $wpdb;
                $wpdb->update( $wpdb->posts, array('post_type' => $post_type),
                    array('post_type' => $data['wpcf-post-type']), array('%s'),
                    array('%s')
                );

                /**
                 * update post meta "_wp_types_group_post_types"
                 */
                $sql = $wpdb->prepare(
                    sprintf(
                        'select meta_id, meta_value from %s where meta_key = %%s',
                        $wpdb->postmeta
                    ),
                    '_wp_types_group_post_types'
                );
                $all_meta = $wpdb->get_results($sql, OBJECT_K);
                $re = sprintf( '/,%s,/', $data['wpcf-post-type'] );
                foreach( $all_meta as $meta ) {
                    if ( !preg_match( $re, $meta->meta_value ) ) {
                        continue;
                    }
                    $wpdb->update(
                        $wpdb->postmeta,
                        array(
                            'meta_value' => preg_replace( $re, ','.$post_type.',', $meta->meta_value ),
                        ),
                        array(
                            'meta_id' => $meta->meta_id,
                        ),
                        array( '%s' ),
                        array( '%d' )
                    );
                }

                /**
                 * update _wpcf_belongs_{$data['wpcf-post-type']}_id
                 */
                $wpdb->update(
                    $wpdb->postmeta,
                    array(
                        'meta_key' => sprintf( '_wpcf_belongs_%s_id', $post_type ),
                    ),
                    array(
                        'meta_key' => sprintf( '_wpcf_belongs_%s_id', $data['wpcf-post-type'] ),
                    ),
                    array( '%s' ),
                    array( '%s' )
                );

                /**
                 * update options "wpv_options"
                 */
                $wpv_options = get_option( 'wpv_options', true );
                if ( is_array( $wpv_options ) ) {
                    $re = sprintf( '/(views_template_(archive_)?for_)%s/', $data['wpcf-post-type'] );
                    foreach( $wpv_options as $key => $value ) {
                        if ( !preg_match( $re, $key ) ) {
                            continue;
                        }
                        unset($wpv_options[$key]);
                        $key = preg_replace( $re, "$1".$post_type, $key );
                        $wpv_options[$key] = $value;
                    }
                    update_option( 'wpv_options', $wpv_options );
                }

                /**
                 * update option "wpcf-custom-taxonomies"
                 */
                $wpcf_custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, true );
                if ( is_array( $wpcf_custom_taxonomies ) ) {
                    $update_wpcf_custom_taxonomies = false;
                    foreach( $wpcf_custom_taxonomies as $key => $value ) {
                        if ( array_key_exists( 'supports', $value ) && array_key_exists( $data['wpcf-post-type'], $value['supports'] ) ) {
                            unset( $wpcf_custom_taxonomies[$key]['supports'][$data['wpcf-post-type']] );
                            $update_wpcf_custom_taxonomies = true;
                        }
                    }
                    if ( $update_wpcf_custom_taxonomies ) {
                        update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $wpcf_custom_taxonomies );
                    }
                }

                // Sync action
                do_action( 'wpcf_post_type_renamed', $post_type, $data['wpcf-post-type'] );

                // Set protected data
                $protected_data_check = $custom_types[$data['wpcf-post-type']];
                // Delete old type
                unset( $custom_types[$data['wpcf-post-type']] );
                $data['wpcf-post-type'] = $post_type;
            } else {
                // Set protected data
                $protected_data_check = !empty( $custom_types[$post_type] ) ? $custom_types[$post_type] : array();
            }

        // Check if active
        if ( isset( $custom_types[$post_type]['disabled'] ) ) {
            $data['disabled'] = $custom_types[$post_type]['disabled'];
        }
    }

    // Sync taxes with custom taxes
    if ( !empty( $data['taxonomies'] ) ) {
        $taxes = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
        foreach ( $taxes as $id => $tax ) {
            if ( array_key_exists( $id, $data['taxonomies'] ) ) {
                $taxes[$id]['supports'][$data['slug']] = 1;
            } else {
                unset( $taxes[$id]['supports'][$data['slug']] );
            }
        }
        update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxes );
    }

    // Preserve protected data
    foreach ( $protected_data_check as $key => $value ) {
        if ( strpos( $key, '_' ) !== 0 ) {
            unset( $protected_data_check[$key] );
        }
    }

    /**
     * set last edit time
     */
    $data[TOOLSET_EDIT_LAST] = time();

    /**
     * set last edit author
     */

    $data[WPCF_AUTHOR] = get_current_user_id();

    /**
     * add builid in
     */
    if ( $data['_builtin'] && !isset( $protected_data_check[$data['slug']])) {
        $protected_data_check[$data['slug']] = array();
    }

    // Merging protected data
    $custom_types[$post_type] = array_merge( $protected_data_check, $data );

    update_option( WPCF_OPTION_NAME_CUSTOM_TYPES, $custom_types );

    // WPML register strings
    if ( !$data['_builtin'] ) {
        wpcf_custom_types_register_translation( $post_type, $data );
    }

    /**
     * success message
     */
    wpcf_admin_message_store(
        apply_filters(
            'types_message_custom_post_type_saved',
            __( 'Post Type saved', 'wpcf' ),
            $data,
            $update
        ),
        'custom'
    );

    if ( !$data['_builtin'] ) {
        // Flush rewrite rules
        flush_rewrite_rules();

        do_action( 'wpcf_custom_types_save', $data );
    }

    // Redirect
    wp_safe_redirect(
        esc_url_raw(
            add_query_arg(
                array(
                    'page' => 'wpcf-edit-type',
                    'wpcf-post-type' => $post_type,
                    'wpcf-rewrite' => 1,
                    'wpcf-message' => 'view',
                ),
                admin_url( 'admin.php' )
            )
        )
    );
    die();
}

