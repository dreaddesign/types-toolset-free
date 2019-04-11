<?php
/**
 *
 *
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_audio() {
    return array(
        'id' => 'wpcf-audio',
        'title' => __( 'Audio', 'wpcf' ),
        'description' => __( 'Audio', 'wpcf' ),
        'wp_version' => '3.6',
        'inherited_field_type' => 'file',
        'validate' => array(
            'required' => array(
                'form-settings' => include( dirname( __FILE__ ) . '/patterns/validate/form-settings/required.php' )
            )
        ),
        'font-awesome' => 'music',
    );
}

/**
 * View function.
 *
 * @global type $wp_embed
 * @param type $params
 * @return string
 */
function wpcf_fields_audio_view( $params )
{
    /**
     * check value
     */
    if (
        !isset($params['field_value'] ) 
        || !is_string( $params['field_value'] )
        || empty($params['field_value'])
    ) {
        return '__wpcf_skip_empty';
    }
    $src = esc_url_raw($params['field_value']);
    /**
     * sanitize src
     * see: https://codex.wordpress.org/Audio_Shortcode#Options
     */
    if ( !preg_match('/(mp3|m4a|ogg|wav|wma)$/i', $src ) ) {
        return '__wpcf_skip_empty';
    }
    /**
     * shortcode
     */
    $shortcode = sprintf( '[audio src="%s"', $src);
    /**
     * add options: loop, autoplay
     */
    foreach( array( 'loop', 'autoplay' ) as $key ) {
        if ( !empty($params[$key]) && preg_match( '/^(on|1|true)$/', $params[$key] ) ) {
            $shortcode .= sprintf( ' %s="on"', $key);
        }
    }
    /**
     * add option preload
     */
    if ( !empty($params['preload']) ) {
        if ( preg_match( '/^(on|1|true|auto)$/', $params['preload'] ) ) {
            $shortcode .= ' preload="auto"';
        } else if ( 'metadata' == $params['preload'] ) {
            $shortcode .= ' preload="metadata"';
        }
    }
    $shortcode .= ']';
    /**
     * output
     */
    $output = do_shortcode( $shortcode );
    if ( empty( $output ) ) {
        return '__wpcf_skip_empty';
    }
    return $output;
}

/**
 * Editor callback form.
 */
function wpcf_fields_audio_editor_callback( $field, $data, $meta_type, $post ) {
    return array(
        'supports' => array(),
        'tabs' => array(
            'display' => array(
                'menu_title' => __( 'Display options', 'wpcf' ),
                'title' => __( 'Display options for this field:', 'wpcf' ),
                'content' => WPCF_Loader::template( 'editor-modal-audio', $data ),
            )
        ),
        'settings' => $data,
    );
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_audio_editor_submit( $data, $field, $context ) {
    $add = '';
    if ( !empty( $data['loop'] ) ) {
        $add .= " loop=\"{$data['loop']}\"";
    }
    if ( !empty( $data['autoplay'] ) ) {
        $add .=" autoplay=\"{$data['autoplay']}\"";
    }
    if ( !empty( $data['preload'] ) ) {
        $add .=" preload=\"{$data['preload']}\"";
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
