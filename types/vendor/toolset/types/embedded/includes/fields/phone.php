<?php
/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_phone() {

    return array(
        'id' => 'wpcf-phone',
        'title' => __('Phone', 'wpcf'),
        'description' => __('Phone', 'wpcf'),
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'inherited_field_type' => 'textfield',
        'font-awesome' => 'phone',
    );
}
