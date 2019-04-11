<?php

/**
 * All AJAX calls go here.
 *
 * @refactoring ! No, they don't!!!
 *
 * @todo auth
 */
function wpcf_ajax_embedded() {

	// If this is true, we won't die() at the end of this method.
	$fallthrough = false;

	if ( isset( $_REQUEST['_typesnonce'] ) ) {
        if ( !wp_verify_nonce( $_REQUEST['_typesnonce'], '_typesnonce' ) ) {
            die( 'Verification failed (1)' );
        }
    } else {

        if (
            !isset( $_REQUEST['_wpnonce'] )
            || !wp_verify_nonce( $_REQUEST['_wpnonce'], $_REQUEST['wpcf_action'] ) 
        ) {
            die( 'Verification failed (2)' );
        }
    }

    global $wpcf;

    switch ( $_REQUEST['wpcf_action'] ) {

        case 'insert_skype_button':
            if( ! current_user_can( 'edit_posts' ) ) {
                die( 'Authentication failed' );
            }

            require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields/skype.php';
            wpcf_fields_skype_meta_box_ajax();
            break;

        case 'editor_callback':

            if( ! current_user_can( 'edit_posts' ) ) {
                die( 'Authentication failed' );
            }

            // Determine Field type and context
            $views_meta = false;
            $field_id = sanitize_text_field( $_GET['field_id'] );

            // todo this could be written in like four lines
            if ( isset( $_GET['field_type'] ) && $_GET['field_type'] == 'usermeta' ) {
                // Group filter
                wp_enqueue_script( 'suggest' );
                $field = types_get_field( $field_id, 'usermeta' );
                $meta_type = 'usermeta';
            } 
            elseif ( isset( $_GET['field_type'] ) && $_GET['field_type'] == 'views-usermeta' ){
                $field = types_get_field( $field_id, 'usermeta' );
                $meta_type = 'usermeta';
                $views_meta = true;
			}
			elseif ( isset( $_GET['field_type'] ) && $_GET['field_type'] == 'termmeta' ) {
                // Group filter
                wp_enqueue_script( 'suggest' );
                $field = types_get_field( $field_id, 'termmeta' );
                $meta_type = 'termmeta';
            } 
            elseif ( isset( $_GET['field_type'] ) && $_GET['field_type'] == 'views-termmeta' ){
                $field = types_get_field( $field_id, 'termmeta' );
                $meta_type = 'termmeta';
                $views_meta = true;
            }else {
                $field = types_get_field( $field_id );
                $meta_type = 'postmeta';
            }

            $parent_post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : null;
            $shortcode = isset( $_GET['shortcode'] ) ? urldecode( $_GET['shortcode'] ) : null;
            $callback = isset( $_GET['callback'] ) ? sanitize_text_field( $_GET['callback'] ) : false;
            if ( !empty( $field ) ) {
                // Editor
                WPCF_Loader::loadClass( 'editor' );
                $editor = new WPCF_Editor();
                $editor->frame( $field, $meta_type, $parent_post_id, $shortcode,
                        $callback, $views_meta );
            }

            break;

        case 'dismiss_message':
            if( ! is_user_logged_in() ) {
                die( 'Authentication failed' );
            }

            if ( isset( $_GET['id'] ) ) {
                $messages = get_option( 'wpcf_dismissed_messages', array() );
                $messages[] = sanitize_text_field( $_GET['id'] );
                update_option( 'wpcf_dismissed_messages', $messages );
            }
            break;

        case 'pr_add_child_post':
            global $current_user;
            $output = '<tr>' . __( 'Passed wrong parameters', 'wpcf' ) . '</tr>';
			$id = 0;
			
			$target_post_type = isset( $_GET['post_type_child'] ) ? sanitize_text_field( $_GET['post_type_child'] ) : '';
			
			$has_permissions  = current_user_can( 'publish_posts' );
			$has_permissions = apply_filters('toolset_access_api_get_post_type_permissions', $has_permissions, $target_post_type, 'publish');
						
			if ( ! $has_permissions ) {
				$output = '<tr><td>' . __( 'You do not have rights to create new items', 'wpcf' ) . '</td></tr>';
			} else if ( 
				//current_user_can( 'edit_posts' )
                /*&&*/ isset( $_GET['post_id'] )
                && isset( $_GET['post_type_child'] )
                && isset( $_GET['post_type_parent'] ) )
            {

                $relationships = get_option( 'wpcf_post_relationship', array() );
                $parent_post_id = intval( $_GET['post_id'] );
                $parent_post = get_post( $parent_post_id );
                if ( !empty( $parent_post->ID ) ) {
                    $post_type = sanitize_text_field( $_GET['post_type_child'] );
                    $parent_post_type = sanitize_text_field( $_GET['post_type_parent'] );
                    // @todo isset & error handling
                    $data = $relationships[$parent_post_type][$post_type];
                    /*
                     * Since Types 1.1.5
                     * 
                     * We save new post
                     * CHECKPOINT
                     */
                    $id = $wpcf->relationship->add_new_child( $parent_post->ID, $post_type );

                    if ( is_wp_error( $id ) ) {
                        $output = '<tr>' . $id->get_error_message() . '</tr>';
                    } else {
                        /*
                         * Here we set Relationship
                         * CHECKPOINT
                         */
                        $parent = get_post( $parent_post_id );
                        $child = get_post( $id );
                        if ( !empty( $parent->ID ) && !empty( $child->ID ) ) {

                            // Set post
                            $wpcf->post = $child;

                            // Set relationship :)
                            $wpcf->relationship->_set( $parent, $child, $data );

                            // Render new row
                            $output = $wpcf->relationship->child_row( $parent_post->ID, $id, $data );
                        } else {
                            $output = '<tr>' . __( 'Error creating post relationship', 'wpcf' ) . '</tr>';
                        }
                    }
                } else {
                    $output = '<tr>' . __( 'Error getting parent post', 'wpcf' ) . '</tr>';
                }
            }
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output . wpcf_form_render_js_validation( '#post', false ),
                    'child_id' => $id,
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                    'child_id' => $id,
                ) );
            }
            break;

        case 'pr_save_all':
            ob_start(); // Try to catch any errors
            $output = '';
            if ( current_user_can( 'edit_posts' ) && isset( $_POST['post_id'] ) ) {

                $parent_id = intval( $_POST['post_id'] );
                $post_type = sanitize_text_field( $_POST['post_type'] );
                if ( isset( $_POST['wpcf_post_relationship'][$parent_id] ) ) {

                    $children = wpcf_sanitize_post_realtionship_input( (array) $_POST['wpcf_post_relationship'][$parent_id] );

                    $wpcf->relationship->save_children( $parent_id, $children );
                    $output = $wpcf->relationship->child_meta_form(
                            $parent_id, strval( $post_type )
                    );
                }
            }
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                // TODO Move to conditional
                $output .= '<script type="text/javascript">wpcfConditionalInit();</script>';
            }
            wpcf_show_admin_messages('echo');
            $errors = ob_get_clean();
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output,
                    'errors' => $errors
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                    'errors' => $errors
                ) );
            }
            break;

        case 'pr_save_child_post':
            ob_start(); // Try to catch any errors
            $output = '';
            if ( current_user_can( 'edit_posts' ) && isset( $_GET['post_id'] )
                    && isset( $_GET['parent_id'] )
                    && isset( $_GET['post_type_parent'] )
                    && isset( $_GET['post_type_child'] )
                    && isset( $_POST['wpcf_post_relationship'] ) ) {

                $parent_id = intval( $_GET['parent_id'] );
                $child_id = intval( $_GET['post_id'] );
                $parent_post_type = sanitize_text_field( $_GET['post_type_parent'] );
                $child_post_type = sanitize_text_field( $_GET['post_type_child'] );

                if ( isset( $_POST['wpcf_post_relationship'][$parent_id][$child_id] ) ) {
                    $fields = wpcf_sanitize_post_relationship_input_fields( (array) $_POST['wpcf_post_relationship'][$parent_id][$child_id] );
                    $wpcf->relationship->save_child( $parent_id, $child_id, $fields );

                    $output = $wpcf->relationship->child_row(
                            $parent_id,
                            $child_id,
                            $wpcf->relationship->settings( $parent_post_type, $child_post_type ) );

                    if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                        // TODO Move to conditional
                        $output .= '<script type="text/javascript">wpcfConditionalInit(\'#types-child-row-' . $child_id . '\');</script>';
                    }
                }
            }
            wpcf_show_admin_messages('echo');
            $errors = ob_get_clean();
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output,
                    'errors' => $errors
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'errors' => $errors,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                ) );
            }
            break;

        case 'pr_delete_child_post':
            require_once WPCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
            $output = 'Passed wrong parameters';
            if ( current_user_can( 'edit_posts' ) && isset( $_GET['post_id'] ) ) {
                $output = wpcf_pr_admin_delete_child_item( intval( $_GET['post_id'] ) );
            }
            echo json_encode( array(
                'output' => $output,
            ) );
            break;

        case 'pr_pagination':
            require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
            require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
            require_once WPCF_EMBEDDED_ABSPATH . '/includes/post-relationship.php';
            $output = 'Passed wrong parameters';
            if ( current_user_can( 'edit_posts' ) && isset( $_GET['post_id'] ) && isset( $_GET['post_type'] ) ) {
                global $wpcf;
                $parent = get_post( intval( $_GET['post_id'] ) );
                $child_post_type = sanitize_text_field( $_GET['post_type'] );

                if ( !empty( $parent->ID ) ) {

                    // Set post in loop
                    $wpcf->post = $parent;

                    // Save items_per_page
                    $wpcf->relationship->save_items_per_page(
                            $parent->post_type, $child_post_type,
                            intval( $_GET[$wpcf->relationship->items_per_page_option_name] )
                    );

                    $output = $wpcf->relationship->child_meta_form(
                            $parent->ID, $child_post_type
                    );
                }
            }
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output,
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                ) );
            }
            break;

        case 'pr_sort':
            $output = 'Passed wrong parameters';
            if ( current_user_can( 'edit_posts' ) && isset( $_GET['field'] ) && isset( $_GET['sort'] ) && isset( $_GET['post_id'] ) && isset( $_GET['post_type'] ) ) {
                $output = $wpcf->relationship->child_meta_form(
                        intval( $_GET['post_id'] ), sanitize_text_field( $_GET['post_type'] )
                );
            }
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output,
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                ) );
            }
            break;

        // Not used anywhere
        /*case 'pr_sort_parent':
            $output = 'Passed wrong parameters';
            if ( isset( $_GET['field'] ) && isset( $_GET['sort'] ) && isset( $_GET['post_id'] ) && isset( $_GET['post_type'] ) ) {
                $output = $wpcf->relationship->child_meta_form(
                        intval( $_GET['post_id'] ), strval( $_GET['post_type'] )
                );
            }
            if ( !defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                echo json_encode( array(
                    'output' => $output,
                ) );
            } else {
                echo json_encode( array(
                    'output' => $output,
                    'conditionals' => array('#post' => wptoolset_form_get_conditional_data( 'post' )),
                ) );
            }
            break;*/
        /* Usermeta */
        case 'um_repetitive_add':

            if ( isset( $_GET['user_id'] ) ) {
                $user_id = (int) $_GET['user_id'];
            } else {
                $user_id = wpcf_usermeta_get_user();
            }

            if ( isset( $_GET['field_id'] )
                && current_user_can( 'edit_user', $user_id ) ) {
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
                $field = wpcf_admin_fields_get_field( sanitize_text_field( $_GET['field_id'] ), false,
                        false, false, 'wpcf-usermeta' );
                global $wpcf;
                $wpcf->usermeta_repeater->set( $user_id, $field );
                /*
                 * 
                 * Force empty values!
                 */
                $wpcf->usermeta_repeater->cf['value'] = null;
                $wpcf->usermeta_repeater->meta = null;
                $form = $wpcf->usermeta_repeater->get_field_form( null, true );

                echo json_encode( array(
                    'output' => wpcf_form_simple( $form )
                    . wpcf_form_render_js_validation( '#your-profile', false ),
                ) );
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;

        case 'um_repetitive_delete':
            if ( isset( $_POST['user_id'] )
                && isset( $_POST['field_id'] )
                && current_user_can( 'edit_user', intval( $_POST['user_id'] ) ) )
            {
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                $user_id = intval( $_POST['user_id'] );

                $field = wpcf_admin_fields_get_field( sanitize_text_field( $_POST['field_id'] ), false,
                        false, false, 'wpcf-usermeta' );
                $meta_id = intval( $_POST['meta_id'] );

                if ( !empty( $field ) && !empty( $user_id ) && !empty( $meta_id ) ) {
                    /*
                     * 
                     * 
                     * Changed.
                     * Since Types 1.2
                     */
                    global $wpcf;
                    $wpcf->usermeta_repeater->set( $user_id, $field );
                    $wpcf->usermeta_repeater->delete( $meta_id );

                    echo json_encode( array(
                        'output' => 'deleted',
                    ) );
                } else {
                    echo json_encode( array(
                        'output' => 'field or post not found',
                    ) );
                }
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;
        /* End Usermeta */
        case 'repetitive_add':
            if ( current_user_can( 'edit_posts' ) && isset( $_GET['field_id'] ) ) {
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
                $field = wpcf_admin_fields_get_field( sanitize_text_field( $_GET['field_id'] ) );
                $parent_post_id = intval( $_GET['post_id'] );

                /*
                 * When post is new - post_id is 0
                 * We can safely set post_id to 1 cause
                 * values compared are filtered anyway.
                 */
                if ( $parent_post_id == 0 ) {
                    $parent_post_id = 1;
                }

                $parent_post = get_post( $parent_post_id );

                global $wpcf;
                $wpcf->repeater->set( $parent_post, $field );
                /*
                 * 
                 * Force empty values!
                 */
                $wpcf->repeater->cf['value'] = null;
                $wpcf->repeater->meta = null;
                $form = $wpcf->repeater->get_field_form( null, true );

                echo json_encode( array(
                    'output' => wpcf_form_simple( $form )
                    . wpcf_form_render_js_validation( '#post', false ),
                ) );
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;

        case 'repetitive_delete':
            if ( current_user_can( 'edit_posts' ) && isset( $_POST['post_id'] ) && isset( $_POST['field_id'] ) ) {
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                $post_id = intval( $_POST['post_id'] );
                $parent_post = get_post( $post_id );
                $field = wpcf_admin_fields_get_field( sanitize_text_field( $_POST['field_id'] ) );
                $meta_id = intval( $_POST['meta_id'] );
                if ( !empty( $field ) && !empty( $parent_post->ID ) && !empty( $meta_id ) ) {
                    /*
                     * 
                     * 
                     * Changed.
                     * Since Types 1.2
                     */
                    global $wpcf;
                    $wpcf->repeater->set( $parent_post, $field );
                    $wpcf->repeater->delete( $meta_id );

                    echo json_encode( array(
                        'output' => 'deleted',
                    ) );
                } else {
                    echo json_encode( array(
                        'output' => 'field or post not found',
                    ) );
                }
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;

        case 'wpcf_entry_search':
            if( current_user_can( 'edit_posts' ) && isset($_REQUEST['post_type'])) {
                $posts_per_page = apply_filters( 'wpcf_pr_belongs_post_numberposts', 10 );

                $args = array(
                    'posts_per_page' => apply_filters( 'wpcf_pr_belongs_post_posts_per_page', $posts_per_page ),
                    'post_status' => apply_filters( 'wpcf_pr_belongs_post_status', array( 'publish', 'private' ) ),
                    'post_type' => sanitize_text_field( $_REQUEST['post_type'] ),
                    'suppress_filters' => 1,
                );

                if ( isset( $_REQUEST['s'] ) ) {
                    $args['s'] = $_REQUEST['s'];
                }

                if ( isset( $_REQUEST['page'] ) && preg_match('/^\d+$/', $_REQUEST['page']) ) {
                    $args['paged'] = intval($_REQUEST['page']);
                }

                $the_query = new WP_Query( $args );

                $posts = array(
                    'items' => array(),
                    'total_count' => $the_query->found_posts,
                    'incomplete_results' => $the_query->found_posts > $posts_per_page,
                    'posts_per_page' => $posts_per_page,
                );

                if ( $the_query->have_posts() ) {
                    while ( $the_query->have_posts() ) {
                        $the_query->the_post();
                        $post_title = get_the_title();
                        if ( empty($post_title) ) {
                            $post_title = sprintf(
                                __('[empty title] ID: %d', 'wpcf'),
                                get_the_ID()
                            );
                        }
                        $posts['items'][] = array(
                            'ID' => get_the_ID(),
                            'post_title' => $post_title,
                        );
                    }
                }
                /* Restore original Post Data */
                wp_reset_postdata();

                echo json_encode($posts);
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;

        case 'wpcf_entry_entry':
            if( current_user_can( 'edit_posts' ) && isset($_REQUEST['p'])) {
                $wpcf_post = get_post( (int) $_REQUEST['p'], ARRAY_A );
                if ( isset($wpcf_post['ID']) ) {
                        $post_title = $wpcf_post['post_title'];
                        if ( empty($post_title) ) {
                            $post_title = sprintf(
                                __('[empty title] ID: %d', 'wpcf'),
                                $wpcf_post['ID']
                            );
                        }
                    echo json_encode(
                        array(
                            'ID' => $wpcf_post['ID'],
                            'post_title' => $wpcf_post['post_title'],
                        )
                    );
                } else {
                    echo json_encode( array( 'output' => 'params missing',));
                }
            } else {
                echo json_encode( array(
                    'output' => 'params missing',
                ) );
            }
            break;

        default:
        	$fallthrough = true;
            break;
    }
    if ( function_exists( 'wpcf_ajax' ) ) {
        $fallthrough = wpcf_ajax( $fallthrough );
    }

    if( ! $fallthrough ) {
		die();
	}
}


/**
 * Sanitize input array with post children and their fields.
 *
 * @param array $children_raw See WPCF_Relationship::save_children().
 * @return array Data with the same structure as input, but sanitized.
 *
 * @todo since
 * @todo move to better location if such exists
 */
function wpcf_sanitize_post_realtionship_input( $children_raw ) {
    $children = array();
    foreach( $children_raw as $child_id_raw => $child_fields_raw ) {
        $child_id = intval( $child_id_raw );
        $children[ $child_id ] = wpcf_sanitize_post_relationship_input_fields( $child_fields_raw );
    }
    return $children;
}


/**
 * Sanitize input array with post child fields.
 *
 * Note that only field keys are sanitized. Values can be arbitrary.
 *
 * @param array $fields_raw See WPCF_Relationship::save_child().
 * @return array Data with the same structure as input, but sanitized.
 *
 * @todo since
 * @todo move to better location if such exists
 */
function wpcf_sanitize_post_relationship_input_fields( $fields_raw ) {
    $fields = array();
    foreach( $fields_raw as $field_key_raw => $field_value_raw ) {
        $field_key = sanitize_text_field( $field_key_raw );
        $fields[ $field_key ] = $field_value_raw;
    }
    return $fields;
}
