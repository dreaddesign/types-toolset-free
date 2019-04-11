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
class Types_Admin_Fields extends Types_Admin_Page
{
    private $meta_key_posts = '_wp_types_group_post_types';

    public function __construct()
    {
        $this->init_admin();
    }

    public function init_admin()
    {
        /**
         * Summary.
         *
         * Description.
         *
         * @since x.x.x
         *
         * @param type  $var Description.
         * @param array $args {
         *     Short description about this hash.
         *
         *     @type type $var Description.
         *     @type type $var Description.
         * }
         * @param type  $var Description.
         */
        add_filter('wpcf_meta_box_order_defaults', array($this, 'add_metaboxes'), 10, 2);
        /**
         * Summary.
         *
         * Description.
         *
         * @since x.x.x
         *
         * @param type  $var Description.
         * @param array $args {
         *     Short description about this hash.
         *
         *     @type type $var Description.
         *     @type type $var Description.
         * }
         * @param type  $var Description.
         */
        add_action('wp_ajax_wpcf_edit_post_get_fields_box', array($this, 'ajax_metabox_custom_fields_get_content'));
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
    public function add_metaboxes($meta_boxes, $type )
    {
        if ( 'post_type' != $type ) {
            return $meta_boxes;
        }

        /**
         * types-608
         */
        add_action( 'admin_head', array( $this, 'wpcf_hide_field_groups' ) );

        $meta_boxes['field_groups'] = array(
            'callback' => array( $this, 'metabox_field_groups'),
            'title' => __('Field Groups to be used with <i class="js-wpcf-singular"></i>', 'wpcf'),
            'default' => 'normal',
        );

        $meta_boxes['custom_fields'] = array(
            'callback' => array( $this, 'metabox_custom_fields'),
            'title' => __('Post Fields to be shown as columns in Post Type listing in WordPress Admin', 'wpcf'),
            'default' => 'normal',
        );
        return $meta_boxes;
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
    public function metabox_field_groups($ct)
    {
        $groups = wpcf_admin_fields_get_groups();
        if (empty($groups) ) {
            _e('There is no groups to display. Please add some first', 'wpcf');
            return;
        }
        /**
         * set post_type
         */
        $post_type = $this->get_type_from_request('wpcf-post-type');
        /**
         * build form
         */
        $form = array();
        foreach( $groups as $group ) {
            $post_types = wpcf_admin_get_post_types_by_group($group['id']);
            $options[] = array(
                '#title' => $group['name'],
                '#name' => 'ct[custom-field-group][' . $group['id'] . ']',
                '#default_value' => empty($post_types) || in_array($post_type, $post_types)? 1 : 0,
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
                '#attributes' => array(
                    'data-wpcf-nonce' => wp_create_nonce('save_custom_fields_groups', $post_type, $group['id']),
                    'data-wpcf-post-type' => $post_type,
                    'data-wpcf-group-id' => $group['id'],
                    'class' => 'js-wpcf-custom-fields-group',
                ),
            );
        }
        $form['custom-fields-groups'] = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'ct[custom-fields-groups]',
            '#inline' => true,
            '#before' => '<ul class="wpcf-list">',
            '#after' => '</ul>',
            '_builtin' => true,
        );
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
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
    public function metabox_custom_fields()
    {
        $type = 'wpcf-post-type';
        $id = $this->get_type_from_request($type);
        $before = sprintf(
            '<div
                class="wpcf-box"
                data-wpcf-nonce="%s"
                data-wpcf-id="%s"
                data-wpcf-type="%s"
            >',
            esc_attr(wp_create_nonce($this->get_nonce('custom-fields-box', $type, $id))),
            esc_attr($id),
            esc_attr($type)
        );

        $form = array(
            'placeholder' => array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<p class="wpcf-box-loading"><span class="spinner"></span>%s</p>',
                    __('Please Wait, Loading…', 'wpcf')
                ),
                '#before' => $before,
                '#after' => '</div>',
                '#pattern' => '<BEFORE><ELEMENT><AFTER>',
            ),
        );
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
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
    public function get_groups_with_post_types()
    {
        $groups = wpcf_admin_fields_get_groups();
        $groups_to_return = array();
        foreach($groups as $group) {
            $group[$this->meta_key_posts] = array_filter(explode(',', get_post_meta($group['id'], $this->meta_key_posts, true)));
            $groups_to_return[] = $group;
        }
        return $groups_to_return;
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
    public function ajax_metabox_custom_fields_get_content()
    {
        // check nonce
        if ( ! isset($_REQUEST['_wpnonce'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_nonce('custom-fields-box', $_REQUEST['type'], $_REQUEST['id'])) )
            $this->verification_failed_and_die();

        // we need an array of all possible fields to check agains what is stored in the database,
        // otherwise stored db fields will always be shown until next cpt save
        $possible_fields = array();

        // current selected groups
        $current_selected_groups = isset( $_REQUEST['current_groups'] ) && !empty( $_REQUEST['current_groups'] )
            ? $_REQUEST['current_groups']
            : array();

        // current selected fields
        $current_selected_fields = isset( $_REQUEST['current_fields'] ) && !empty( $_REQUEST['current_fields'] )
            ? $_REQUEST['current_fields']
            : array();

        // get post type slug
        $post_type_slug = $this->get_type_from_request( 'id' );

        // not saved yet
        if ( empty( $post_type_slug ) ) {
            $this->print_notice_and_die(
                __( 'Please save first, before you can edit the custom fields.', 'wpcf' )
            );
        }

        // current post type
        require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.post-type.php';
        $wpcf_post_type = new Types_Admin_Post_Type();
        $post_type = $wpcf_post_type->get_post_type( $post_type_slug );

        // no groups selected
        if( empty( $current_selected_groups ) ) {
            $this->print_notice_and_die(
                sprintf(
                    __(
                        'You can include some Post Fields in the <b>listing page for %s</b>. Add Field Group(s) to this Post Type and you will be able to select which fields will be displayed in the listing page.',
                        'wpcf'
                    ),
                    $post_type['labels']['name']
                )
            );
        }

        // all custom field groups
        $custom_field_groups = wpcf_admin_fields_get_groups(TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, true, true);

        if( empty( $custom_field_groups ) ) {
            echo wpautop(__('To use custom fields, please create a group to hold them.', 'wpcf'));
            die;
        }

        // form
        $form = $field_checkboxes = array();

        // add description to form
        $form['description'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<p class="description">%s</p>',
                __('Check which fields should be shown on this Post Type list as columns. (NOTE: Some fields cannot be shown due to their complexity)', 'wpcf')
            ),
        );

        $form_add = array();

        // for all custom field groups
        foreach( $custom_field_groups as $custom_field_group ) {

            // skip group if not selected
            if ( !in_array( $custom_field_group['id'], $current_selected_groups ) )
                continue;

            $id = 'group-' . $custom_field_group['id'] . '-';

            // add fields to possible fields
            if ( isset( $custom_field_group['fields'] ) ) {
                $possible_fields = array_merge( $possible_fields, $custom_field_group['fields'] );
            } else {
                $custom_field_group['fields'] = array();
            }

            // get field checkboxes
            $field_checkboxes = $this->build_options($post_type, $custom_field_group['fields'], 'custom-field-%s');

            // no fields
            if( empty( $field_checkboxes ) ) {
                $form_add[$id] = array(
                    '#type' => 'markup',
                    '#title' => $custom_field_group['name'],
                    '#markup' => __('There are no available fields in this group.', 'wpcf' ),
                    '#inline' => true,
                    '_builtin' => true,
                    '#pattern' => '<div class="js-wpcf-custom-field-group wpcf-custom-field-group"><h4><TITLE></h4><ul><ELEMENT></ul></div>',
                );
                continue;
            }

            // if not first time loading fields use the selected fields and not the database stored
            if( $_REQUEST['init'] == 0 ) {

                foreach( $field_checkboxes as $field_key => $field_values ) {

                    // check if field is selected
                    $field_values['#default_value'] =
                        in_array( 'wpcf-'.$field_key, $current_selected_fields )
                        || in_array( $field_key, $current_selected_fields )
                        ? 1
                        : 0;

                    $field_checkboxes[$field_key] = $field_values;
                }
            }

            // add checkboxes to form
            $form_add[$id] = array(
                '#type' => 'checkboxes',
                '#title' => $custom_field_group['name'],
                '#options' => $field_checkboxes,
                '#name' => 'wpcf[group][supports]',
                '#inline' => true,
                '_builtin' => true,
                '#pattern' => '<div class="js-wpcf-custom-field-group wpcf-custom-field-group"><h4><TITLE></h4><ul><ELEMENT></ul></div>',
            );
        }

        // if form is still empty here the user didn't create any group
        if( empty( $form_add ) ) {
            $form['no-fields'] = array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<p class="description">%s</p>',
                    __('No custom field groups found.','wpcf')
                ),
            );
            $form = wpcf_form(__FUNCTION__, $form);
            echo $form->renderForm();
            die;
        }

        $form['groups-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="clearfix wpcf-custom-field-group-container js-wpcf-custom-field-group-container">',
        );

        $form += $form_add;
        $form['groups-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        /**
         * Sortable Area
         */
        // on first load use db stored fields
        if( $_REQUEST['init'] == 1 ) {
            $fields_selected = wpcf_admin_fields_get_active_fields_by_post_type($post_type['slug']);

        // otherwise use unsaved selected fields
        } else {
            $fields_selected = array();
            foreach ($custom_field_groups as $custom_field_group) {
                if( isset( $custom_field_group['fields'] ) )
                    $fields_selected += $custom_field_group['fields'];
            }
        }

        if( !empty( $fields_selected ) ) {
            $field_checkboxes = $this->build_options($post_type, $fields_selected, 'ct[custom_fields][%s]');
            foreach ( $field_checkboxes as $field_key => $field_values) {
                if( $_REQUEST['init'] == 0 ) {

                    $field_values['#default_value'] =
                        in_array( 'wpcf-'.$field_key, $current_selected_fields )
                        || in_array( $field_key, $current_selected_fields )
                        ? 1
                        : 0;
                }

                // remove if unchecked
                if ( empty($field_values['#default_value']) ) {
                    unset($field_checkboxes[$field_key]);
                    continue;
                }

                $field_checkboxes[$field_key]['#type'] = 'hidden';
                $field_checkboxes[$field_key]['#pattern'] = '<div class="wpcf-custom-field js-wpcf-custom-field"><ELEMENT><TITLE></div>';
            }
        }

        $form['wpcf-custom-field-order-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="wpcf-custom-field-order">',
        );

        $fields_in_sortable = array();

        // on first load use db data if not empty
        if( $_REQUEST['init'] == 1 && isset( $post_type['custom_fields'] ) && ! empty( $post_type['custom_fields'] ) ) {
            $fields_in_sortable = $post_type['custom_fields'];

        // otherwise currently selected fields
        } elseif( ! empty( $current_selected_fields ) ) {
            foreach( $current_selected_fields as $field ) {
                $fields_in_sortable[$field] = 1;
            }
        }

        if ( ! empty( $fields_in_sortable) ) {
            $fields_to_add_to_sortable = array();
            foreach( array_keys( $fields_in_sortable ) as $key ) {

                // remove "wpcf-" of id if field in sortable area is not part of checkboxes
                if( !isset( $field_checkboxes[$key] ) )
                    $key = preg_replace( '/^wpcf-/', '', $key );

                // abort if sortable field is still not part of checkboxes
                // OR if field isn't in possible fields
                if( !isset($field_checkboxes[$key] ) || !array_key_exists( $key, $possible_fields ) )
                    continue;

                $fields_to_add_to_sortable['wpcf-custom-field-order-'.$key] = array(
                    '#type' => 'markup',
                    '#markup' => sprintf(
                        '<li class="menu-item-handle" id="wpcf-custom-field-%s"><input type="hidden" name="%s" value="1" />%s</li>',
                        esc_attr($field_checkboxes[$key]['#attributes']['data-wpcf-key']),
                        esc_attr($field_checkboxes[$key]['#name']),
                        $field_checkboxes[$key]['#title']
                    ),
                );
            }

            // add fields to sortable area
            if ( ! empty($fields_to_add_to_sortable) ) {
                $form['wpcf-custom-field-order-ul-open'] = $this->get_empty_ul();
                $form += $fields_to_add_to_sortable;
                $form['wpcf-custom-field-order-ul-close'] = array(
                    '#type' => 'markup',
                    '#markup' => '</ul>',
                );
            }
        }

        // if no fields selected add empty ul
        if( !isset( $fields_to_add_to_sortable ) || empty( $fields_to_add_to_sortable ) )
            $form['wpcf-custom-field-order-ul'] = $this->get_empty_ul(true);

        // close sortable container
        $form['wpcf-custom-field-order-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
        die;
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
    private function get_empty_ul($close_ul = false)
    {
        return array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<p class="description" data-wpcf-message-drag="%s" id="wpcf-custom-field-message"></p><ul class="ui-sortable js-wpcf-custom-field-order-container">%s',
                esc_attr(__('Drag to reorder.', 'wpcf')),
                $close_ul? '</ul>':''
            )
        );
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
    private function build_options($post_type, $fields, $option_name)
    {
        $options = array();
        foreach($fields as $field => $data) {
            if ( isset($data['data']['repetitive']) && $data['data']['repetitive']) {
                continue;
            }
            switch( $data['type'] ) {
            case 'embed':
            case 'checkboxes':
            case 'audio':
            case 'file':
            case 'textarea':
            case 'video':
            case 'wysiwyg':
                $options[$field] = array(
                    '#name' => sprintf($option_name, esc_attr($data['meta_key'])),
                    '#title' => sprintf( '%s <small>(%s)</small>', $data['name'], $data['type']),
                    '#value' => 0,
                    '#inline' => true,
                    '#before' => '<li class="js-wpcf-tooltip wpcf-custom-field-disabled" data-tooltip="'.__( 'This field cannot be shown in Post Type listing due to its complexity.', 'wpcf' ).'">',
                    '#after' => '</li>',
                    '#default_value' => 0,
                    '#attributes' => array(
                        'disabled' => 'disabled',
                    ),
                );
                    continue;
            default:
                $options[$field] = array(
                    '#name' => sprintf($option_name, esc_attr($data['meta_key'])),
                    '#title' => sprintf( '%s <small>(%s)</small>', $data['name'], $data['type']),
                    '#value' => 1,
                    '#inline' => true,
                    '#before' => '<li class="js-wpcf-custom-field">',
                    '#after' => '</li>',
                    '#default_value' => intval(isset($post_type['custom_fields']) && isset($post_type['custom_fields'][$data['meta_key']])),
                    '#attributes' => array(
                        'data-wpcf-key' => $data['meta_key'],
                    ),
                );
            }
        }
        return $options;
    }


    /**
     * types-608
     */
    function wpcf_hide_field_groups() {
        echo '<style>body.toolset_page_wpcf-edit-type label[for="field_groups-hide"],'
             . 'body.toolset_page_wpcf-edit-type #field_groups { display: none !important; }'
             . '</style>';
    }
}
