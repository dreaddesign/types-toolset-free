<?php
/*
 * Footer credit
 */
if (file_exists(WPCF_EMBEDDED_INC_ABSPATH . '/src.php')) {
    include_once WPCF_EMBEDDED_INC_ABSPATH . '/src.php';
}
if (isset($_GET['page']) && in_array($_GET['page'],
                array('wpcf', 'wpcf-ctt', 'wpcf-import-export', 'wpcf-custom-fields-control', 'wpcf-custom-settings'))) {
    add_action('wpcf_admin_page_init', 'wpcf_footer_credit_message_init');
}

if (!is_admin()) {
    wpcf_footer_credits_init();
}

/**
 * Init function. 
 */
function wpcf_footer_credits_init() {
    $template = get_template();
    $option = get_option('wpcf_footer_credit', false);
    if (!empty($option['active'])) {
        if (in_array($template, array('twentyten', 'twentyeleven'))) {
            add_action($template . '_credits', 'wpcf_footer_credit_render');
        } else if ($template == 'canvas') {
            add_action('woo_footer_right_before', 'wpcf_footer_credit_render');
        } else if ($template == 'genesis') {
            add_action('genesis_footer', 'wpcf_footer_credit_render', 10);
        } else if ($template == 'thesis_18') {
            add_action('thesis_hook_footer', 'wpcf_footer_credit_render', 1);
        } else if ($template == 'headway') {
            add_action('headway_footer_open', 'wpcf_footer_credit_render', 11);
        } else {
            add_action('wp_footer', 'wpcf_footer_credit_render');
        }
    }
}

/**
 * Default credits.
 * 
 * @return string[]
 *
 * @fixme this is ancient, might need a review
 */
function wpcf_footer_credit_defaults() {
    return array(

        sprintf(
	        __("Functionality enhanced using %sWordPress Post Fields%s", 'wpcf'),
            sprintf(
	            '<a href="%s" target="_blank">',
	            Types_Helper_Url::get_url( 'using-post-fields' )
            ),
            ' &raquo;</a>'
        ),

        sprintf(
	        __("Functionality enhanced using %sWordPress Post Types%s", 'wpcf'),
            sprintf(
                '<a href="%s" target="_blank">',
	            Types_Helper_Url::get_url( 'custom-post-types' )
            ),
            ' &raquo;</a>'
        ),

        sprintf(
	        __("Functionality enhanced using %sWordPress Taxonomy%s", 'wpcf'),
            sprintf(
	            '<a href="%s" target="_blank">',
	            Types_Helper_Url::get_url( 'custom-taxonomy' )
            ),
            ' &raquo;</a>'
        ),
    );
}

/**
 * Renders credits in footer. 
 */
function wpcf_footer_credit_render() {
    $option = get_option('wpcf_footer_credit', false);
    if (!empty($option['active'])) {
        // Set message
        $data = wpcf_footer_credit_defaults();
        if (isset($option['message']) && isset($data[$option['message']])) {
            $message = $data[$option['message']];
        } else {
            $message = $data[0];
        }
        $template = get_template();
        if ($template == 'canvas') {
            echo '<p style="margin-bottom:10px;">' . $message . '</p>';
        } else if ($template == 'genesis') {
            echo '<div id="types-credits" class="creds"><p>' . $message . '</p></div>';
        } else if ($template == 'thesis_18') {
            echo '<p>' . $message . '</p>';
        } else if ($template == 'headway') {
            echo '<p style="float:none;" class="footer-left footer-headway-link footer-link">' . $message . '</p>';
        } else if ($template == 'twentyeleven') {
            echo $message . '<br />';
        } else if ($template == 'twentyten') {
            echo str_replace('<a ', '<a style="background:none;" ', $message) . '<br />';
        } else {
            echo '<div id="types-credits" style="margin: 10px 0 10px 0;width:95%;text-align:center;font-size:0.9em;">' . $message . '</div>';
        }
    }
}

/**
 * Support message init.
 */
function wpcf_footer_credit_message_init() {
    add_action('admin_notices', 'wpcf_footer_credit_message');
}

/**
 * Support message.
 */
function wpcf_footer_credit_message() {
    $dismissed = get_option('wpcf_dismissed_messages', array());
    if (in_array('footer_credit_support_message', $dismissed)) {
        return false;
    }
    $option = get_option('wpcf_footer_credit', false);
    if (empty($option['active'])) {
        $message = __('You too can support Types! Would you like to add a small credit link, saying that you\'re using Types for custom fields or post types?', 'wpcf')
                . '<br /><br />'
                . '<a onclick="jQuery(this).parent().parent().fadeOut();" class="wpcf-ajax-link button-primary" href="'
                . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=footer_credit_activate_message&amp;_wpnonce='
                        . wp_create_nonce('footer_credit_activate_message')) . '" class="button-primary">' . __('Yes', 'wpcf') . '</a>'
                . "&nbsp;<a onclick=\"jQuery(this).parent().parent().fadeOut();\" class=\"wpcf-ajax-link button-secondary\" href=\""
                . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=dismiss_message&amp;id='
                        . 'footer_credit_support_message' . '&amp;_wpnonce=' . wp_create_nonce('dismiss_message')) . "\">"
                . __('No, thanks', 'wpcf') . '</a>';
        echo '<div class="message updated"><p>' . $message . '</p></div>';
    }
}
