<?php
/*
 * Fields and groups form functions.
 *
 *
 */
require_once WPCF_EMBEDDED_ABSPATH . '/classes/validate.php';
require_once WPCF_ABSPATH . '/includes/conditional-display.php';

/**
 * Dynamically adds existing field on AJAX call.
 */
function wpcf_usermeta_insert_existing_ajax() {
    $field = wpcf_admin_fields_get_field( sanitize_text_field( $_GET['field'] ), false, true, false, 'wpcf-usermeta');

    if ( !empty( $field ) ) {
        echo wpcf_fields_get_field_form( $field['type'], $field );
    } else {
        echo '<div>' . __( "Requested field don't exist", 'wpcf' ) . '</div>';
    }
}
