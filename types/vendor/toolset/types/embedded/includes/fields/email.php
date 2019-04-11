<?php

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_email() {

    return array(
        'id' => 'wpcf-email',
        'title' => __( 'Email', 'wpcf' ),
        'description' => __( 'Email', 'wpcf' ),
        'validate' => array(
            'required' => array(
                'form-settings' =>
                    include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' ),
            ),
            'email' => array(
                'form-settings' => array_replace_recursive(
                    include( dirname( __FILE__ ) . '/patterns/validate/form-settings/default.php' ),
                    array(
                        'control' => array(
                            '#title' => __('Validation', 'wpcf' ),
                            '#label' => 'Email',
                        )
                    )
                )
            )
        ),
        'inherited_field_type' => 'textfield',
        'font-awesome' => 'envelope',
    );
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_email_view( $params ) {
    $add = '';
    if ( !empty( $params['title'] ) ) {
        $add .= ' title="' . $params['title'] . '"';
        $title = $params['title'];
    } else {
        $add .= ' title="' . $params['field_value'] . '"';
        $title = $params['field_value'];
    }
    if ( !empty( $params['class'] ) ) {
        $add .= ' class="' . $params['class'] . '"';
    }
    if ( !empty( $params['style'] ) ) {
        $add .= ' style="' . $params['style'] . '"';
    }
    $output = '<a href="mailto:' . $params['field_value'] . '"' . $add . '>'
            . $title . '</a>';
    return $output;
}

/**
 * Editor callback form.
 */
function wpcf_fields_email_editor_callback( $field, $settings ) {
    return array(
        'supports' => array('styling', 'style'),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'wpcf' ),
                'title' => __( 'Display options for this field:', 'wpcf' ),
                'content' => WPCF_Loader::template( 'editor-modal-email',
                        $settings ),
            )
        )
    );
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_email_editor_submit( $data, $field, $context ) {
    $add = '';
    if ( !empty( $data['title'] ) ) {
        $add = ' title="' . strval( $data['title'] ) . '"';
    }
    if ( $context == 'usermeta' ) {
        $add .= wpcf_get_usermeta_form_addon_submit();
        $shortcode = wpcf_usermeta_get_shortcode( $field, $add );
	} elseif ( $context == 'termmeta' ) {
        $add .= wpcf_get_termmeta_form_addon_submit();
        $shortcode = wpcf_termmeta_get_shortcode( $field, $add );
    } else {
        $shortcode = wpcf_fields_get_shortcode( $field, $add );
    }

    return $shortcode;
}
