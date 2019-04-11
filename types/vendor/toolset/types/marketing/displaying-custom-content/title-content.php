<?php
// make sure needed css is enqueued
wp_enqueue_style('wpcf-css-embedded');

$medium = 'undefined';

if( ( isset( $_REQUEST['post'] ) && isset( $_REQUEST['action'] ) )
    || isset( $_REQUEST['post_type'] ) )  {
	$medium = 'post-edit';
} elseif( isset( $_REQUEST['page'] )
          && ( empty( $medium ) || ('types' == $medium) || ('undefined' == $medium ) ) ) {
    switch ($_REQUEST['page']) {
        case 'wpcf-edit-type':
            $medium = 'post-type-edit';
            break;
        case 'wpcf-edit-tax':
            $medium = 'taxonomy-edit';
            break;
        case 'wpcf-edit':
            $medium = 'custom-fields-group-edit';
            break;
        case 'wpcf-edit-usermeta':
            $medium = 'user-fields-group-edit';
            break;
        default:
            $medium = 'types';
    }

}

Types_Helper_Url::load_documentation_urls();
Types_Helper_Url::set_medium( $medium );

return array(
	'title' => __('Displaying Custom Content', 'wpcf'),
	'content' => '
		<div class="wpcf-howto-views wpcf-info-box-with-icon">
            <div class="wpcf-icon">
                <span class="icon-types-logo"></span>
            </div>
            <div class="wpcf-info">
                <p>
                    ' . __( 'The complete Toolset package lets you display custom content on the site’s front-end easily, without writing PHP code.', 'wpcf' ) . '
                </p>

                 <a target="_blank" href="'. Types_Helper_Url::get_url( 'compare-toolset-php', true ) . '"><b>' . __( 'Toolset vs. PHP comparison', 'wpcf' ) . '</b></a>
            </div>


            <p style="padding-top: 10px; margin-bottom: 0; border-top: 1px solid #eee;">
                <a class="wpcf-arrow-right wpcf-no-glow" href="javascript:void(0)" data-wpcf-toggle-trigger="wpcf-displaying-custom-content-php-info">
                    ' . __( 'Show instructions for displaying custom content with PHP', 'wpcf' ) . '
                </a>
            </p>

            <div data-wpcf-toggle="wpcf-displaying-custom-content-php-info" style="display: none;">
                <p>
                    ' . __( 'If you are customizing an existing theme, consider <a href="https://codex.wordpress.org/Child_Themes" target="_blank">creating a child theme</a> first.', 'wpcf' ) . '
                </p>

                <p>
                    ' . __( 'Read about <a href="https://codex.wordpress.org/Post_Type_Templates" target="_blank">post type templates</a>, in the WordPress codex, to learn which template functions you should edit in your theme.', 'wpcf' ) . '
                </p>

                <p>
                    ' . __( 'Use <a href="https://codex.wordpress.org/Class_Reference/WP_Query" target="_blank">WP_Query</a> to load content from the database and display it.', 'wpcf' ) . '
                </p>

                <p>
                    ' . sprintf(
							__( 'Use %s to display custom fields in your PHP templates.', 'wpcf' ),
							sprintf(
								'<a href="%s" target="_blank">%s</a>',
								Types_Helper_Url::get_url( 'types-fields-api', true ),
								__( 'Types fields API', 'wpcf' )
							)
						) . '
                </p>
            </div>
        </div>'
);