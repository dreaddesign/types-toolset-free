<?php

/*
 * Edit post page functions
 *
 *
 *
 * Core file with stable and working functions.
 * Please add hooks if adjustment needed, do not add any more new code here.
 *
 * Consider this file half-locked since Types 1.2
 */

// Include conditional field code
require_once WPCF_EMBEDDED_ABSPATH . '/includes/conditional-display.php';

/**
 * Init functions for post edit pages.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post
 */
function wpcf_admin_post_init( $post ) {
    add_action( 'admin_footer', 'wpcf_admin_fields_postfields_styles' );
    wpcf_admin_add_js_settings( 'wpcf_nonce_toggle_group',
            '\'' . wp_create_nonce( 'group_form_collapsed' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcf_nonce_toggle_fieldset',
            '\'' . wp_create_nonce( 'form_fieldset_toggle' ) . '\'' );

    // Get post_type
    $post_type = wpcf_admin_get_edited_post_type( $post );

    /*
     *
     * This is left to maintain compatibility with older versions
     * TODO Remove
     */
    // Add items to View dropdown
    if ( in_array( $post_type, array('view', 'view-template', 'cred-form', 'cred-user-form') ) ) {
        add_filter( 'editor_addon_menus_wpv-views',
                'wpcf_admin_post_editor_addon_menus_filter' );
        add_action( 'admin_footer', 'wpcf_admin_post_js_validation' );
        wpcf_enqueue_scripts();
        wp_enqueue_script( 'toolset-colorbox' );
        wp_enqueue_style( 'toolset-colorbox' );
    }
    // Never show on 'Views' and 'Content Templates'
    if ( in_array( $post_type, array('view', 'view-template') ) ) {
        return false;
    }

    /**
     * remove custom field WordPress metabox
     */
    if ( 'hide' == wpcf_get_settings('hide_standard_custom_fields_metabox') ) {
        foreach( array( 'normal', 'advanced', 'side' ) as $context) {
            remove_meta_box('postcustom', $post_type, $context );
        }
    }

    // Are Types active?
    $wpcf_active = false;

    // Get groups
    $groups = wpcf_admin_post_get_post_groups_fields( $post );

    foreach ( $groups as $group ) {

        $only_preview = '';

        //If Access plugin activated
        if ( function_exists( 'wpcf_access_register_caps' ) ) {
            //If user can't view own profile fields
            if ( !current_user_can( 'view_fields_in_edit_page_' . $group['slug'] ) ) {
                continue;
            }
            //If user can modify current group in own profile
            if ( !current_user_can( 'modify_fields_in_edit_page_' . $group['slug'] ) ) {
                $only_preview = 1;
            }
        }
        if ( !empty( $group['fields'] ) && empty( $only_preview ) ) {
            $wpcf_active = true;
            break;
        }
    }

    // Activate scripts
    if ( $wpcf_active ) {
        add_action( 'admin_head', 'wpcf_post_preview_warning' );
        wpcf_edit_post_screen_scripts();
    }

    // Add validation
    add_action( 'admin_footer', 'wpcf_admin_post_js_validation' );

    /*
     * TODO Review
     * This is forced because of various Child cases
     * and when field are rendered via AJAX but not registered yet.
     *
     * Basically all fields that require additional JS should be added here.
     *
     * This is a must for now.
     * These fields need init JS in various cases.
     */
    wpcf_field_enqueue_scripts( 'date' );
    wpcf_field_enqueue_scripts( 'image' );
    wpcf_field_enqueue_scripts( 'file' );
    wpcf_field_enqueue_scripts( 'skype' );
    wpcf_field_enqueue_scripts( 'numeric' );

    do_action( 'wpcf_admin_post_init', $post_type, $post, $groups, $wpcf_active );
}

/**
 * Add meta boxes.
 *
 * @param type $post_type
 * @param type $post
 * @return boolean
 */
function wpcf_add_meta_boxes( $post_type, $post )
{

    $post_types_without_meta_boxes = array(
        'view',
        'view-template',
        'acf-field-group',
        'acf'
    );

    /**
     * Do not add metaboxes
     *
     * Filter allow to add post types which are immune to wpcf_add_meta_boxes()
     * function.
     *
     * @since 1.9.0
     *
     * @param array $post_types array of post types slugs
     */
    $post_types_without_meta_boxes = apply_filters(
        'wpcf_exclude_meta_boxes_on_post_type',
        $post_types_without_meta_boxes
    );

    /** This action is documented in embedded/bootstrap.php */
    $post_types_without_meta_boxes = apply_filters(
        'toolset_filter_exclude_own_post_types',
        $post_types_without_meta_boxes
    );

    /**
     * check
     */
    if ( !empty($post_types_without_meta_boxes) && in_array( $post_type, $post_types_without_meta_boxes ) ) {
        return false;
    }

    //  Fix for empty $post (tabify)
    if ( empty( $post->ID ) ) {
        $post = get_default_post_to_edit( $post_type, false );
    }

    // Get groups
    $groups = wpcf_admin_post_get_post_groups_fields( $post );

    foreach ( $groups as $group ) {

        $only_preview = '';

        //If Access plugin activated
        if ( function_exists( 'wpcf_access_register_caps' ) ) {
            //If user can't view own profile fields
            if ( !current_user_can( 'view_fields_in_edit_page_' . $group['slug'] ) ) {
                continue;
            }
            //If user can modify current group in own profile
            if ( !current_user_can( 'modify_fields_in_edit_page_' . $group['slug'] ) ) {
                $only_preview = 1;
            }
        }

        // Process fields
        if ( !empty( $group['fields'] ) && empty( $only_preview ) ) {
            if ( defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                $errors = get_post_meta( $post->ID, '__wpcf-invalid-fields', true );
                $group['html'] = array();
                foreach ( $group['fields'] as $field ) {
                    wpcf_admin_post_add_to_editor( $field );
	                // Note that the $single argument defaults to false, ergo $meta will be allways an array of metadata,
	                // even for single fields.
                    $meta = get_post_meta( $post->ID, $field['meta_key'] );
                    $config = wptoolset_form_filter_types_field( $field, $post->ID );
                    if ( $errors ) {
                        $config['validate'] = true;
                    }
                    if ( $field['type'] == 'wysiwyg' ) {
                        $group['html'][] = array('config' => $config, 'meta' => $meta);
                    } else {
                        $group['html'][] = wptoolset_form_field( 'post',
                                $config, $meta );
                    }
                }
                // OLD
                delete_post_meta( $post->ID, 'wpcf-invalid-fields' );
                delete_post_meta( $post->ID, '__wpcf-invalid-fields' );
            } else {
                // Process fields
                $group['fields'] = wpcf_admin_post_process_fields( $post,
                        $group['fields'], true );
            }
        }

        // Check if hidden
        if ( !isset( $group['__show_meta_box'] ) || $group['__show_meta_box'] != false ) {

            $field_group = Types_Field_Group_Post_Factory::load( $group['slug'] );

            // Skip field groups that can't be loaded
            if( null !== $field_group ) {

                $group_wpml = new Types_Wpml_Field_Group( $field_group );

                // Add meta boxes
                if ( empty( $only_preview ) ) {
                    add_meta_box( "wpcf-group-{$group['slug']}",
                        $group_wpml->translate_name(),
                        'wpcf_admin_post_meta_box',
                        $post_type, $group['meta_box_context'], 'high', $group );
                } else {
                    add_meta_box( "wpcf-group-{$group['slug']}",
                        $group_wpml->translate_name(),
                        'wpcf_admin_post_meta_box_preview', $post_type,
                        $group['meta_box_context'], 'high', $group );
                }

            }
        }
    }
}

/**
 * Renders meta box content (preview).
 *
 * @param $post
 * @param array $group
 * @param string $echo
 *
 * @return string
 */
function wpcf_admin_post_meta_box_preview( $post, $group, $echo = '' ){

    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_ABSPATH . '/frontend.php';
    global $wpcf;

    // $group['args']['fields'] or $group['fields'] or array().
    $fields = wpcf_getnest(
        $group,
        array( 'args', 'fields' ),
        wpcf_getarr( $group, 'fields', array() )
    );

    $slug = wpcf_getarr( $group, 'slug', wpcf_getarr( $group, 'id' ) );
    $name = wpcf_getarr( $group, 'name', wpcf_getarr( $group, 'title' ) );

    $post = wpcf_admin_create_fake_post_if_need( $post );

    $group_output = '';
    if ( !empty( $echo ) ) {
        $group_output = '<h3>This Preview generated for latest post "' . $post->post_title . '"</h3>';
        $group_output .= '<div id="test-field-group" class="postbox " >
<h3 class=\'hndle\'><span>' . $name . '</span></h3>
<div class="inside">';
    }

    $group_output .= '<table class="form-table fields-preview fields-preview-' . $slug . '">
<tbody>' . "\n\n";

    $content = $code = '';
    if( !empty( $fields ) ) {
        foreach ( $fields as $field ) {
            $html = '';
            $params['separator'] = ', ';
            if ( wpcf_admin_is_repetitive( $field ) ) {
                $wpcf->repeater->set( $post, $field );
                $_meta = $wpcf->repeater->_get_meta();
                if ( isset( $_meta['custom_order'] ) ) {
                    $meta = $_meta['custom_order'];
                } else {
                    $meta = array();
                }
                $content = $code = '';
                // Sometimes if meta is empty - array(0 => '') is returned
                if ( (count( $meta ) == 1 ) ) {
                    $meta_id = key( $meta );
                    $_temp = array_shift( $meta );
                    if (!is_array($_temp) && strval( $_temp ) == '' ) {

                    } else {
                        $params['field_value'] = $_temp;
                        if ( !empty( $params['field_value'] ) ) {
                            $html = types_render_field_single( $field, $params,
                                $content, $code, $meta_id );
                        }
                    }
                } else if ( !empty( $meta ) ) {
                    $output = '';

                    if ( isset( $params['index'] ) ) {
                        $index = $params['index'];
                    } else {
                        $index = '';
                    }

                    // Allow wpv-for-each shortcode to set the index
                    $index = apply_filters( 'wpv-for-each-index', $index );

                    if ( $index === '' ) {
                        $output = array();
                        foreach ( $meta as $temp_key => $temp_value ) {
                            $params['field_value'] = $temp_value;
                            if ( !empty( $params['field_value'] ) ) {
                                $temp_output = types_render_field_single( $field,
                                    $params, $content, $code, $temp_key );
                            }
                            if ( !empty( $temp_output ) ) {
                                $output[] = $temp_output;
                            }
                        }
                        if ( !empty( $output ) && isset( $params['separator'] ) ) {
                            $output = implode( html_entity_decode( $params['separator'] ),
                                $output );
                        } else if ( !empty( $output ) ) {
                            $output = implode( '', $output );
                        }
                    } else {
                        // Make sure indexed right
                        $_index = 0;
                        foreach ( $meta as $temp_key => $temp_value ) {
                            if ( $_index == $index ) {
                                $params['field_value'] = $temp_value;
                                if ( !empty( $params['field_value'] ) ) {
                                    $output = types_render_field_single( $field,
                                        $params, $content, $code, $temp_key );
                                }
                            }
                            $_index++;
                        }
                    }
                    $html = $output;
                }
            } else {
                $params['field_value'] = get_post_meta( $post->ID,
                    wpcf_types_get_meta_prefix( $field ) . $field['slug'], true );

                //  $html = types_render_field_single( $field, $params, $content, $code );
                if ( !empty( $params['field_value'] ) && $field['type'] != 'date' ) {
                    $html = types_render_field_single( $field, $params, $content,
                        $code );
                }
                if ( $field['type'] == 'date' && !empty( $params['field_value'] ) ) {
                    $html = types_render_field_single( $field, $params, $content,
                        $code );
                    if ( $field['data']['date_and_time'] == 'and_time' ) {
                        $html .= ' ' . date( "H", $params['field_value'] ) . ':' . date( "i",
                                $params['field_value'] );
                    }
                }
            }

            // API filter
            $wpcf->field->set( $post, $field );
            $field_value = $wpcf->field->html( $html, array() );
            $group_output .= '<tr class="wpcf-profile-field-line-' . $field['slug'] . '">
	<td scope="row">' . $field['name'] . '</td>
    <td>' . $field_value . '</td>
</tr>' . "\n\n";
        }
    }

    $group_output .= "\n\n</tbody>
</table>";
    if ( empty( $echo ) ) {
        echo $group_output;
    } else {
        $group_output .= "\n\n</div></div>";
        return $group_output;
    }




}

/**
 * Renders meta box content.
 *
 * @param $post
 * @param array $group
 * @param string $echo
 * @param bool $open_style_editor if true use code for open style editor when edit group
 *
 * @return string|void
 */
function wpcf_admin_post_meta_box( $post, $group, $echo = '', $open_style_editor = false )
{

    $field_group = Types_Field_Group_Post_Factory::load( wpcf_getnest( $group, array( 'args', 'slug' ) ) );
    // Field group will not exist when we're creating a new one.
    $group_wpml = ( null !== $field_group ) ? new Types_Wpml_Field_Group( $field_group ) : null;

    if (
        false === $open_style_editor
        && defined( 'WPTOOLSET_FORMS_VERSION' )
        && isset( $group['args']['html'] )
    ) {

        // Show group description if there's something to show.
        $description = wpcf_getnest( $group, array( 'args', 'description' ), '' );
        if ( ! empty( $description ) ) {
            $description = ( null !== $group_wpml ? $group_wpml->translate_description() : $description );

            echo '<div class="wpcf-meta-box-description">';
            echo wpautop( $description );
            echo '</div>';
        }
        foreach ( $group['args']['html'] as $field ) {
            echo is_array( $field ) ? wptoolset_form_field( 'post',
                $field['config'], $field['meta'] ) : $field;
        }

        return;
    }

    global $wpcf;

    $post = wpcf_admin_create_fake_post_if_need( $post );

    static $nonce_added = false;
    $group_output = '';

    // A compatibility fix, maybe somehow related to user fields, maybe outdated.
    // Beware issues with non-existent (new) groups.
    if ( ! isset( $group['title'] ) ) {
        $temp = $group;
        $name = wpcf_getarr( $temp, 'name' );
        $group = array(
            'args' => $temp,
            'id' => (int) wpcf_getarr( $temp, 'slug' ),
            'title' => $name
        );
    }
    if ( !empty( $echo ) ) {
        $group_output = '<h3>This Preview generated for latest post "' . $post->post_title . '"</h3>' . "\n" .
            '<!-- Previous lines visible only in Admin Style Editor.-->' . "\n\n";
        $group_output .= '<div id="wpcf-group-' . $group['id'] . '" class="postbox " >
            <h3 class=\'hndle\'><span>' . $name . '</span></h3>
            <div class="inside">' . "\n";
    }


    /*
     * TODO Document where this is used
     */
    if ( !$nonce_added && empty( $echo ) ) {
        $nonce_action = 'update-' . $post->post_type . '_' . $post->ID;
        wp_nonce_field( $nonce_action, '_wpcf_post_wpnonce' );
        $nonce_added = true;
    }
    $group_output .= "\n\n" . '<div id="wpcf-group-metabox-id-' . esc_attr( wpcf_getnest( $group, array( 'args', 'slug' ), 'new-group' ) ) . '">' . "\n";
    /*
     * TODO Move to Conditional code
     *
     * This is already checked. Use hook to add wrapper DIVS and apply CSS.
     */
    if ( !empty( $group['args']['_conditional_display'] ) ) {
        if ( $group['args']['_conditional_display'] == 'failed' ) {
            $group_output .= '<div class="wpcf-cd-group wpcf-cd-group-failed" style="display:none;">';
        } else {
            $group_output .= '<div class="wpcf-cd-group wpcf-cd-group-passed">';
        }
    }


    /*
     * TODO Move this into Field code
     * Process fields
     */
    if ( !empty( $group['args']['fields'] ) ) {
        // Display description
        if ( !empty( $group['args']['description'] ) ) {
            $group_output .= '<div class="wpcf-meta-box-description">'
                . wpautop( $group_wpml->translate_description() ) . '</div>';
        }
        foreach ( $group['args']['fields'] as $field_slug => $field ) {
            if ( empty( $field ) || !is_array( $field ) ) {
                continue;
            }

            $field = $wpcf->field->_parse_cf_form_element( $field );

            if ( isset( $field['wpcf-type'] ) ) { // May be ignored
                $field = apply_filters( 'wpcf_fields_' . $field['wpcf-type'] . '_meta_box_form_value_display',
                    $field );
            }

            if ( !isset( $field['#id'] ) ) {
                $field['#id'] = wpcf_unique_id( serialize( $field ) );
            }
            // Render form elements
            if (
                wpcf_compare_wp_version()
                && array_key_exists( '#type', $field )
                && 'wysiwyg' == $field['#type']
                && !isset( $field['#attributes']['disabled'] )
            ) {
                //                if ( isset( $field['#attributes']['disabled'] ) ) {
                //                    $field['#editor_settings']['tinymce'] = false;
                //                    $field['#editor_settings']['teeny'] = false;
                //                    $field['#editor_settings']['media_buttons'] = false;
                //                    $field['#editor_settings']['quicktags'] = false;
                //                    $field['#editor_settings']['dfw'] = false;
                //                }
                // Especially for WYSIWYG
                $group_output .= '<div class="wpcf-wysiwyg">';
                $group_output .= '<div id="wpcf-textarea-textarea-wrapper" class="form-item form-item-textarea wpcf-form-item wpcf-form-item-textarea">';
                $group_output .= isset( $field['#before'] ) ? $field['#before'] : '';
                $group_output .= '<label class="wpcf-form-label wpcf-form-textarea-label">'
                    . stripslashes( $field['#title'] ) . '</label>';
                $group_output .= '<div class="description wpcf-form-description wpcf-form-description-textarea description-textarea">
                    ' . wpautop( $field['#description'] ) . '</div>';
                ob_start();
                wp_editor( $field['#value'], $field['#id'],
                    $field['#editor_settings'] );
                $group_output .= ob_get_clean() . "\n\n";
                $field['slug'] = str_replace( WPCF_META_PREFIX . 'wysiwyg-', '',
                    $field_slug );
                $field['type'] = 'wysiwyg';
                $group_output .= '</div>';
                $group_output .= isset( $field['#after'] ) ? $field['#after'] : '';
                $group_output .= '</div>';
            } else {
                if (
                    array_key_exists( '#type', $field )
                    && 'wysiwyg' == $field['#type']
                ) {
                    $field['#type'] = 'textarea';
                }
                if ( !empty( $echo ) ) {
                    $field['#validate'] = '';
                }
                $group_output .= wpcf_form_simple( array($field['#id'] => $field) );
            }
            do_action( 'wpcf_fields_' . $field_slug . '_meta_box_form', $field );
            if ( isset( $field['wpcf-type'] ) ) { // May be ignored
                do_action( 'wpcf_fields_' . $field['wpcf-type'] . '_meta_box_form', $field );
            }
        }
    }

    /*
     * TODO Move to Conditional code
     *
     * This is already checked. Use hook to add wrapper DIVS and apply CSS.
     */
    if ( !empty( $group['args']['_conditional_display'] ) ) {
        $group_output .= '</div>';
    }

    $group_output .= '</div>';

    if ( !empty( $echo ) ) {
        $group_output .= "\n\n</div></div>";
        return $group_output;
    } else {
        echo $group_output;
    }
}

/**
 * The save_post hook.
 *
 * @param int $post_ID
 * @param WP_Post $post
 * @since unknown
 * @refactoring !! Extreme spaghetti code.
 */
function wpcf_admin_post_save_post_hook( $post_ID, $post ) {

	// Apparently, some things are *slightly* different when saving a child post in the Post relationships metabox.
	// Ugly hack that will go away with m2m.
	//
	// Note: The types_updating_child_post filter is needed for a situation when user clicks on the Update button
	// for the parent post. In that case we don't get enough information to determine if a child post is being updated.
	$is_child_post_update = (
		( 'wpcf_ajax' == wpcf_getget( 'action' ) && 'pr_save_child_post' == wpcf_getget( 'wpcf_action' ) )
		|| apply_filters( 'types_updating_child_post', false )
	);

	global $wpcf;
	$errors = false;

	// Do not save post if is type of
	if ( in_array( $post->post_type,
		array(
			'revision',
			'view',
			'view-template',
			'cred-form',
			'cred-user-form',
			'nav_menu_item',
			'mediapage'
		) ) )
	{
		return;
	}

	$wpcf_form_data = wpcf_ensarr( wpcf_getarr( $_POST, 'wpcf' ) );


	// For parent saving we need to add all checkbox/radio fields (even unchecked) to
    // make sure save 0 is applied. This is NOT needed for the child update at this point
    // (Including child post here will add all checkbox fields to children, even if the fields
    // are not assigned to the child post cpt)
    if( ! $is_child_post_update ) {
	    // Check wpcf_adjust_form_input_for_checkboxlike_fields() for information about side effects.
	    $wpcf_form_data = wpcf_adjust_form_input_for_checkboxlike_fields(
		    $wpcf_form_data,
		    wpcf_ensarr( wpcf_getarr( $_POST, '_wptoolset_checkbox' ) )
	    );

	    $wpcf_form_data = wpcf_adjust_form_input_for_checkboxlike_fields(
		    $wpcf_form_data,
		    wpcf_ensarr( wpcf_getarr( $_POST, '_wptoolset_radios' ) )
	    );
    }

	if ( count( $wpcf_form_data ) ) {
		$add_error_message = true;
		if ( isset( $_POST['post_id'] ) && $_POST['post_id'] != $post_ID ) {
			$add_error_message = false;
		}

		// check some attachment to delete
		$delete_attachments = apply_filters( 'wpcf_delete_attachments', true );
		$images_to_delete = array();
		if ( $delete_attachments && isset( $_POST['wpcf'] ) && array_key_exists( 'delete-image', $_POST['wpcf'] ) && is_array( $_POST['wpcf']['delete-image'] ) ) {
			foreach ( $_POST['wpcf']['delete-image'] as $image ) {
				$images_to_delete[ $image ] = 1;
			}
		}
		foreach ( $wpcf_form_data as $field_slug => $field_value ) {
			// Get field by slug
			$field = wpcf_fields_get_field_by_slug( $field_slug );
			if ( empty( $field ) ) {
				continue;
			}
			// Skip copied fields
			if ( isset( $_POST['wpcf_repetitive_copy'][ $field['slug'] ] ) ) {
				continue;
			}

			// This is (apparently) expected for repetitive fields...
			if ( $is_child_post_update && types_is_repetitive( $field ) ) {
				$field_value = array( $field_value );
			}

			$_field_value = ! types_is_repetitive( $field ) ? array( $field_value ) : $field_value;

			// Set config
			$config = wptoolset_form_filter_types_field( $field, $post_ID, $wpcf_form_data );

			// remove from images_to_delete if user add again
			if ( $delete_attachments && 'image' == $config['type'] ) {
				$images = $_field_value;
				if ( ! is_array( $images ) ) {
					$images = array( $images );
				}
				foreach ( $images as $image ) {
					if ( array_key_exists( $image, $images_to_delete ) ) {
						unset( $images_to_delete[ $image ] );
					}
				}
			}

			// add filter to remove field name from error message
			// This filter is toolset-common/toolset-forms/classes/class.validation.php
			add_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false', 1234, 1 );
			foreach ( $_field_value as $_k => $_val ) {
				// Check if valid
				$validation = wptoolset_form_validate_field( 'post', $config, $_val );
				$conditional = wptoolset_form_conditional_check( $config );
				$not_valid = is_wp_error( $validation ) || ! $conditional;
				if ( $add_error_message && is_wp_error( $validation ) ) {
					$errors = true;
					$_errors = $validation->get_error_data();
					$_msg = sprintf( __( 'Field "%s" not updated:', 'wpcf' ), $field['name'] );
					wpcf_admin_message_store( $_msg . ' ' . implode( ', ', $_errors ), 'error' );
				}
				if ( $not_valid ) {
					if ( types_is_repetitive( $field ) ) {
						unset( $field_value[ $_k ] );
					} else {
						break;
					}
				}
			}
			remove_filter( 'toolset_common_validation_add_field_name_to_error', '__return_false', 1234 );

			// Save field
			if ( types_is_repetitive( $field ) ) {
				$wpcf->repeater->set( $post_ID, $field );
				$wpcf->repeater->save( $field_value );
			} else if ( ! $not_valid ) {
				$wpcf->field->set( $post_ID, $field );
				$wpcf->field->save( $field_value );
			}
			do_action( 'wpcf_post_field_saved', $post_ID, $field );

			// Note: Checkboxes fields used to be handled as a special case here, that was now moved
            // to wpcf_update_checkboxes_field() and is executed on *each* save_post by
            // wpcf_fields_checkbox_save_check().

		}

		// delete images
		if ( $delete_attachments && ! empty( $images_to_delete ) ) {
			$args = array(
				'post_parent' => $post_ID,
				'posts_per_page' => - 1,
			);
			$children_array = get_children( $args );
			foreach ( $children_array as $child ) {
				if ( ! array_key_exists( $child->guid, $images_to_delete ) ) {
					continue;
				}
				wp_delete_attachment( $child->ID );
			}
		}

	}
	if ( $errors ) {
		update_post_meta( $post_ID, '__wpcf-invalid-fields', true );
	}
	do_action( 'wpcf_post_saved', $post_ID );
}


/**
 *
 * Only for attachments, only default checkboxes!
 *
 * @internal breakpoint
 * @param type $post_ID
 * @param type $post
 */
function wpcf_admin_post_add_attachment_hook( $post_ID, $post )
{
    global $wpcf;
    /**
     * Basic check: only attachment
     */
    if ( 'attachment' !=  $post->post_type ) {
        return false;
    }

    /**
     * Get all groups connected to this $post
     */
    $groups = wpcf_admin_post_get_post_groups_fields( $post );
    if ( empty( $groups ) ) {
        return false;
    }
    $all_fields = array();
    $_not_valid = array();
    $_error = false;


    /**
     * Loop over each group
     *
     * TODO Document this
     * Connect 'wpcf-invalid-fields' with all fields
     */
    foreach ( $groups as $group ) {
        if ( isset( $group['fields'] ) ) {
            // Process fields
            $fields = wpcf_admin_post_process_fields( $post, $group['fields'],
                true, false, 'validation' );
            // Validate fields
            $form = wpcf_form_simple_validate( $fields );
            $all_fields = $all_fields + $fields;
            // Collect all not valid fields
            if ( $form->isError() ) {
                $_error = true; // Set error only to true
                $_not_valid = array_merge( $_not_valid,
                    (array) $form->get_not_valid() );
            }
        }
    }
    // Set fields
    foreach ( $all_fields as $k => $v ) {
        // only Types field
        if ( empty( $v['wpcf-id'] ) ) {
            continue;
        }
        $_temp = new WPCF_Field();
        $_temp->set( $wpcf->post, $v['wpcf-id'] );
        $all_fields[$k]['_field'] = $_temp;
    }
    foreach ( $_not_valid as $k => $v ) {
        // only Types field
        if ( empty( $v['wpcf-id'] ) ) {
            continue;
        }
        $_temp = new WPCF_Field();
        $_temp->set( $wpcf->post, $v['wpcf-id'] );
        $_not_valid[$k]['_field'] = $_temp;
    }

    /**
     * Process all checkbox fields
     */

    foreach ( $all_fields as $field ) {
        /**
         * only checkbox
         */
        if ( !isset( $field['#type'] ) || 'checkbox' != $field['#type'] ) {
            continue;
        }
        $field_data = wpcf_admin_fields_get_field( $field['wpcf-id'] );
        /**
         * check is checked for new!
         */
        $checked = array_key_exists( 'checked', $field_data['data'] ) && $field_data['data']['checked'];
        /**
         * do not save empty and not checked? fine, go away...
         */
        if ( 'no' == $field_data['data']['save_empty'] && !$checked ) {
            continue;
        }
        /**
         * all other just save...
         */
        $update_data = 0;
        if ( $checked ) {
            $update_data = $field_data['data']['set_value'];
        }
        add_post_meta( $post_ID, wpcf_types_get_meta_prefix( $field ) . $field['wpcf-slug'], $update_data );
    }
    do_action( 'wpcf_attachement_add', $post_ID );
}
/**
 * Renders JS validation script.
 */
function wpcf_admin_post_js_validation() {
    wpcf_form_render_js_validation( '#post' );
}

/**
 * Creates form elements.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post
 * @param type $fields
 * @return type
 */
function wpcf_admin_post_process_fields( $post = false, $fields = array(),
        $use_cache = true, $add_to_editor = true, $context = 'group' ) {

    global $wpcf;

    $wpcf->field->use_cache = $use_cache;
    $wpcf->field->add_to_editor = $add_to_editor;
    $wpcf->repeater->use_cache = $use_cache;
    $wpcf->repeater->add_to_editor = $add_to_editor;

    // Get cached
    static $cache = array();
    $cache_key = !empty( $post->ID ) ? $post->ID . md5( serialize( $fields ) ) : false;
    if ( $use_cache && $cache_key && isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }

    $fields_processed = array();

    // Get invalid fields (if submitted)
    $invalid_fields = array();
    if ( !empty( $post->ID ) ) {
        $invalid_fields = get_post_meta( $post->ID, 'wpcf-invalid-fields', true );
        delete_post_meta( $post->ID, 'wpcf-invalid-fields' );

        /*
         *
         * Add it to global $wpcf
         * From now on take it there.
         */
        $wpcf->field->invalid_fields = $invalid_fields;
    }

    // TODO WPML Get WPML original fields
    $original_cf = array();
    if ( function_exists( 'wpml_get_copied_fields_for_post_edit' ) && !wpcf_wpml_post_is_original( $post ) ) {
        $__fields_slugs = array();
        foreach ( $fields as $_f ) {
            $__fields_slugs[] = $_f['meta_key'];
        }
        $original_cf = wpml_get_copied_fields_for_post_edit( $__fields_slugs );
    }

    if( !empty( $fields ) ) {
        foreach( $fields as $field ) {

            // Repetitive fields
            if( wpcf_admin_is_repetitive( $field ) && $context != 'post_relationship' ) {
                // First check if repetitive fields are copied using WPML
                /*
				 * TODO All WPML specific code needs moving to
				 * /embedded/includes/wpml.php
				 *
				 * @since Types 1.2
				 */
                // TODO WPML move
                if( ! empty( $original_cf['fields'] ) && in_array( wpcf_types_get_meta_prefix( $field ) . $field['slug'],
                        $original_cf['fields'] )
                ) {
                    /*
					 * See if repeater can handle copied fields
					 */
                    $wpcf->repeater->set( get_post( $original_cf['original_post_id'] ),
                        $field );
                    $fields_processed = $fields_processed + $wpcf->repeater->get_fields_form();
                } else {
                    // Set repeater
                    /*
					 *
					 *
					 * @since Types 1.2
					 * Now we're using repeater class to handle repetitive forms.
					 * Main change is - use form from $field_meta_box_form() without
					 * re-processing form elements.
					 *
					 * Field should pass form as array:
					 * 'my_checkbox' => array('#type' => 'checkbox' ...),
					 * 'my_textfield' => array('#type' => 'textfield' ...),
					 *
					 * In form it should set values to be stored.
					 * Use hooks to adjust saved data.
					 */
                    $wpcf->repeater->set( $post, $field );
                    $fields_processed = $fields_processed + $wpcf->repeater->get_fields_form();
                }
                /*
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 *
				 * Non-repetitive fields
				 */
            } else {

                /*
				 * meta_form will be treated as normal form.
				 * See if any obstacles prevent us from using completed
				 * form from config files.
				 *
				 * Main change is - use form from $field_meta_box_form() without
				 * re-processing form elements.
				 *
				 * Field should pass form as array:
				 * 'my_checkbox' => array('#type' => 'checkbox' ...),
				 * 'my_textfield' => array('#type' => 'textfield' ...),
				 *
				 * In form it should set values to be stored.
				 * Use hooks to adjust saved data.
				 */
                $wpcf->field->set( $post, $field );

                // TODO WPML move Check if repetitive field is copied using WPML
                if( ! empty( $original_cf['fields'] ) ) {
                    if( in_array( $wpcf->field->slug, $original_cf['fields'] ) ) {
                        // Switch to parent post
                        $wpcf->field->set( get_post( $original_cf['original_post_id'] ),
                            $field );
                    }
                }
                /*
				 * From Types 1.2 use complete form setup
				 */
                $fields_processed = $fields_processed + $wpcf->field->_get_meta_form();
            }
        }
    }

    // Cache results
    if ( $cache_key ) {
        $cache[$cache_key] = $fields_processed;
    }

    return $fields_processed;
}

/**
 * Processes single field.
 *
 * Since Types 1.2 this function changed. It handles single form element.
 * Form element is already fetched, also meta values using class WPCF_Field.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @todo gradually remove usage of inherited fields
 * @todo Cleanup
 *
 * @staticvar array $repetitive_started
 * @param type $field_object
 * @return mixed boolean|array
 */
function wpcf_admin_post_process_field( $field_object ) {

    /*
     * Since Types 1.2
     * All data we need is stored in global $wpcf
     */
    global $wpcf;
    $post = $wpcf->post;
    $field = (array) $field_object->cf;
    $context = $field_object->context;
    $invalid_fields = $wpcf->field->invalid_fields;

    if ( !empty( $field ) ) {
        /*
         * Set Unique ID
         */
        $field_id = 'wpcf-' . $field['type'] . '-' . $field['slug'] . '-'
                . wpcf_unique_id( serialize( $field_object->__current_form_element ) );

        /*
         * Get inherited field
         *
         * TODO Deprecated
         *
         * Since Types 1.2 we encourage developers to completely define fields.
         */
        $inherited_field_data = false;
        if ( isset( $field_object->config->inherited_field_type ) ) {
            $_allowed = array(
                'image' => 'file',
                'numeric' => 'textfield',
                'email' => 'textfield',
                'phone' => 'textfield',
                'url' => 'textfield',
            );
            if ( !array_key_exists( $field_object->cf['type'], $_allowed ) ) {
//                _deprecated_argument( 'inherited_field_type', '1.2',
//                        'Since Types 1.2 we encourage developers to completely define fields' );
            }
            $inherited_field_data = wpcf_fields_type_action( $field_object->config->inherited_field_type );
        }



        /*
         * CHECKPOINT
         * APPLY FILTERS
         *
         *
         * Moved to WPCF_Field
         * Field value should be already filtered
         *
         * Explanation:
         * When WPCF_Field::set() is called, all these properties are set.
         * WPCF_Field::$cf['value']
         * WPCF_Field::$__meta (single value from DB)
         * WPCF_Field::$meta (single or multiple values if single/repetitive)
         *
         * TODO Make sure value is already filtered and not overwritten
         */

        /*
         * Set generic values
         *
         * FUTURE BREAKPOINT
         * Since Types 1.2 we do not encourage relying on generic field data.
         * Only core fields should use this.
         *
         * TODO Open 3rd party fields dir
         */
        $_element = array(
            '#type' => isset( $field_object->config->inherited_field_type ) ? $field_object->config->inherited_field_type : $field['type'],
            '#id' => $field_id,
            '#title' => $field['name'],
            '#name' => 'wpcf[' . $field['slug'] . ']',
            '#value' => isset( $field['value'] ) ? $field['value'] : '',
            'wpcf-id' => $field['id'],
            'wpcf-slug' => $field['slug'],
            'wpcf-type' => $field['type'],
        );

        /*
         * TODO Add explanation about creating duplicated fields
         *
         * NOT USED YET
         *
         * Explain users that fields are added if slug is changed
         */
        wpcf_admin_add_js_settings( 'wpcfFieldNewInstanceWarning',
                __( 'If you change slug, new field will be created', 'wpcf' ) );

        /*
         * Merge with default element
         *
         * Deprecated from Types 1.2
         * Only core fields use this.
         */
        $element = array_merge( $_element, $field_object->__current_form_element );


        /*
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         *
         * TODO From this point code should be simplified.
         */

        if ( isset( $field['description_extra'] ) ) {
            $element['#description'] .= wpautop( $field['description_extra'] );
        }

        // Set atributes #1
        if ( isset( $field['disable'] ) ) {
            $field['#disable'] = $field['disable'];
        }
        if ( !empty( $field['disable'] ) ) {
            $field['#attributes']['disabled'] = 'disabled';
        }
        if ( !empty( $field['readonly'] ) ) {
            $field['#attributes']['readonly'] = 'readonly';
        }

        // Format description
        if ( !empty( $element['#description'] ) ) {
            $element['#description'] = wpautop( $element['#description'] );
        }

        // Set validation element
        if ( isset( $field['data']['validate'] ) ) {
            /*
             *
             *
             * TODO First two check are not needed anymore
             */
            // If array has more than one field - see which one is marked
            if ( $field_object->__multiple && isset( $element['#_validate_this'] ) ) {
                $element['#validate'] = $field['data']['validate'];
            } else if ( !$field_object->__multiple ) {
                $element['#validate'] = $field['data']['validate'];
            }
        }

        // Set atributes #2 (override)
        if ( isset( $field['disable'] ) ) {
            $element['#disable'] = $field['disable'];
        }
        if ( !empty( $field['disable'] ) ) {
            $element['#attributes']['disabled'] = 'disabled';
        }
        if ( !empty( $field['readonly'] ) ) {
            $element['#attributes']['readonly'] = 'readonly';
            if ( !empty( $element['#options'] ) ) {
                foreach ( $element['#options'] as $key => $option ) {
                    if ( !is_array( $option ) ) {
                        $element['#options'][$key] = array(
                            '#title' => $key,
                            '#value' => $option,
                        );
                    }
                    $element['#options'][$key]['#attributes']['readonly'] = 'readonly';
                    if ( $element['#type'] == 'select' ) {
                        $element['#options'][$key]['#attributes']['disabled'] = 'disabled';
                    }
                }
            }
            if ( $element['#type'] == 'select' ) {
                $element['#attributes']['disabled'] = 'disabled';
            }
        }

        // Check if it was invalid on submit and add error message
        if ( $post && !empty( $invalid_fields ) ) {
            if ( isset( $invalid_fields[$element['#id']]['#error'] ) ) {
                $element['#error'] = $invalid_fields[$element['#id']]['#error'];
            }
        }

        // TODO WPML move Set WPML locked icon
        if ( wpcf_wpml_field_is_copied( $field ) ) {
            $element['#title'] .= '<img src="' . WPCF_EMBEDDED_RES_RELPATH . '/images/locked.png" alt="'
                    . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'wpcf' ) . '" title="'
                    . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'wpcf' ) . '" style="position:relative;left:2px;top:2px;" />';
        }

        // Add repetitive class
        // TODO WPML move
        if ( types_is_repetitive( $field ) && $context != 'post_relationship' && wpcf_wpml_field_is_copied( $field ) ) {
            if ( !empty( $element['#options'] ) && $element['#type'] != 'select' ) {
                foreach ( $element['#options'] as $temp_key => $temp_value ) {
                    $element['#options'][$temp_key]['#attributes']['class'] = isset( $element['#attributes']['class'] ) ? $element['#attributes']['class'] . ' wpcf-repetitive' : 'wpcf-repetitive';
                }
            } else {
                $element['#attributes']['class'] = isset( $element['#attributes']['class'] ) ? $element['#attributes']['class'] . ' wpcf-repetitive' : 'wpcf-repetitive';
            }
            /*
             *
             *
             * Since Types 1.2 we allow same field values
             *
             * TODO Remove
             *
             * wpcf_admin_add_js_settings('wpcfFormRepetitiveUniqueValuesCheckText',
              '\'' . __('Warning: same values set', 'wpcf') . '\'');
             */
        }

        // Set read-only if copied by WPML
        // TODO WPML Move this to separate WPML code and use only hooks 1.1.5
        if ( wpcf_wpml_field_is_copied( $field ) ) {
            if ( isset( $element['#options'] ) ) {
                foreach ( $element['#options'] as $temp_key => $temp_value ) {
                    if ( isset( $temp_value['#attributes'] ) ) {
                        $element['#options'][$temp_key]['#attributes']['readonly'] = 'readonly';
                    } else {
                        $element['#options'][$temp_key]['#attributes'] = array('readonly' => 'readonly');
                    }
                }
            }
            if ( $field['type'] == 'select' ) {
                if ( isset( $element['#attributes'] ) ) {
                    $element['#attributes']['disabled'] = 'disabled';
                } else {
                    $element['#attributes'] = array('disabled' => 'disabled');
                }
            } else {
                if ( isset( $element['#attributes'] ) ) {
                    $element['#attributes']['readonly'] = 'readonly';
                } else {
                    $element['#attributes'] = array('readonly' => 'readonly');
                }
            }
        }

        // Final filter for disabled if readonly
        if ( isset( $element['#attributes']['readonly'] ) || isset( $element['#attributes']['disabled'] ) ) {
            if ( types_is_repetitive( $field ) ) {
                $element['#name'] .= '[]';
            }
            if ( $field['type'] == 'checkboxes' ) {
                if ( isset( $element['#options'] ) ) {
                    foreach ( $element['#options'] as $temp_key => $temp_value ) {
                        $value = isset( $temp_value['#default_value'] ) ? $temp_value['#default_value'] : $temp_value['#value'];
                        $_after = "<input type=\"hidden\" name=\"{$temp_value['#name']}\" value=\"{$value}\" />";
                        $temp_value['#after'] = isset( $temp_value['#after'] ) ? $temp_value['#after'] . $_after : $_after;
                        $temp_value['#name'] = "wpcf-disabled[{$field['id']}_{$temp_value['#id']}]";
                        $temp_value['#attributes']['disabled'] = 'disabled';
                        $element['#options'][$temp_key] = $temp_value;
                    }
                }
            } else if ( in_array( $element['#type'],
                            array('checkbox', 'checkboxes', 'radios') ) ) {
                if ( isset( $element['#options'] ) ) {
                    foreach ( $element['#options'] as $temp_key => $temp_value ) {
                        $element['#options'][$temp_key]['#attributes']['disabled'] = 'disabled';
                    }
                }
                $value = isset( $element['#default_value'] ) ? $element['#default_value'] : $element['#value'];
                $_after = "<input type=\"hidden\" name=\"{$element['#name']}\" value=\"{$value}\" />";
                $element['#after'] = isset( $element['#after'] ) ? $element['#after'] . $_after : $_after;
                $element['#attributes']['disabled'] = 'disabled';
                $element['#name'] = "wpcf-disabled[{$field['id']}_{$element['#id']}]";
            } else {
                $element['#attributes']['disabled'] = 'disabled';
                if ( is_array( $element['#value'] ) ) {//$field['type'] == 'skype' ) {
                    $element['#value'] = array_shift( $element['#value'] );
                }
                $value = htmlentities( $element['#value'] );
                $_after = "<input type=\"hidden\" name=\"{$element['#name']}\" value=\"{$value}\" />";
                $element['#after'] = isset( $element['#after'] ) ? $element['#after'] . $_after : $_after;
                $element['#name'] = "wpcf-disabled[{$field['id']}_{$element['#id']}]";
            }
        }
        return array('field' => $field, 'element' => $element);
    }
    return false;
}

/**
 * Gets all groups and fields for post.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @param type $post_ID
 * @return type
 */
function wpcf_admin_post_get_post_groups_fields( $post = false, $context = 'group' )
{
    // Get post_type
    /*
     *
     *
     * Since WP 3.5 | Types 1.2
     * Looks like $post is altered with get_post_type()
     * We do not want that if post is already set
     */
    if ( !empty( $post->post_type ) ) {
        $post_type = $post->post_type;
    } else if ( !empty( $post ) ) {
        $post_type = get_post_type( $post );
    } else {
        if ( !isset( $_GET['post_type'] ) ) {
            $post_type = 'post';
        } else if ( in_array( $_GET['post_type'], get_post_types( array('show_ui' => true) ) ) ) {
            $post_type = sanitize_text_field( $_GET['post_type'] );
        } else {
            $post_type = 'post';
        }
    }

    // Get post terms
    $support_terms = false;
    if ( !empty( $post ) ) {
        $post->_wpcf_post_terms = array();
        $taxonomies = get_taxonomies( '', 'objects' );
        if ( !empty( $taxonomies ) ) {
            foreach ( $taxonomies as $tax_slug => $tax ) {
                $temp_tax = get_taxonomy( $tax_slug );
                if ( !in_array( $post_type, $temp_tax->object_type ) ) {
                    continue;
                }
                $support_terms = true;
                $terms = wp_get_post_terms( $post->ID, $tax_slug,
                        array('fields' => 'all') );
                foreach ( $terms as $term_id ) {
                    $post->_wpcf_post_terms[] = $term_id->term_taxonomy_id;
                }
            }
        }
    }

    // Get post template
    if ( empty( $post ) ) {
        $post = new stdClass();
        $post->_wpcf_post_template = false;
        $post->_wpcf_post_views_template = false;
    } else {
        $post->_wpcf_post_template = get_post_meta( $post->ID, '_wp_page_template', true );
        $post->_wpcf_post_views_template = get_post_meta( $post->ID, '_views_template', true );
    }

    if ( empty( $post->_wpcf_post_terms ) ) {
        $post->_wpcf_post_terms = array();
    }

    // Filter groups
    $groups = array();
    $groups_all = apply_filters( 'wpcf_post_groups_all', wpcf_admin_fields_get_groups(), $post, $context );
    foreach ( $groups_all as $temp_key => $temp_group ) {
        if ( empty( $temp_group['is_active'] ) ) {
            unset( $groups_all[$temp_key] );
            continue;
        }
        // Get filters
        // Post Types
        $groups_all[$temp_key]['_wp_types_group_post_types'] = explode( ',',
                trim( get_post_meta( $temp_group['id'],
                                '_wp_types_group_post_types', true ), ',' ) );

        // Taxonomies
        $groups_all[$temp_key]['_wp_types_group_terms'] = explode( ',',
                trim( get_post_meta( $temp_group['id'], '_wp_types_group_terms',
                                true ), ',' ) );

        // Templates
        $groups_all[$temp_key]['_wp_types_group_templates'] = explode( ',',
                trim( get_post_meta( $temp_group['id'],
                                '_wp_types_group_templates', true ), ',' ) );

        // Data-Dependant
        $groups_all[$temp_key]['_wpcf_conditional_display'] =
            get_post_meta( $temp_group['id'], '_wpcf_conditional_display' );

        $post_type_filter = $groups_all[$temp_key]['_wp_types_group_post_types'][0] == 'all'
                            || empty( $groups_all[$temp_key]['_wp_types_group_post_types'][0] )
            ? -1
            : 0;
        $taxonomy_filter = $groups_all[$temp_key]['_wp_types_group_terms'][0] == 'all'
                            || empty( $groups_all[$temp_key]['_wp_types_group_terms'][0] )
            ? -1
            : 0;
        $template_filter = $groups_all[$temp_key]['_wp_types_group_templates'][0] == 'all'
                           || empty( $groups_all[$temp_key]['_wp_types_group_templates'][0] )
            ? -1
            : 0;

        $groups_all[$temp_key] = apply_filters( 'wpcf_post_group_filter_settings', $groups_all[$temp_key], $post, $context, $post->_wpcf_post_terms );

        // See if post type matches
        if ( $post_type_filter == 0 && in_array( $post_type,
                $groups_all[$temp_key]['_wp_types_group_post_types'] ) ) {
            $post_type_filter = 1;
        }

        // See if terms match
        if ( $taxonomy_filter == 0 ) {
            foreach ( $post->_wpcf_post_terms as $temp_post_term ) {
                if ( in_array( $temp_post_term,
                    $groups_all[$temp_key]['_wp_types_group_terms'] ) ) {
                    $taxonomy_filter = 1;
                }
            }
        }

        // See if template match
        if ( $template_filter == 0 ) {
            if ( (!empty( $post->_wpcf_post_template ) && in_array( $post->_wpcf_post_template,
                        $groups_all[$temp_key]['_wp_types_group_templates'] )) || (!empty( $post->_wpcf_post_views_template ) && in_array( $post->_wpcf_post_views_template,
                        $groups_all[$temp_key]['_wp_types_group_templates'] )) ) {
                $template_filter = 1;
            }
        }

        /**
         * if ALL must met
         */
        if( isset( $groups_all[$temp_key]['filters_association'] )
            && $groups_all[$temp_key]['filters_association'] == 'all' ) {

            // if no conditions are set, do not display the group
            // - this is important because ounce filter association is set to "all"
            //   it's not unset even if the user removes all conditions
            if( $post_type_filter == 0
                && $template_filter == 0
                && $taxonomy_filter == -1
                && empty( $groups_all[$temp_key]['_wpcf_conditional_display'] ) ) {
                unset( $groups_all[$temp_key] );

            // there are conditions set
            } else {
                // post types
                // accepted if none isset || one has to match (proofed above)
                if( empty( $groups_all[$temp_key]['_wp_types_group_post_types'][0] ) )
                    $post_type_filter = 1;

                // taxonomies
                // none isset || all have to match
                if( empty( $groups_all[$temp_key]['_wp_types_group_terms'][0] )
                    || $groups_all[$temp_key]['_wp_types_group_terms'][0] == 'all' ) {
                    $taxonomy_filter = 1;
                } else {
                    $taxonomy_filter = 1;
                    // check all terms which need to be active
                    foreach ( $groups_all[$temp_key]['_wp_types_group_terms'] as $term_has_to_match ) {
                        // break on first term not active
                        if( ! in_array( $term_has_to_match, $post->_wpcf_post_terms ) ) {
                            $taxonomy_filter = 0;
                            break;
                        }
                    }
                }

                // templates
                // one has to match  (proofed above) || none isset
                if( empty( $groups_all[$temp_key]['_wp_types_group_templates'][0] ) )
                    $template_filter = 1;
            }
        }

        // Filter by association
        if ( empty( $groups_all[$temp_key]['filters_association'] ) ) {
            $groups_all[$temp_key]['filters_association'] = 'any';
        }
        // If context is post_relationship allow all groups that match post type
        if ( $context == 'post_relationships_header' ) {
            $groups_all[$temp_key]['filters_association'] = 'any';
        }

        if ( $post_type_filter == -1 && $taxonomy_filter == -1 && $template_filter == -1 ) {
            $passed = 1;
        } else if ( $groups_all[$temp_key]['filters_association'] == 'any' ) {
            $passed = $post_type_filter == 1 || $taxonomy_filter == 1 || $template_filter == 1;
        } else {
            $passed = $post_type_filter != 0 && $taxonomy_filter != 0 && $template_filter != 0;
        }
        if ( !$passed ) {
            unset( $groups_all[$temp_key] );
        } else {
            $groups_all[$temp_key]['fields'] = wpcf_admin_fields_get_fields_by_group( $temp_group['id'],
                    'slug', true, false, true );
        }
    }
    $groups = apply_filters( 'wpcf_post_groups', $groups_all, $post, $context );
    return $groups;
}

/**
 * Stores fields for editor menu.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @staticvar array $fields
 * @param type $field
 * @return array
 */
function wpcf_admin_post_add_to_editor( $field ) {
    static $fields = array();
    if ( $field == 'get' ) {
        return $fields;
    }
    if (
		empty( $fields )
		&& ! (
			apply_filters( 'toolset_is_views_available', false )
			|| apply_filters( 'toolset_is_views_embedded_available', false )
		)
	) {
        add_action( 'admin_enqueue_scripts', 'wpcf_admin_post_add_to_editor_js' );
    }
    $fields[$field['id']] = $field;
}

/**
 * Renders JS for editor menu.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @return type
 */
function wpcf_admin_post_add_to_editor_js() {

    // since 1.3 we do not use global $post
    $post = wpcf_admin_get_edited_post();
    if ( empty( $post ) ) {
        $post = (object) array('ID' => -1);
        $post->post_type = false;
    }

    $fields = wpcf_admin_post_add_to_editor( 'get' );
    $groups = wpcf_admin_post_get_post_groups_fields( $post );


    if ( empty( $fields ) || empty( $groups ) ) {
        /**
         * check user fields too, but only when CF are empty
         */
        include_once dirname(__FILE__).'/usermeta-post.php';
        $groups = wpcf_admin_usermeta_get_groups_fields();
        if ( empty( $groups ) ) {
            return false;
        }
    }
    $editor_addon = new Editor_addon( 'types',
            __( 'Insert Types Shortcode', 'wpcf' ),
            WPCF_EMBEDDED_RES_RELPATH . '/js/types_editor_plugin.js',
            '', true, 'icon-types-logo ont-icon-18 ont-color-gray' );

    foreach ( $groups as $group ) {
        if ( empty( $group['fields'] ) ) {
            continue;
        }
        foreach ( $group['fields'] as $group_field_id => $group_field ) {
            if ( !isset( $fields[$group_field_id] ) ) {
                continue;
            }
            $field = $fields[$group_field_id];
            $callback = 'wpcfFieldsEditorCallback(\'' . $field['id']
                    . '\', \'postmeta\', ' . $post->ID . ')';

            $editor_addon->add_insert_shortcode_menu( stripslashes( $field['name'] ),
                    trim( wpcf_fields_get_shortcode( $field ), '[]' ),
                    $group['name'], $callback );
        }
    }
}

/**
 * Adds items to view dropdown.
 *
 * Core function. Works and stable. Do not move or change.
 * If required, add hooks only.
 *
 * @todo Remove (to WPCF_WPViews)
 *
 * @param type $menu
 * @return type
 */
function wpcf_admin_post_editor_addon_menus_filter( $menu ) {

    global $wpcf;

    $post = wpcf_admin_get_edited_post();
    if ( empty( $post ) ) {
        $post = (object) array('ID' => -1);
    }

    $groups = wpcf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'group_active' );
    $all_post_types = implode( ' ', get_post_types( array('public' => true) ) );
    $add = array();
    if ( !empty( $groups ) ) {
        // $group_id is blank therefore not equal to $group['id']
        // use array for item key and CSS class
        $item_styles = array();

        foreach ( $groups as $group_id => $group ) {
            $fields = wpcf_admin_fields_get_fields_by_group( $group['id'],
                    'slug', true, false, true );
            if ( !empty( $fields ) ) {
                // code from Types used here without breaking the flow
                // get post types list for every group or apply all
                $post_types = get_post_meta( $group['id'],
                        '_wp_types_group_post_types', true );
                if ( $post_types == 'all' ) {
                    $post_types = $all_post_types;
                }
                $post_types = trim( str_replace( ',', ' ', $post_types ) );
                $item_styles[$group['name']] = $post_types;

                foreach ( $fields as $field_id => $field ) {

                    // Use field class
                    $wpcf->field->set( $wpcf->post, $field );

                    // Get field data
                    $data = (array) $wpcf->field->config;
                    // Get inherited field
                    if ( isset( $data['inherited_field_type'] ) ) {
                        $inherited_field_data = wpcf_fields_type_action( $data['inherited_field_type'] );
                    }

                    $callback = 'wpcfFieldsEditorCallback(\'' . $field['id']
                            . '\', \'postmeta\', ' . $post->ID . ')';

                    $menu[$group['name']][stripslashes( $field['name'] )] = array(
                        stripslashes( $field['name'] ), trim( wpcf_fields_get_shortcode( $field ),
                                '[]' ), $group['name'], $callback);

                    /*
                     * Since Types 1.2
                     * We use field class to enqueue JS and CSS
                     */
                    $wpcf->field->enqueue_script();
                    $wpcf->field->enqueue_style();
                }
            }
        }
    }

    return $menu;
}

function wpcf_admin_post_marketing_displaying_custom_content() {
    $displaying_custom_content = include( WPCF_ABSPATH . '/marketing/displaying-custom-content/title-content.php' );
    echo $displaying_custom_content['content'];
}

function wpcf_post_preview_warning() {
    $post = wpcf_admin_get_edited_post();
    // Add preview warning
    if ( isset( $post->post_status ) && !in_array( $post->post_status,
                    array('auto-draft', 'draft') ) && !in_array( $post->post_type,
                    array('cred', 'view', 'view-template') ) ) {
        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script( 'wp-pointer' );

        ?><script type="text/javascript">
            if ( "undefined" != typeof typesPostScreen ) {
                typesPostScreen.previewWarning(
                    '<?php esc_attr_e( 'Preview warning', 'wpcf' ); ?>',
                    '<?php echo esc_attr( sprintf( __( 'Custom field changes cannot be previewed until %s is updated', 'wpcf' ), $post->post_type ) ); ?>');
            }
</script><?php
    }
}

/**
 * Create fake post object.
 *
 * Create fake post object if in system is no post.
 *
 * @param mixed $post
 * @return object
 *
 */
function wpcf_admin_create_fake_post_if_need($post)
{
    if ( !is_object( $post ) ) {
        $post = new stdClass();
        $post->post_title = 'Lorem Ipsum';
        $post->ID = 0;
    }
    return $post;
}
