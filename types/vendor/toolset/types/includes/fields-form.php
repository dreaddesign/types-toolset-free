<?php
/*
 * Fields and groups form functions.
 *
 *
 */
require_once WPCF_EMBEDDED_ABSPATH . '/classes/validate.php';
require_once WPCF_ABSPATH . '/includes/conditional-display.php';

global $wp_version;
$wpcf_button_style = '';
$wpcf_button_style30 = '';

if ( version_compare( $wp_version, '3.5', '<' ) ) {
    $wpcf_button_style = 'style="line-height: 35px;"';
    $wpcf_button_style30 = 'style="line-height: 30px;"';
}

/**
 * Generates form data.
 * 
 * @deprecated Possibly deprecated, no usage found in Types. Possibly identical code in Types_Admin_Edit_Fields::get_field_form_data()
 */
function wpcf_admin_fields_form()
{
    /**
     * include common functions
     */
    include_once dirname(__FILE__).'/common-functions.php';

    wpcf_admin_add_js_settings( 'wpcf_nonce_toggle_group',
            '\'' . wp_create_nonce( 'group_form_collapsed' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcf_nonce_toggle_fieldset',
            '\'' . wp_create_nonce( 'form_fieldset_toggle' ) . '\'' );
    $default = array();

    global $wpcf_button_style;
    global $wpcf_button_style30;

    global $wpcf;


    $form = array();


    $form['#form']['callback'] = array('wpcf_admin_save_fields_groups_submit');

    // Form sidebars

    if ( $current_user_can_edit ) {

        $form['open-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="wpcf-form-fields-align-right">',
        );
        // Set help icon
        $form['help-icon'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="wpcf-admin-fields-help"><img src="' . WPCF_EMBEDDED_TOOLSET_RELPATH
            . '/toolset-common/res/images/question.png" style="position:relative;top:2px;" />&nbsp;<a href="' . Types_Helper_Url::get_url( 'using-post-fields', 'fields-editor', 'fields-help', Types_Helper_Url::UTM_MEDIUM_HELP ) . '" target="_blank">'
            . __( 'Custom fields help', 'wpcf' ) . '</a></div>',
            );
        $form['submit2'] = array(
            '#type' => 'submit',
            '#name' => 'save',
            '#value' => __( 'Save', 'wpcf' ),
            '#attributes' => array('class' => 'button-primary wpcf-disabled-on-submit'),
        );
        $form['fields'] = array(
            '#type' => 'fieldset',
            '#title' => __( 'Available fields', 'wpcf' ),
        );

        // Get field types
        $fields_registered = wpcf_admin_fields_get_available_types();
        foreach ( $fields_registered as $filename => $data ) {
            $form['fields'][basename( $filename, '.php' )] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url( 'admin-ajax.php'
                . '?action=wpcf_ajax&amp;wpcf_action=fields_insert'
                . '&amp;field=' . basename( $filename, '.php' )
                . '&amp;page=wpcf-edit' )
                . '&amp;_wpnonce=' . wp_create_nonce( 'fields_insert' ) . '" '
                . 'class="wpcf-fields-add-ajax-link button-secondary">' . $data['title'] . '</a> ',
            );
            // Process JS
            if ( !empty( $data['group_form_js'] ) ) {
                foreach ( $data['group_form_js'] as $handle => $script ) {
                    if ( isset( $script['inline'] ) ) {
                        add_action( 'admin_footer', $script['inline'] );
                        continue;
                    }
                    $deps = !empty( $script['deps'] ) ? $script['deps'] : array();
                    $in_footer = !empty( $script['in_footer'] ) ? $script['in_footer'] : false;
                    wp_register_script( $handle, $script['src'], $deps,
                        WPCF_VERSION, $in_footer );
                    wp_enqueue_script( $handle );
                }
            }

            // Process CSS
            if ( !empty( $data['group_form_css'] ) ) {
                foreach ( $data['group_form_css'] as $handle => $script ) {
                    if ( isset( $script['src'] ) ) {
                        $deps = !empty( $script['deps'] ) ? $script['deps'] : array();
                        wp_enqueue_style( $handle, $script['src'], $deps,
                            WPCF_VERSION );
                    } else if ( isset( $script['inline'] ) ) {
                        add_action( 'admin_head', $script['inline'] );
                    }
                }
            }
        }

        // Get fields created by user
        $fields = wpcf_admin_fields_get_fields( true, true );
        if ( !empty( $fields ) ) {
            $form['fields-existing'] = array(
                '#type' => 'fieldset',
                '#title' => __( 'User created fields', 'wpcf' ),
                '#id' => 'wpcf-form-groups-user-fields',
            );
            foreach ( $fields as $key => $field ) {
                if ( isset( $update['fields'] ) && array_key_exists( $key,
                    $update['fields'] ) ) {
                        continue;
                    }
                if ( !empty( $field['data']['removed_from_history'] ) ) {
                    continue;
                }
                $form['fields-existing'][$key] = array(
                    '#type' => 'markup',
                    '#markup' => '<div id="wpcf-user-created-fields-wrapper-' . $field['id'] . '" style="float:left; margin-right: 10px;"><a href="' . admin_url( 'admin-ajax.php'
                    . '?action=wpcf_ajax'
                    . '&amp;wpcf_action=fields_insert_existing'
                    . '&amp;page=wpcf-edit'
                    . '&amp;field=' . $field['id'] ) . '&amp;_wpnonce='
                    . wp_create_nonce( 'fields_insert_existing' ) . '" '
                    . 'class="wpcf-fields-add-ajax-link button-secondary" onclick="jQuery(this).parent().fadeOut();" '
                    . ' data-slug="' . $field['id'] . '">'
                    . htmlspecialchars( stripslashes( $field['name'] ) ) . '</a>'
                    . '<a href="' . admin_url( 'admin-ajax.php'
                    . '?action=wpcf_ajax'
                    . '&amp;wpcf_action=remove_from_history'
                    . '&amp;field_id=' . $field['id'] ) . '&amp;_wpnonce='
                    . wp_create_nonce( 'remove_from_history' ) . '&amp;wpcf_warning='
                    . sprintf( __( 'Are you sure that you want to remove field %s from history?', 'wpcf' ),
                    htmlspecialchars( stripslashes( $field['name'] ) ) )
                    . '&amp;wpcf_ajax_update=wpcf-user-created-fields-wrapper-'
                    . $field['id'] . '" title="'
                    . sprintf( __( 'Remove field %s', 'wpcf' ),
                        htmlspecialchars( stripslashes( $field['name'] ) ) )
                        . '" class="wpcf-ajax-link"><img src="'
                        . WPCF_RES_RELPATH
                        . '/images/delete-2.png" style="postion:absolute;margin-top:5px;margin-left:-4px;" /></a></div>',
                    );
            }
        }
        $form['close-sidebar'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

    }
    // Group data

    $form['open-main'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-form-fields-main" class="wpcf-form-fields-main">',
    );


    /**
     * Now starting form
     */

    /** End admin Styles * */
    // Group fields

    $form['fields_title'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __( 'Fields', 'wpcf' ) . '</h2>',
    );
    $show_under_title = true;

    $form['ajax-response-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-fields-sortable" class="ui-sortable">',
    );

    // If it's update, display existing fields
    $existing_fields = array();
    if ( $update && isset( $update['fields'] ) ) {
        foreach ( $update['fields'] as $slug => $field ) {
            $field['submitted_key'] = $slug;
            $field['group_id'] = $update['id'];
            $form_field = wpcf_fields_get_field_form_data( $field['type'],
                    $field );
            if ( is_array( $form_field ) ) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
            $existing_fields[] = $slug;
            $show_under_title = false;
        }
    }
    // Any new fields submitted but failed? (Don't double it)
    if ( !empty( $_POST['wpcf']['fields'] ) ) {
        foreach ( $_POST['wpcf']['fields'] as $key => $field ) {
            if ( in_array( $key, $existing_fields ) ) {
                continue;
            }
            $field['submitted_key'] = $key;
            $form_field = wpcf_fields_get_field_form_data( $field['type'],
                    $field );
            if ( is_array( $form_field ) ) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
        }
        $show_under_title = false;
    }
    $form['ajax-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>' . '<div id="wpcf-ajax-response"></div>',
    );

    if ( $show_under_title ) {
        $form['fields_title']['#markup'] = $form['fields_title']['#markup']
                . '<div id="wpcf-fields-under-title">'
                . __( 'There are no fields in this group. To add a field, click on the field buttons at the right.', 'wpcf' )
                . '</div>';
    }

    // If update, create ID field
    if ( $update ) {
        $form['group_id'] = array(
            '#type' => 'hidden',
            '#name' => 'group_id',
            '#value' => $update['id'],
            '#forced_value' => true,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => __( 'Save', 'wpcf' ),
        '#attributes' => array('class' => 'button-primary wpcf-disabled-on-submit'),
    );

    // Close main div
    $form['close-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    $form = apply_filters( 'wpcf_form_fields', $form, $update );

    // Add JS settings
    wpcf_admin_add_js_settings( 'wpcfFormUniqueValuesCheckText',
            '\'' . __( 'Warning: same values selected', 'wpcf' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcfFormUniqueNamesCheckText',
            '\'' . __( 'Warning: field name already used', 'wpcf' ) . '\'' );
    wpcf_admin_add_js_settings( 'wpcfFormUniqueSlugsCheckText',
            '\'' . __( 'Warning: field slug already used', 'wpcf' ) . '\'' );

    wpcf_admin_add_js_settings( 'wpcfFormAlertOnlyPreview', sprintf( "'%s'", __( 'Sorry, but this is only preview!', 'wpcf' ) ) );

    $form['form-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    /**
     * return form if current_user_can edit
     */
    if ( $current_user_can_edit) {
        return $form;
    }

    return wpcf_admin_common_only_show($form);
}

/**
 * Dynamically adds new field on AJAX call.
 *
 * @param type $form_data
 */
function wpcf_fields_insert_ajax( $form_data = array() ) {
    echo wpcf_fields_get_field_form( sanitize_text_field( $_GET['field'] ) );
}

/**
 * Dynamically adds existing field on AJAX call.
 *
 * @param type $form_data
 */
function wpcf_fields_insert_existing_ajax() {
    $field = wpcf_admin_fields_get_field( sanitize_text_field( $_GET['field'] ), false, true );
    if ( !empty( $field ) ) {
        echo wpcf_fields_get_field_form( $field['type'], $field );
    } else {
        echo '<div>' . __( "Requested field don't exist", 'wpcf' ) . '</div>';
    }
}

/**
 * Returns HTML formatted field form (draggable).
 *
 * @param $type
 * @param array $form_data
 *
 * @return string
 */
function wpcf_fields_get_field_form( $type, $form_data = array() ) {
    $form = wpcf_fields_get_field_form_data( $type, $form_data );
    if ( $form ) {
        $return = '<div class="ui-draggable">'
                . wpcf_form_simple( $form )
                . '</div>';

        /**
         * add extra condition check if this is checkbox
         */
        foreach( $form as $key => $value ) {
            if (
                !array_key_exists('value', $value )
                || !array_key_exists('#attributes', $value['value'] )
                || !array_key_exists('data-wpcf-type', $value['value']['#attributes'] )
                || 'checkbox' != $value['value']['#attributes']['data-wpcf-type']
            ) {
                continue;
            }
            echo '<script type="text/javascript">';
            printf('jQuery(document).ready(function($){wpcf_checkbox_value_zero(jQuery(\'[name="%s"]\'));});', $value['value']['#name'] );
            echo '</script>';
        }

        return $return;
    }
    return '<div>' . __( 'Wrong field requested', 'wpcf' ) . '</div>';
}

/**
 * Processes field form data.
 *
 * @param type $type
 * @param type $form_data
 * @return type
 */
function wpcf_fields_get_field_form_data( $type, $form_data = array() ) {

    // Get field type data
    $field_data = wpcf_fields_type_action( $type );

    if ( !empty( $field_data ) ) {
        $form = array();

        // Set right ID if existing field
        if ( isset( $form_data['submitted_key'] ) ) {
            $id = $form_data['submitted_key'];
        } else {
            $id = $type . '-' . rand();
        }

        // Sanitize
        $form_data = wpcf_sanitize_field( $form_data );

        // Set remove link
        $remove_link = isset( $form_data['group_id'] ) ? admin_url( 'admin-ajax.php?'
                        . 'wpcf_ajax_callback=wpcfFieldsFormDeleteElement&amp;wpcf_warning='
                        . __( 'Are you sure?', 'wpcf' )
                        . '&amp;action=wpcf_ajax&amp;wpcf_action=remove_field_from_group'
                        . '&amp;group_id=' . intval( $form_data['group_id'] )
                        . '&amp;field_id=' . $form_data['id'] )
                . '&amp;_wpnonce=' . wp_create_nonce( 'remove_field_from_group' ) : admin_url( 'admin-ajax.php?'
                        . 'wpcf_ajax_callback=wpcfFieldsFormDeleteElement&amp;wpcf_warning='
                        . __( 'Are you sure?', 'wpcf' )
                        . '&amp;action=wpcf_ajax&amp;wpcf_action=remove_field_from_group' )
                . '&amp;_wpnonce=' . wp_create_nonce( 'remove_field_from_group' );

        /**
         * Set move button
         */
        $form['wpcf-' . $id . '-control'] = array(
            '#type' => 'markup',
            '#markup' => '<img src="' . WPCF_RES_RELPATH
            . '/images/move.png" class="wpcf-fields-form-move-field" alt="'
            . __( 'Move this field', 'wpcf' ) . '" /><a href="'
            . $remove_link . '" '
            . 'class="wpcf-form-fields-delete wpcf-ajax-link">'
            . '<img src="' . WPCF_RES_RELPATH . '/images/delete-2.png" alt="'
            . __( 'Delete this field', 'wpcf' ) . '" /></a>',
        );

        // Set fieldset

        $collapsed = wpcf_admin_fields_form_fieldset_is_collapsed( 'fieldset-' . $id );
        // Set collapsed on AJAX call (insert)
        $collapsed = defined( 'DOING_AJAX' ) ? false : $collapsed;

        // Set title
        $title = !empty( $form_data['name'] ) ? $form_data['name'] : __( 'Untitled', 'wpcf' );
        $title = '<span class="wpcf-legend-update">' . $title . '</span> - '
                . sprintf( __( '%s field', 'wpcf' ), $field_data['title'] );

        // Do not display on Usermeta Group edit screen
        if ( !isset( $_GET['page'] ) || $_GET['page'] != 'wpcf-edit-usermeta' ) {
            if ( !empty( $form_data['data']['conditional_display']['conditions'] ) ) {
                $title .= ' ' . __( '(conditional)', 'wpcf' );
            }
        }

        $form['wpcf-' . $id] = array(
            '#type' => 'fieldset',
            '#title' => $title,
            '#id' => 'fieldset-' . $id,
            '#collapsible' => true,
            '#collapsed' => $collapsed,
            '#attributes' => array(
                'class' => 'js-wpcf-slugize-container',
            ),
        );

        // Get init data
        $field_init_data = wpcf_fields_type_action( $type );

        // See if field inherits some other
        $inherited_field_data = false;
        if ( isset( $field_init_data['inherited_field_type'] ) ) {
            $inherited_field_data = wpcf_fields_type_action( $field_init_data['inherited_field_type'] );
        }

        $form_field = array();

        // Force name and description
        $form_field['name'] = array(
            '#type' => 'textfield',
            '#name' => 'name',
            '#attributes' => array(
                'class' => 'wpcf-forms-set-legend wpcf-forms-field-name js-wpcf-slugize-source',
                'style' => 'width:100%;margin:10px 0 10px 0;',
                'placeholder' => __( 'Enter field name', 'wpcf' ),
            ),
            '#validate' => array('required' => array('value' => true)),
            '#inline' => true,
        );
        $form_field['slug'] = array(
            '#type' => 'textfield',
            '#name' => 'slug',
            '#attributes' => array(
                'class' => 'wpcf-forms-field-slug js-wpcf-slugize',
                'style' => 'width:100%;margin:0 0 10px 0;',
                'maxlength' => 255,
                'placeholder' => __( 'Enter field slug', 'wpcf' ),
            ),
            '#validate' => array('nospecialchars' => array('value' => true)),
            '#inline' => true,
        );

        // If insert form callback is not provided, use generic form data
        if ( function_exists( 'wpcf_fields_' . $type . '_insert_form' ) ) {
            $form_field_temp = call_user_func( 'wpcf_fields_' . $type
                    . '_insert_form', $form_data,
                    'wpcf[fields]['
                    . $id . ']' );
            if ( is_array( $form_field_temp ) ) {
                unset( $form_field_temp['name'], $form_field_temp['slug'] );
                $form_field = $form_field + $form_field_temp;
            }
        }

        $form_field['description'] = array(
            '#type' => 'textarea',
            '#name' => 'description',
            '#attributes' => array(
                'rows' => 5,
                'cols' => 1,
                'style' => 'margin:0 0 10px 0;',
                'placeholder' => __( 'Describe this field', 'wpcf' ),
            ),
            '#inline' => true,
        );

        /**
         * add placeholder field
         */
            switch($type)
            {
            case 'audio':
            case 'colorpicker':
            case 'date':
            case 'email':
            case 'embed':
            case 'file':
            case 'image':
            case 'numeric':
            case 'phone':
            case 'skype':
            case 'textarea':
            case 'textfield':
            case 'url':
            case 'video':
                $form_field['placeholder'] = array(
                    '#type' => 'textfield',
                    '#name' => 'placeholder',
                    '#inline' => true,
                    '#title' => __( 'Placeholder', 'wpcf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter placeholder', 'wpcf'),
                    ),
                );
                break;
            }

        /**
         * add default value
         */
            switch($type)
            {
            case 'audio':
            case 'email':
            case 'embed':
            case 'file':
            case 'image':
            case 'numeric':
            case 'phone':
            case 'textfield':
            case 'url':
            case 'video':
                $form_field['user_default_value'] = array(
                    '#type' => 'textfield',
                    '#name' => 'user_default_value',
                    '#inline' => true,
                    '#title' => __( 'Default Value', 'wpcf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter default value', 'wpcf'),
                    ),
                );
                break;
            case 'textarea':
            case 'wysiwyg':
                $form_field['user_default_value'] = array(
                    '#type' => 'textarea',
                    '#name' => 'user_default_value',
                    '#inline' => true,
                    '#title' => __( 'Default Value', 'wpcf' ),
                    '#attributes' => array(
                        'style' => 'width:100%;margin:0 0 10px 0;',
                        'placeholder' =>  __('Enter default value', 'wpcf'),
                    ),
                );
                break;
            }
            switch($type)
            {
            case 'audio':
            case 'file':
            case 'image':
            case 'embed':
            case 'url':
            case 'video':
                $form_field['user_default_value']['#validate'] = array('url'=>array());
                break;
            case 'email':
                $form_field['user_default_value']['#validate'] = array('email'=>array());
                break;
            case 'numeric':
                $form_field['user_default_value']['#validate'] = array('number'=>array());
                break;
            }

        if ( wpcf_admin_can_be_repetitive( $type ) ) {

	        // We need to set the "repetitive" setting to a string '0' or '1', not numbers, because it will be used
	        // again later in this method (which I'm not going to refactor now) and because the form renderer
	        // is oversensitive.
	        $is_repetitive_as_string = ( 1 == wpcf_getnest( $form_data, array( 'data', 'repetitive' ), '0' ) ) ? '1' : '0';
	        if( !array_key_exists( 'data', $form_data ) || !is_array( $form_data['data'] ) ) {
		        $form_data['data'] = array();
	        }
	        $form_data['data']['repetitive'] = $is_repetitive_as_string;

            $temp_warning_message = '';
            $form_field['repetitive'] = array(
                '#type' => 'radios',
                '#name' => 'repetitive',
                '#title' => __( 'Single or repeating field?', 'wpcf' ),
                '#options' => array(
                    'repeat' => array(
                        '#title' => __( 'Allow multiple-instances of this field', 'wpcf' ),
                        '#value' => '1',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.wpcf-cd-warning\').hide(); jQuery(this).parent().find(\'.wpcf-cd-repetitive-warning\').show();'),
                    ),
                    'norepeat' => array(
                        '#title' => __( 'This field can have only one value', 'wpcf' ),
                        '#value' => '0',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.wpcf-cd-warning\').show(); jQuery(this).parent().find(\'.wpcf-cd-repetitive-warning\').hide();'),
                    ),
                ),
                '#default_value' => $is_repetitive_as_string,
                '#after' => wpcf_admin_is_repetitive( $form_data ) ? '<div class="wpcf-message wpcf-cd-warning wpcf-error" style="display:none;"><p>' . __( "There may be multiple instances of this field already. When you switch back to single-field mode, all values of this field will be updated when it's edited.", 'wpcf' ) . '</p></div>' . $temp_warning_message : $temp_warning_message,
            );
        }

        // Process all form fields
        foreach ( $form_field as $k => $field ) {
            $form['wpcf-' . $id][$k] = $field;
            // Check if nested
            if ( isset( $field['#name'] ) && strpos( $field['#name'], '[' ) === false ) {
                $form['wpcf-' . $id][$k]['#name'] = 'wpcf[fields]['
                        . $id . '][' . $field['#name'] . ']';
            } else if ( isset( $field['#name'] ) ) {
                $form['wpcf-' . $id][$k]['#name'] = 'wpcf[fields]['
                        . $id . ']' . $field['#name'];
            }
            if ( !isset( $field['#id'] ) ) {
                $form['wpcf-' . $id][$k]['#id'] = $type . '-'
                        . $field['#type'] . '-' . rand();
            }
            if ( isset( $field['#name'] ) && isset( $form_data[$field['#name']] ) ) {
                $form['wpcf-'
                        . $id][$k]['#value'] = $form_data[$field['#name']];
                $form['wpcf-'
                        . $id][$k]['#default_value'] = $form_data[$field['#name']];
                // Check if it's in 'data'
            } else if ( isset( $field['#name'] ) && isset( $form_data['data'][$field['#name']] ) ) {
                $form['wpcf-'
                        . $id][$k]['#value'] = $form_data['data'][$field['#name']];
                $form['wpcf-'
                        . $id][$k]['#default_value'] = $form_data['data'][$field['#name']];
            }
        }

        // Set type
        $form['wpcf-' . $id]['type'] = array(
            '#type' => 'hidden',
            '#name' => 'wpcf[fields][' . $id . '][type]',
            '#value' => $type,
            '#id' => $id . '-type',
        );

        // Add validation box
        $form_validate = wpcf_admin_fields_form_validation( 'wpcf[fields]['
                . $id . '][validate]', call_user_func( 'wpcf_fields_' . $type ),
                $form_data );
        foreach ( $form_validate as $k => $v ) {
            $form['wpcf-' . $id][$k] = $v;
        }

        /**
         * WPML Translation Preferences
         *
         * only for post meta
         *
         */
        if (
            isset($form_data['meta_type'])
            && 'postmeta' == $form_data['meta_type']
            && function_exists( 'wpml_cf_translation_preferences' )
        ) {
            $custom_field = !empty( $form_data['slug'] ) ? wpcf_types_get_meta_prefix( $form_data ) . $form_data['slug'] : false;
            $suppress_errors = $custom_field == false ? true : false;
            $translatable = array('textfield', 'textarea', 'wysiwyg');
            $action = in_array( $type, $translatable ) ? 'translate' : 'copy';
            $form['wpcf-' . $id]['wpml-preferences'] = array(
                '#type' => 'fieldset',
                '#title' => __( 'Translation preferences', 'wpcf' ),
                '#collapsed' => true,
            );
            $wpml_prefs = wpml_cf_translation_preferences( $id,
                        $custom_field, 'wpcf', false, $action, false,
                        $suppress_errors );
            $wpml_prefs = str_replace('<span style="color:#FF0000;">', '<span class="wpcf-form-error">', $wpml_prefs);
            $form['wpcf-' . $id]['wpml-preferences']['form'] = array(
                '#type' => 'markup',
                '#markup' => $wpml_prefs,
            );
        }

        if ( empty( $form_data ) || isset( $form_data['is_new'] ) ) {
            $form['wpcf-' . $id]['is_new'] = array(
                '#type' => 'hidden',
                '#name' => 'wpcf[fields][' . $id . '][is_new]',
                '#value' => '1',
                '#attributes' => array(
                    'class' => 'wpcf-is-new',
                ),
            );
        }
        $form_data['id'] = $id;
        $form['wpcf-' . $id] = apply_filters( 'wpcf_form_field',
                $form['wpcf-' . $id], $form_data );
        return $form;
    }
    return false;
}

/**
 * Adds validation box.
 *
 * @param type $name
 * @param string $field
 * @param type $form_data
 * @return type
 */
function wpcf_admin_fields_form_validation( $name, $field, $form_data = array() ) {
    $form = array();

    if ( isset( $field['validate'] ) ) {

        $form['validate-table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="wpcf-fields-field-value-options" '
            . 'cellspacing="0" cellpadding="0"><thead><tr><td>'
            . __( 'Validation', 'wpcf' ) . '</td><td>' . __( 'Error message', 'wpcf' )
            . '</td></tr></thead><tbody>',
        );

        // Process methods
        foreach ( $field['validate'] as $k => $method ) {

            // Set additional method data
            if ( is_array( $method ) ) {
                $form_data['data']['validate'][$k]['method_data'] = $method;
                $method = $k;
            }

            if ( !Wpcf_Validate::canValidate( $method )
                    || !Wpcf_Validate::hasForm( $method ) ) {
                continue;
            }

            $form['validate-tr-' . $method] = array(
                '#type' => 'markup',
                '#markup' => '<tr><td>',
            );

            // Get method form data
            if ( Wpcf_Validate::canValidate( $method )
                    && Wpcf_Validate::hasForm( $method ) ) {

                $field['#name'] = $name . '[' . $method . ']';
                $form_validate = call_user_func_array(
                        array('Wpcf_Validate', $method . '_form'),
                        array(
                    $field,
                    isset( $form_data['data']['validate'][$method] ) ? $form_data['data']['validate'][$method] : array()
                        )
                );

                // Set unique IDs
                foreach ( $form_validate as $key => $element ) {
                    if ( isset( $element['#type'] ) ) {
                        $form_validate[$key]['#id'] = $element['#type'] . '-'
                                . wpcf_unique_id( serialize( $element ) );
                    }
                    if ( isset( $element['#name'] ) && strpos( $element['#name'],
                                    '[message]' ) !== FALSE ) {
                        $before = '</td><td>';
                        $after = '</td></tr>';
                        $form_validate[$key]['#before'] = isset( $element['#before'] ) ? $element['#before'] . $before : $before;
                        $form_validate[$key]['#after'] = isset( $element['#after'] ) ? $element['#after'] . $after : $after;
                    }
                }

                // Join
                $form = $form + $form_validate;
            }
        }
        $form['validate-table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
        );
    }

    return $form;
}

/**
 * Adds JS validation script.
 */
function wpcf_admin_fields_form_js_validation() {
    wpcf_form_render_js_validation();
}

/**
 * Saves open fieldsets.
 *
 * @param $action
 * @param $fieldset
 * @param bool $group_id
 */
function wpcf_admin_fields_form_save_open_fieldset( $action, $fieldset, $group_id = false ) {
    $data = wpcf_ensarr( get_user_meta( get_current_user_id(), 'wpcf-group-form-toggle', true ) );

    if ( $group_id && $action == 'open' ) {
        $data[intval( $group_id )][$fieldset] = 1;
    } else if ( $group_id && $action == 'close' ) {
        $group_id = intval( $group_id );
        if ( isset( $data[$group_id][$fieldset] ) ) {
            unset( $data[$group_id][$fieldset] );
        }
    } else if ( $action == 'open' ) {
        $data[-1][$fieldset] = 1;
    } else if ( $action == 'close' ) {
        if ( isset( $data[-1][$fieldset] ) ) {
            unset( $data[-1][$fieldset] );
        }
    }
    update_user_meta( get_current_user_id(), 'wpcf-group-form-toggle', $data );
}

/**
 * Saves open fieldsets.
 *
 * @param type $action
 * @param type $fieldset
 * @param type $group_id
 */
function wpcf_admin_fields_form_fieldset_is_collapsed( $fieldset ) {
    if ( isset( $_REQUEST['group_id'] ) ) {
        $group_id = intval( $_REQUEST['group_id'] );
    } else {
        $group_id = -1;
    }
    $data = get_user_meta( get_current_user_id(), 'wpcf-group-form-toggle', true );
    if ( !isset( $data[$group_id] ) ) {
        return true;
    }
    return array_key_exists( $fieldset, $data[$group_id] ) ? false : true;
}

/**
 * Adds 'Edit' and 'Cancel' buttons, expandable div.
 *
 * @todo REMOVE THIS - Since Types 1.2 we do not need it
 *
 * @param type $id
 * @param type $element
 * @param type $title
 * @param type $list
 * @param type $empty_txt
 * @return string
 */
function wpcf_admin_fields_form_nested_elements( $id, $element, $title, $list,
        $empty_txt ) {
    global $wpcf_button_style;
    global $wpcf_button_style30;
    $form = array();
    $form = $element;
    $id = strtolower( strval( $id ) );

    $form['#before'] = '<span id="wpcf-group-form-update-' . $id . '-ajax-response"'
            . ' style="font-style:italic;font-weight:bold;display:inline-block;">'
            . esc_html( $title ) . ' ' . $list . '</span>'
            . '&nbsp;&nbsp;<a href="javascript:void(0);" ' . $wpcf_button_style30 . ' '
            . ' class="button-secondary" onclick="'
            . 'window.wpcf' . ucfirst( $id ) . 'Text = new Array(); window.wpcfFormGroups' . ucfirst( $id ) . 'State = new Array(); '
            . 'jQuery(this).next().slideToggle()'
            . '.find(\'.checkbox\').each(function(index){'
            . 'if (jQuery(this).is(\':checked\')) { '
            . 'window.wpcf' . ucfirst( $id ) . 'Text.push(jQuery(this).next().html()); '
            . 'window.wpcfFormGroups' . ucfirst( $id ) . 'State.push(jQuery(this).attr(\'id\'));'
            . '}'
            . '});'
            . ' jQuery(this).css(\'visibility\', \'hidden\');">'
            . __( 'Edit', 'wpcf' ) . '</a>' . '<div class="hidden" id="wpcf-form-fields-' . $id . '">';

    $form['#after'] = '<a href="javascript:void(0);" ' . $wpcf_button_style . ' '
            . 'class="button-primary wpcf-groups-form-ajax-update-' . $id . '-ok"'
            . ' onclick="">'
            . __( 'OK', 'wpcf' ) . '</a>&nbsp;'
            . '<a href="javascript:void(0);" ' . $wpcf_button_style . ' '
            . 'class="button-secondary wpcf-groups-form-ajax-update-' . $id . '-cancel"'
            . ' onclick="">'
            . __( 'Cancel', 'wpcf' ) . '</a>' . '</div></div>';

    return $form;
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
 *
 *
 *
 *
 *
 * From here add revised code
 */

/**
 *
 * Use this to show filter item
 *
 * @since Types 1.2
 * @global type $wpcf_button_style
 * @global type $wpcf_button_style30
 * @param type $id
 * @param type $txt
 * @param type $txt_empty
 * @param type $e
 * @return string
 */
function _wpcf_filter_wrap( $id, $title, $txt, $txt_empty, $e, $edit_button = '' ) {

    global $wpcf_button_style;
    global $wpcf_button_style30;

    $form = array();
    $unique_id = wpcf_unique_id( serialize( func_get_args() ) );
    $query = 'jQuery(this), \'' . esc_js( $id ) . '\', \'' . esc_js( $title )
        . '\', \'' . esc_js( $txt ) . '\', \'' . esc_js( $txt_empty ) . '\'';

    $group = array(
        'id' => isset($_REQUEST['group_id'])? intval($_REQUEST['group_id']):0,
    );

    $current_user_can_edit = WPCF_Roles::user_can_edit('custom-field', $group);

    if ( empty( $edit_button ) ) {
        $edit = __( 'View', 'wpcf' );
        if ( $current_user_can_edit ) {
            $edit = __( 'Edit', 'wpcf' );
        }
    } else {
        $edit = $edit_button;
    }
    /*
     *
     * Title and Edit button
     */
    $form['filter_' . $unique_id . '_wrapper'] = array(
        '#type' => 'markup',
        '#markup' => '<span class="wpcf-filter-ajax-response"'
        . ' style="font-style:italic;font-weight:bold;display:inline-block;">'
        . $title . ' ' . $txt . '</span>'
        . '&nbsp;&nbsp;<a href="javascript:void(0);" ' . $wpcf_button_style30 . ' '
        . ' class="button-secondary wpcf-form-filter-edit" onclick="wpcfFilterEditClick('
        . $query . ');">'
        . $edit . '</a><div class="hidden" id="wpcf-form-fields-' . $id . '">',
    );

    /**
     * Form element as param
     * It may be single element or array of elements
     * Simply check if array has #type - indicates it is a form item
     */
    if ( isset( $e['#type'] ) ) {
        $form['filter_' . $unique_id . '_items'] = $e;
    } else {
        /*
         * If array of elements just join
         */
        $form = $form + (array) $e;
    }

    /**
     * OK button
     */
    if ( $current_user_can_edit ) {
        $form['filter_' . $unique_id . '_ok'] = array(
            '#type' => 'markup',
            '#markup' => '<a href="javascript:void(0);" ' . $wpcf_button_style . ' '
            . 'class="button-primary  wpcf-form-filter-ok wpcf-groups-form-ajax-update-'
            . $id . '-ok"'
            . ' onclick="wpcfFilterOkClick('
            . $query . ');">'
            . __( 'OK', 'wpcf' ) . '</a>&nbsp;',
            );
    }

    /**
     * Cancel button
     */
    $button_cancel_text = __( 'Close', 'wpcf' );
    if ( $current_user_can_edit ) {
        $button_cancel_text = __( 'Cancel', 'wpcf' );
    }
    $form['filter_' . $unique_id . '_cancel'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            '<a href="javascript:void(0);" %s class="button-secondary wpcf-form-filter-cancel wpcf-groups-form-ajax-update-%s-cancel" onclick="wpcfFilterCancelClick(%s);">%s</a>',
            $wpcf_button_style,
            $id,
            $query,
            $button_cancel_text
        ),
    );

    /**
     * Close wrapper
     */
    $form['filter_' . $unique_id . 'wrapper_close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    return $form;
}
