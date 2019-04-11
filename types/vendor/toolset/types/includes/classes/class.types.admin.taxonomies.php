<?php
require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.page.php';
/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 * @access (for functions: only use if private)
 *
 * @see Function/method/class relied on
 * @link URL
 * @global type $varname Description.
 * @global type $varname Description.
 *
 * @param type $var Description.
 * @param type $var Optional. Description.
 * @return type Description.
 */
class Types_Admin_Taxonomies extends Types_Admin_Page
{
    private $taxonomies_array = array();

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function __construct()
    {
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function init_admin()
    {
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function get()
    {
        if (!empty($this->taxonomies_array) ) {
            return $this->taxonomies_array;
        }
        $taxonomies = array();
        /**
         * get custom taxonomies
         */
        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        if( is_array( $custom_taxonomies ) ) {
            foreach ($custom_taxonomies as $slug => $data ) {
                /*
                 * commented out next line as it's a db saved value and deactivated
                 * build-in taxonomies also saved as custom
                 */
                // $data['_builtin'] = false;
                $taxonomies[$slug] = $data;
            }
        }

        /**
         * get built-in taxonomies
         */
        $buildin_taxonomies = $this->object_to_array(wpcf_get_builtin_in_taxonomies('objects'));
        foreach ($buildin_taxonomies as $slug => $data ) {
            // check if built-in taxonomies are already saved as custom taxonomies
            if( isset( $taxonomies[$slug] ) )
                continue;

            if( !isset( $data['slug'] ) )
                $data['slug'] = $slug;
            
            $taxonomies[$slug] = $data;
        }
        return $taxonomies;
    }

    /**
     * Summary.
     *
     * Description.
     *
     * @since x.x.x
     * @access (for functions: only use if private)
     *
     * @see Function/method/class relied on
     * @link URL
     * @global type $varname Description.
     * @global type $varname Description.
     *
     * @param type $var Description.
     * @param type $var Optional. Description.
     * @return type Description.
     */
    public function get_post_types_supported_by_taxonomy($taxonomy)
    {
        $supported = array();
        $taxonomies = $this->get();
        /**
         * custom
         */
        if (
            true
            && isset($taxonomies[$taxonomy])
            && isset($taxonomies[$taxonomy]['supports'])
            && is_array($taxonomies[$taxonomy]['supports'])
        ) {
            return $taxonomies[$taxonomy]['supports'];
        }
        /**
         * built-in
         */
        if (
            true
            && isset($taxonomies[$taxonomy])
            && isset($taxonomies[$taxonomy]['object_type'])
            && is_array($taxonomies[$taxonomy]['object_type'])
        ) {
            return $taxonomies[$taxonomy]['object_type'];
        }
        return array();
    }
}
