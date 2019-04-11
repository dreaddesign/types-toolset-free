<?php

/**
 * Register data (called automatically).
 *
 * @return type
 */
function wpcf_fields_wysiwyg() {
    $settings = array(
        'id' => 'wpcf-wysiwyg',
        'title' => __( 'WYSIWYG', 'wpcf' ),
        'description' => __( 'WYSIWYG editor', 'wpcf' ),
        'meta_box_css' => array(
            'wpcf-fields-wysiwyg' => array(
                'inline' => 'wpcf_fields_wysiwyg_css',
            ),
        ),
        'wp_version' => '3.3',
        'font-awesome' => 'list-alt',
    );
    return $settings;
}

/**
 * Meta box form.
 *
 * @param type $field
 * @return array
 */
function wpcf_fields_wysiwyg_meta_box_form( $field, $f ) {

    if ( isset( $f->context ) && $f->context == 'relationship' ) {
        $form = array(
            '#type' => 'textarea',
            '#attributes' => array('class' => 'wpcf-textarea'),
        );
    } else {
        $set = array(
            'wpautop' => true, // use wpautop?
            'media_buttons' => true, // show insert/upload button(s)
            'textarea_name' => 'wpcf[' . $field['id'] . ']', // set the textarea name to something different, square brackets [] can be used here
            'textarea_rows' => get_option( 'default_post_edit_rows', 10 ), // rows="..."
            'tabindex' => '',
            'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
            'editor_class' => 'wpcf-wysiwyg', // add extra class(es) to the editor textarea
            'teeny' => false, // output the minimal editor config used in Press This
            'dfw' => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
            'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
            'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
        );
        $form = array(
            '#type' => 'wysiwyg',
            '#attributes' => array('class' => 'wpcf-wysiwyg'),
            '#editor_settings' => $set,
        );
    }

    return $form;
}

/**
 * CSS for styling TinyMCE Editor.
 */
function wpcf_fields_wysiwyg_css() {
    global $wp_version;

    ?>
    <style type="text/css">
        .wpcf-wysiwyg iframe, .wpcf-wysiwyg .mceIframeContainer {
            background-color: #FFFFFF !important;
        }
        .wpcf-wysiwyg table {
            border: 1px solid #DFDFDF !important;
        }
        .wpcf-media-buttons {
            margin-bottom: 10px;
        }
        .wpcf-media-buttons a {
            margin-left: 5px;
            text-decoration: none;
        }
        .wpcf-wysiwyg-switcher {
            float: right;
            margin-top: -24px;
            padding: 0;
        }
        .wpcf-wysiwyg-switcher a {
            padding: 10px;
            line-height: 25px;
            text-decoration: none;
            color: #000000;
            border: 1px solid #DFDFDF !important;
            border-bottom: none !important;
            background-color: #E8E8E8;
            margin-left: 2px;
        }
        <?php
// WP 3.3 changes
        if ( version_compare( $wp_version, '3.2.1', '<=' ) ) {

            ?>
            .wpcf-wysiwyg .mceResize {
                margin-top: -25px !important;
            }
            <?php
        }

        ?>
    </style>
    <?php
}

/**
 * View function.
 *
 * @param type $params
 * @return type
 */
function wpcf_fields_wysiwyg_view( $params ) {
    $output = '';
    if ( !empty( $params['style'] ) || !empty( $params['class'] ) ) {
        $output .= '<div';
        if ( !empty( $params['style'] ) ) {
            $output .= ' style="' . $params['style'] . '"';
        }
        if ( !empty( $params['class'] ) ) {
            $output .= ' class="' . $params['class'] . '"';
        }
        $output .= '>';
    }

    remove_shortcode('playlist', 'wp_playlist_shortcode');

    $content = stripslashes( $params['field_value'] );

	$the_content_default_filters = array(
		'wptexturize' => 10,
		'wpautop' => 10,
		'shortcode_unautop' => 10,
		'prepend_attachment' => 10,
		'convert_chars' => 10,
		'capital_P_dangit' => 11,
		'do_shortcode' => 11,
        'convert_smilies' => 20,
    );

	if ( isset( $params['suppress_filters'] ) && $params['suppress_filters'] == 'true' ) {
		// suppress filter is used, we still apply the default filters
		// (otherwise WYSIWYG is no more than a multiline field)
		foreach ( $the_content_default_filters as $func => $priority ) {
			if ( function_exists( $func ) ) {
				$content = call_user_func( $func, $content );
			}
		}

		$output .= $content;

	} else {
		$filter_state = new WPCF_WP_filter_state( 'the_content' );
        $applied_original_filters = array();

		// make sure all default
		foreach ( $the_content_default_filters as $filter => $priority ) {
			if ( has_filter( 'the_content', $filter ) ) {
				// filter is registered and will be applied by apply_filters() after this loop
				continue;
			}

			// add filter again
            add_filter( 'the_content', $filter, $priority );

			// store added filter
            $applied_original_filters[$filter] = $priority;
		}


		$output .= apply_filters( 'the_content', $content );

		// remove all previously added filters
		foreach( $applied_original_filters as $filter => $priority ) {
		    remove_filter( 'the_content', $filter, $priority );
        }
        
        if  ((!(strpos( $output, "&amp;#91;") === false)) && (!( strpos( $output, "&amp;#93;") === false)) && (!(strpos( $output, "<pre") === false)) ) {
        	global $SyntaxHighlighter;
        	if ( isset( $SyntaxHighlighter ) ) {
        		if ( is_object( $SyntaxHighlighter ) ) {
        			//This is a syntax higlighting content
        			$output = str_replace("&amp;#91;", "[", $output);
        			$output = str_replace("&amp;#93;", "]", $output);
        		}
        	}
        }
        
        $filter_state->restore( );
    }

    if ( preg_match_all('/\[playlist[^\]]+\]/', $output, $matches ) ) {
        foreach( $matches[0] as $one ) {
            $one = preg_replace('/\[/', '\\[', $one);
            $one = preg_replace('/\]/', '\\]', $one);
            $re = '/'.$one.'/';
            $one = preg_replace('/\&\#(8221|8243);/', '\'', $one);
            $output = preg_replace($re, $one, $output);
        }
    }
    add_shortcode( 'playlist', 'wp_playlist_shortcode' );

    if ( !empty( $params['style'] ) || !empty( $params['class'] ) ) {
        $output .= '</div>';
    }
    return $output;
}

/**
 * Used for recording a current item of the callbacks in $wp_filter[ $tag ] and restoring it
 * after applying a filter recursively.
 *
 * Workaround for https://core.trac.wordpress.org/ticket/17817.
 *
 * From WordPress 4.7 above, this does nothing.
 *
 * @since 1.9.1
 * @deprecated No longer needed since WordPress 4.7
 */
class WPCF_WP_filter_state {

    private $current_index;
    private $tag;
	private $is_disabled = false;

    public function __construct( $tag ) {

	    global $wp_version;

	    if( version_compare( $wp_version, '4.6.9', '>' ) ) {
		    $this->is_disabled = true;
		    return;
	    }

	    global $wp_filter;

        $this->tag = $tag;

        if ( isset( $wp_filter[$tag] ) ) {
            $this->current_index = current($wp_filter[$tag]);
        }
    }

    public function restore( ) {

	    if( $this->is_disabled ) {
		    return;
	    }

        global $wp_filter;

        if ( isset( $wp_filter[$this->tag] ) && $this->current_index ) {
            reset($wp_filter[$this->tag]);
            while ( $this->current_index && current($wp_filter[$this->tag]) && $this->current_index != current($wp_filter[$this->tag]) ) {
                next( $wp_filter[$this->tag] );
            }
        }

    }

}