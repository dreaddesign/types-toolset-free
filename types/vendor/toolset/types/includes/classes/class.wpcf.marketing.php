<?php
/**
 *
 * Types Marketing Class
 *
 *
 */

/**
 * Types Marketing Class
 *
 * @since Types 1.6.5
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category Help
 * @author marcin <marcin.p@icanlocalize.com>
 */
class WPCF_Types_Marketing
{
    protected $option_name = 'types-site-kind';
    protected $option_disable = 'types-site-kind-disable';
    protected $options;
    protected $adverts;

    public function __construct()
    {
        $this->options = array();
        $this->adverts = include WPCF_ABSPATH.'/marketing/etc/types.php';
        add_filter('editor_addon_dropdown_after_title', array($this, 'add_views_advertising'));
    }

    /**
     * Add Views advertising on modal shortcode window.
     *
     * Add Views advertising on modal shortcode window. Advertisng will be 
     * added only when Views plugin is not active.
     *
     * @since 1.7
     * @param string $content Content of this filter.
     * @return string Content with advert or not.
     */
    public function add_views_advertising($content)
    {
        /**
         * do not load advertising if Views are active
         */
        if ( defined('WPV_VERSION') ) {
            return $content;
        }

        /**
         * Allow to turn off views advert.
         *
         * This filter allow to turn off views advert even Viwes plugin is not 
         * avaialbe.
         *
         * @since 1.7
         *
         * @param boolean $show Show adver?
         */
        if ( !apply_filters('show_views_advertising', true )) {
            return;
        }

	    // documentation urls
	    Types_Helper_Url::load_documentation_urls();
	    
        $content .= '<div class="types-marketing types-marketing-views">';
        $content .= sprintf(
            '<h4><span class="icon-toolset-logo ont-color-orange"></span>%s</h4>',
            __('Want to create templates with fields?', 'wpcf')
        );
        $content .= sprintf(
            '<p>%s</p>',
            __('The full Toolset package allows you to design templates for content and insert fields using the WordPress editor.', 'wpcf')
        );
        $content .= sprintf(
            '<p class="buttons"><a href="%s" class="button" target="_blank">%s</a> <a href="%s" class="more" target="_blank">%s</a></p>',
            esc_attr(
	            Types_Helper_Url::get_url( 'wp-types', true, 'meet-toolset', Types_Helper_Url::UTM_MEDIUM_POSTEDIT )
            ),
            __('Meet Toolset', 'wpcf'),
            esc_attr(
	            Types_Helper_Url::get_url( 'content-templates', true, 'creating-content-templates', Types_Helper_Url::UTM_MEDIUM_POSTEDIT )
            ),
            __('Creating Templates for Content', 'wpcf')
        );
        $content .= '</div>';
        return $content;
    }

    protected function get_page_type() {
	    $screen = get_current_screen();
	    switch ( $screen->id ) {
		    case 'toolset_page_wpcf-edit-type':
			    return 'cpt';
		    case 'toolset_page_wpcf-edit-tax':
			    return 'taxonomy';
		    case 'toolset_page_wpcf-edit':
		    case 'toolset_page_wpcf-edit-usermeta':
		    case 'toolset_page_wpcf-termmeta-edit':
			    return 'fields';
	    }

	    return false;
    }

    public function get_options()
    {
        return $this->options;
    }

    public function get_option_name()
    {
        return $this->option_name;
    }

    public function get_option_disiable_value()
    {
        return get_option($this->option_disable, 0);
    }

    public function get_option_disiable_name()
    {
        return $this->option_disable;
    }

}
