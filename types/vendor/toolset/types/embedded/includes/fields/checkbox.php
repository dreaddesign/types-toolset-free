<?php
/*
    IMPORTANT NOTE

	Some checkboxes-related functionality is - for historical reasons - shared with checkbox fields in a
	very unclear way. It's defined in this file.

	That is the reason (at least one of them) why this file needs to be included on every request.
*/

/**
 * Register data (called automatically).
 *
 * @return array
 */
function wpcf_fields_checkbox()
{
    return array(
        'id' => 'wpcf-checkbox',
        'title' => __( 'Checkbox', 'wpcf' ),
        'description' => __( 'Checkbox', 'wpcf' ),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'meta_key_type' => 'BINARY',
        'font-awesome' => 'check-square-o',
    );
}


// We call this function late and wherever we *think* it might be necessary
// to fix any potential mess created by other legacy code.
//
// wpcf_fields_checkbox_save_check() is the final authority when it comes to saving checkboxes
// field values.
add_action( 'save_post', 'wpcf_fields_checkbox_save_check', 100, 1 );
add_action( 'wpcf_relationship_save_child', 'wpcf_fields_checkbox_save_check', 100, 1 );


add_action( 'edit_attachment', 'wpcf_fields_checkbox_save_check', 15, 1 );

/**
 * Form data for post edit page.
 *
 * @param $field
 * @param $field_object
 *
 * @return array
 * @deprecated seems
 */
function wpcf_fields_checkbox_meta_box_form($field, $field_object)
{
    global $wpcf;
    $checked = false;

    /**
     * sanitize set_value
     */
    if ( array_key_exists('set_value', $field['data'] ) ) {
        $field['data']['set_value'] = stripslashes( $field['data']['set_value'] );
    } else {
        $field['data']['set_value'] = null;
    }

    if ( $field['value'] == $field['data']['set_value'] ) {
        $checked = true;
    }
    // If post is new check if it's checked by default
    global $pagenow;
    if ( $pagenow == 'post-new.php' && !empty( $field['data']['checked'] ) ) {
        $checked = true;
    }
    // This means post is new
    if ( !isset( $field_object->post->ID ) ) {
        $field_object->post = (object) array('ID' => 0);
    }
    return array(
        '#type' => 'checkbox',
        '#value' => esc_attr($field['data']['set_value']),
        '#default_value' => $checked,
        '#after' => '<input type="hidden" name="_wpcf_check_checkbox['
        . esc_attr($field_object->post->ID) . '][' . esc_attr($field_object->slug)
        . ']" value="1" />',
    );
}

/**
 * Editor callback form.
 */
function wpcf_fields_checkbox_editor_callback($field, $settings)
{
    $value_not_selected = '';
    $value_selected = '';

    if ( isset( $field['data']['display_value_not_selected'] ) ) {
        $value_not_selected = $field['data']['display_value_not_selected'];
    }
    if ( isset( $field['data']['display_value_selected'] ) ) {
        $value_selected = $field['data']['display_value_selected'];
    }

    $data = array_merge( array(
        'selected' => WPCF_Editor::sanitizeParams( $value_selected ),
        'not_selected' => WPCF_Editor::sanitizeParams( $value_not_selected ),
            ), $settings );

    return array(
        'supports' => array('style'),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'wpcf' ),
                'title' => __( 'Display options for this field:', 'wpcf' ),
                'content' => WPCF_Loader::template( 'editor-modal-checkbox',
                        $data ),
            )
        ),
    );
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_checkbox_editor_submit($data, $field, $context)
{
    $add = '';
    if ( $context == 'usermeta' ) {
        $add .= wpcf_get_usermeta_form_addon_submit();
    } elseif ( $context == 'termmeta' ) {
        $add .= wpcf_get_termmeta_form_addon_submit();
	}

    if ( isset($data['display']) && $data['display'] == 'value' ) {

        $checked_add = $add . ' state="checked"';
        $unchecked_add = $add . ' state="unchecked"';

        if ( $context == 'usermeta' ) {
            $shortcode_checked = wpcf_usermeta_get_shortcode( $field,
                    $checked_add, $data['selected'] );
            $shortcode_unchecked = wpcf_usermeta_get_shortcode( $field,
                    $unchecked_add, $data['not_selected'] );
		} else if ( $context == 'termmeta' ) {
			$shortcode_checked = wpcf_termmeta_get_shortcode( $field,
                    $checked_add, $data['selected'] );
            $shortcode_unchecked = wpcf_termmeta_get_shortcode( $field,
                    $unchecked_add, $data['not_selected'] );
        } else {
            $shortcode_checked = wpcf_fields_get_shortcode( $field,
                    $checked_add, $data['selected'] );
            $shortcode_unchecked = wpcf_fields_get_shortcode( $field,
                    $unchecked_add, $data['not_selected'] );
        }
        $shortcode = $shortcode_checked . $shortcode_unchecked;
    } else {
        if ( $context == 'usermeta' ) {
            $shortcode = wpcf_usermeta_get_shortcode( $field, $add );
		} else if ( $context == 'termmeta' ) {
			$shortcode = wpcf_termmeta_get_shortcode( $field, $add );
        } else {
            $shortcode = wpcf_fields_get_shortcode( $field, $add );
        }
    }

    return $shortcode;

}

/**
 * View function.
 *
 * @param type $params
 */
function wpcf_fields_checkbox_view($params)
{
    $output = '';
    $option_name = 'wpcf-fields';
    if ( isset( $params['usermeta'] ) && ! empty( $params['usermeta'] ) ) {
        $option_name = 'wpcf-usermeta';
    } else if ( isset( $params['termmeta'] ) && ! empty( $params['termmeta'] ) ) {
        $option_name = 'wpcf-termmeta';
    }
    if ( isset( $params['option_name'] ) ) {
        $option_name = $params['option_name'];
    }
    if ( isset( $params['state'] )
            && $params['state'] == 'unchecked'
            && empty( $params['field_value'] ) ) {
        if ( empty( $params['#content'] ) ) {
            return '__wpcf_skip_empty';
        }
        return htmlspecialchars_decode( $params['#content'] );
    } elseif ( isset( $params['state'] ) && $params['state'] == 'unchecked' ) {
        return '__wpcf_skip_empty';
    }

    if ( isset( $params['state'] ) && $params['state'] == 'checked' && !empty( $params['field_value'] ) ) {
        if ( empty( $params['#content'] ) ) {
            return '__wpcf_skip_empty';
        }
        return htmlspecialchars_decode( $params['#content'] );
    } elseif ( isset( $params['state'] ) && $params['state'] == 'checked' ) {
        return '__wpcf_skip_empty';
    }
    if ( !empty( $params['#content'] )
            && !empty( $params['field_value'] ) ) {
        return htmlspecialchars_decode( $params['#content'] );
    }

    // Check if 'save_empty' is yes and if value is 0 - set value to empty string
    if (
        isset( $params['field']['data']['save_empty'] )
        && $params['field']['data']['save_empty'] == 'yes'
        && $params['field_value'] == '0'
        && 'db' != $params['field']['data']['display']
    ) {
        $params['field_value'] = '';
    }

    if (
        'db' == $params['field']['data']['display']
        && $params['field_value'] != ''
    ) {
		// We need to translate here because the stored value is on the original language
		// When updaing the value in the Field group, we might have problems
        $output = $params['field_value'];
        // Show the translated value if we have one.
        $field = wpcf_fields_get_field_by_slug( $params['field']['slug'], $option_name );

        if( is_array( $field ) && isset( $field['id'] ) ) {
            $output = wpcf_translate( 'field ' . $field['id'] . ' checkbox value', $output );
        }
    } elseif ( $params['field']['data']['display'] == 'value'
            && $params['field_value'] != '' ) {
        if ( !empty( $params['field']['data']['display_value_selected'] ) ) {
			// We need to translate here because the stored value is on the original language
			// When updaing the value in the Field group, we might have problems
            $output = $params['field']['data']['display_value_selected'];
            $output = wpcf_translate( 'field ' . $params['field']['id'] . ' checkbox value selected', $output );
        }
    } elseif ( $params['field']['data']['display'] == 'value'
        && !empty( $params['field']['data']['display_value_not_selected'] ) ) {
		// We need to translate here because the stored value is on the original language
		// When updaing the value in the Field group, we might have problems
        $output = $params['field']['data']['display_value_not_selected'];
        $output = wpcf_translate( 'field ' . $params['field']['id'] . ' checkbox value not selected', $output );
    } else {
        return '__wpcf_skip_empty';
    }

    return $output;
}

/**
 * Check if checkbox is submitted.
 *
 * Currently used on Relationship saving.
 *
 * @param int|WP_Post $post_source
 */
function wpcf_fields_checkbox_save_check( $post_source )
{
	if( $post_source instanceof WP_Post ) {
		$post_id = (int) $post_source->ID;
	} else {
		$post_id = (int) $post_source;
	}

    $meta_to_unset = array();
    $meta_to_unset[$post_id] = array();
    $cf = new WPCF_Field();

    /* We have several calls on this:
     * 1. Saving post with Update
     * 2. Saving all children
     * 3. Saving child
     */

    $mode = 'save_main';
    if ( defined( 'DOING_AJAX' ) && isset( $_GET['wpcf_action']) ) {
        switch ( $_GET['wpcf_action']) {
        case 'pr_save_all':
            $mode = 'save_all';
            break;
        case 'pr_save_child_post':
            $mode = 'save_child';
            break;
        }
    }

    if( apply_filters( 'types_updating_child_post', false ) ) {
    	$mode = 'save_child';
    }

    // Update edited post's checkboxes
    switch( $mode ) {
    case 'save_main':
        if( isset($_POST['_wptoolset_checkbox']) ){
            foreach ( array_keys( $_POST['_wptoolset_checkbox'] ) as $slug ) {
                if ( array_key_exists( 'wpcf', $_POST ) ) {
                    wpcf_fields_checkbox_update_one( $post_id, $slug, $_POST['wpcf'] );
                } else {
                    $slug_without_form = preg_replace( '/cred_form_\d+_\d+_/', '', $slug);
                    wpcf_fields_checkbox_update_one( $post_id, $slug_without_form, $_POST );
                }
            }
        }
        return;
    case 'save_child':
    case 'save_all':
        if ( !array_key_exists('_wptoolset_checkbox', $_POST) ) {
            break;
        }
        foreach(array_keys($_POST['wpcf_post_relationship']) as $post_id) {
            /**
             * sanitize and check variable
             */
            $post_id = intval($post_id);
            if (0==$post_id) {
                continue;
            }
            /**
             * stop if do not exist arary key
             */
            if ( !array_key_exists($post_id, $_POST['wpcf_post_relationship']) ) {
                continue;
            }
            /**
             * stop if array is empty
             */
            if (!count($_POST['wpcf_post_relationship'][$post_id])) {
                continue;
            }
            /**
             * prepare children id
             */
            $children = array();
            foreach(array_keys($_POST['wpcf_post_relationship'][$post_id]) as $child_id) {
                $children[] = $child_id;
            }
            $re = sprintf('/\-(%s)$/', implode('|', $children));
            $checkboxes = array();
            foreach(array_keys($_POST['_wptoolset_checkbox']) as $key) {
            	// make sure to only collect checkboxes which are assigned to the children post type
            	if( preg_match( $re, $key ) ) {
		            $checkboxes[] = preg_replace($re, '', $key);
	            }

            }
            foreach( $children as $child_id ) {
                foreach( array_unique($checkboxes) as $slug ) {
                    wpcf_fields_checkbox_update_one($child_id, $slug, $_POST['wpcf_post_relationship'][$post_id][$child_id]);
                }
            }
        }
        break;
    }

    // See if any marked for checking
    if ( isset( $_POST['_wpcf_check_checkbox'] ) ) {

        // Loop and search in $_POST
        foreach ( $_POST['_wpcf_check_checkbox'] as $child_id => $slugs ) {
            foreach ( $slugs as $slug => $true ) {

                $cf->set( $child_id, $cf->__get_slug_no_prefix( $slug ) );

                // First check main post
                if ( $mode == 'save_main'
                        && intval( $child_id ) == wpcf_get_post_id() ) {
                    if ( !isset( $_POST['wpcf'][$cf->cf['slug']] ) ) {
                        $meta_to_unset[intval( $child_id )][$cf->slug] = true;
                    }
                    continue;
                }

                // If new post
                if ( $mode == 'save_main' && $child_id == 0 ) {
                    if ( !isset( $_POST['wpcf'][$cf->cf['slug']] ) ) {
                        $meta_to_unset[$post_id][$cf->slug] = true;
                    }
                    continue;
                }
                /**
                 * Relationship check
                 */
                if ( $mode == 'save_main' ) {
                    if ( !isset( $_POST['wpcf'][$cf->cf['slug']] ) ) {
                        $meta_to_unset[$post_id][$cf->slug] = true;
                    }
                    continue;
                } elseif ( !empty( $_POST['wpcf_post_relationship'] ) ) {
                    foreach ( $_POST['wpcf_post_relationship'] as $_parent => $_children ) {
                        foreach ( $_children as $_child_id => $_slugs ) {
                            if ( !isset( $_slugs[$slug] ) ) {
                                $meta_to_unset[$_child_id][$cf->slug] = true;
                            }
                        }
                    }
                }
            }
        }
    }

    // After collected - delete them
    foreach ( $meta_to_unset as $child_id => $slugs ) {
        foreach ( $slugs as $slug => $true ) {
            $cf->set( $child_id, $cf->__get_slug_no_prefix( $slug ) );
            if ( $cf->cf['data']['save_empty'] != 'no' ) {
                update_post_meta( $child_id, $slug, 0 );
            } else {
                delete_post_meta( $child_id, $slug );
            }
        }
    }
}

function wpcf_fields_checkbox_update_one($post_id, $slug, $array_to_check) {
	$cf = new WPCF_Field();
	$field_slug = $cf->__get_slug_no_prefix( $slug );

	$cf->set( $post_id, $field_slug );

	// Abort if the field doesn't exist.
	if ( ! array_key_exists( 'data', $cf->cf ) ) {
		return;
	}

	if ( 'checkbox' == $cf->cf['type'] ) {
		if ( array_key_exists( $field_slug, $array_to_check ) || array_key_exists( $slug, $array_to_check ) ) {

			update_post_meta( $post_id, $slug, $cf->cf['data']['set_value'] );
			return;
		}

		$cf->set( $post_id, $field_slug );

		if ( $cf->cf['data']['save_empty'] != 'no' ) {
			update_post_meta( $post_id, $cf->slug, 0 );
		} else {
			delete_post_meta( $post_id, $cf->slug );
		}

	} else if ( 'checkboxes' == $cf->cf['type'] ) {
		wpcf_update_checkboxes_field( $cf->cf, 'post', $post_id, $array_to_check );
	}
}


/**
 * This actually handles saving checkboxes field properly from Types, overwriting the default method
 * WPCF_Field::save().
 *
 * It (finally) respects the "save_empty" field option properly.
 * If a non-checkboxes field is passed, nothing happens.
 *
 * @param array $field_definition_array Checkboxes field definition array, basic keys are assumed.
 * @param string $meta_type 'post'|'user'|'term'
 * @param int $object_id ID of an existing post that is to be updated.
 * @param array $wpcf_form_data Form data, usually coming from $_POST['wpcf'].
 *
 * @since 2.2.7
 */
function wpcf_update_checkboxes_field( $field_definition_array, $meta_type, $object_id, $wpcf_form_data ) {

	if( ! in_array( $meta_type, array( 'post', 'user', 'term ') ) ) {
		return;
	}

	if( 'checkboxes' != wpcf_getarr( $field_definition_array, 'type' ) ) {
		return;
	}

	// We'll save an empty array if there's nothing else to save (done for historical reasons).
	$meta_value = array();

	$field_options = wpcf_getnest( $field_definition_array, array( 'data', 'options' ), null );
	$is_updating_types_child = apply_filters( 'types_updating_child_post', false );

	if( is_array( $field_options ) ) {
		// When saving a legacy child post, meta keys are used instead of a field slug.
		$field_id_key = ( $is_updating_types_child ? 'meta_key' : 'id' );

		$field_id = wpcf_getarr( $field_definition_array, $field_id_key );
		$save_zero_if_empty = ( 'yes' == wpcf_getnest( $field_definition_array, array( 'data', 'save_empty' ) ) );

		foreach( $field_options as $option_id => $option_settings ) {
			$is_option_checked = isset( $wpcf_form_data[ $field_id ][ $option_id ] );

			if( $is_option_checked ) {
				// Use actual option value coming from the form submission.
				$option_value = $wpcf_form_data[ $field_id ][ $option_id ];

				// When saving a legacy child post, the value may not be encapsulated in the array,
				// how it is supposed to be.
				if( $is_updating_types_child && ! is_array( $option_value ) ) {
					$option_value = array( $option_value );
				}
				$meta_value[ $option_id ] = $option_value;

			} elseif( $save_zero_if_empty ) {
				// Beware, "zero if empty" value is stored as-is, without the encapsulating array,
				// unlike any other values.
				$meta_value[ $option_id ] = 0;
			}

			// Otherwise, skip the key
		}
	}

	update_metadata( $meta_type, $object_id, wpcf_getarr( $field_definition_array, 'meta_key' ), $meta_value );
}

