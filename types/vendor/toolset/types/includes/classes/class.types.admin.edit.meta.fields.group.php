<?php

require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.edit.fields.php';

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
class Types_Admin_Edit_Meta_Fields_Group extends Types_Admin_Edit_Fields
{

    public function __construct()
    {
        parent::__construct();
        $this->get_id = 'group_id';
        $this->type = 'wpcf-usermeta';
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
        $this->post_type = TYPES_USER_META_FIELD_GROUP_CPT_NAME;
        $this->init_hooks();
        $this->boxes = array(
            'submitdiv' => array(
                'callback' => array($this, 'box_submitdiv'),
                'title' => __('Save', 'wpcf'),
                'default' => 'side',
                'priority' => 'high',
            ),
            'types_where' => array(
                'callback' => array($this, 'box_where'),
                'title' => __('Where to display this group', 'wpcf'),
                'default' => 'side',
            ),
        );

        /** Admin styles **/
        $this->current_user_can_edit = WPCF_Roles::user_can_create( 'custom-field' );

        if( defined( 'TYPES_USE_STYLING_EDITOR' )
            && TYPES_USE_STYLING_EDITOR
            && $this->current_user_can_edit) {
            $this->boxes['types_styling_editor'] = array(
                'callback' => array( $this, 'types_styling_editor' ),
                'title' => __( 'Fields Styling Editor' ),
                'default'  => 'normal',
            );
        }
        $this->boxes = apply_filters('wpcf_meta_box_order_defaults', $this->boxes, $this->post_type);
        $this->boxes = apply_filters('wpcf_meta_box_user_meta', $this->boxes, $this->post_type);

        wp_enqueue_script(
            __CLASS__,
            WPCF_RES_RELPATH . '/js/' . 'taxonomy-form.js',
            array('jquery', 'jquery-ui-dialog'),
            WPCF_VERSION
        );
        wp_enqueue_style('wp-jquery-ui-dialog');
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
    public function form()
    {
        $this->save();

        global $wpcf;

        $this->current_user_can_edit = WPCF_Roles::user_can_create('user-meta-field');

        // If it's update, get data
        $this->update = false;
        if (isset($_REQUEST[$this->get_id])) {
            $this->update = wpcf_admin_fields_get_group(intval($_REQUEST[$this->get_id]), TYPES_USER_META_FIELD_GROUP_CPT_NAME);
            $this->current_user_can_edit = WPCF_Roles::user_can_edit('user-meta-field', $this->update);
            if (empty($this->update)) {
                $this->update = false;
                wpcf_admin_message(sprintf(__("Group with ID %d do not exist", 'wpcf'), intval($_REQUEST[$this->get_id])));
            } else {
                $this->update['fields'] = wpcf_admin_fields_get_fields_by_group( sanitize_text_field( $_REQUEST[$this->get_id] ), 'slug', false, true, false, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta');
                $this->update['show_for'] = wpcf_admin_get_groups_showfor_by_group( sanitize_text_field( $_REQUEST[$this->get_id] ) );
                if( defined( 'TYPES_USE_STYLING_EDITOR' ) && TYPES_USE_STYLING_EDITOR ) {
                    $this->update['admin_styles'] = wpcf_admin_get_groups_admin_styles_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
                }
            }
        }

        /**
         * sanitize id
         */
        if ( !isset($this->update['id']) ) {
            $this->update['id'] = 0;
        }

        /**
         * setup meta type
         */
        $this->update['meta_type'] = 'custom_fields_group';

        /**
         * copy update to ct
         */
        $this->ct = $this->update;

        $form = $this->prepare_screen();

        $form['_wpnonce_wpcf'] = array(
            '#type' => 'markup',
            '#markup' => wp_nonce_field('wpcf_form_fields', '_wpnonce_wpcf', true, false),
        );

        /**
         * nonce depend on group id
         */
        $form['_wpnonce_'.$this->post_type] = array(
            '#type' => 'markup',
            '#markup' => wp_nonce_field(
                $this->get_nonce_action($this->update['id']),
                'wpcf_save_group_nonce',
                true,
                false
            ),
        );

        $form['form-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="post-body-content" class="%s">',
                $this->current_user_can_edit? '':'wpcf-types-read-only'
            ),
        );
        $form[$this->get_id] = array(
            '#type' => 'hidden',
            '#name' => 'wpcf[group][id]',
            '#value' => $this->update['id'],
        );

        $form['table-1-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table id="wpcf-types-form-name-table" class="wpcf-types-form-table widefat js-wpcf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'wpcf' ) . '</th></tr></thead><tbody>',
        );
        $table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
        $form['title'] = array(
            '#title' => sprintf(
                '%s <b>(%s)</b>',
                __( 'Name', 'wpcf' ),
                __( 'required', 'wpcf' )
            ),
            '#type' => 'textfield',
            '#name' => 'wpcf[group][name]',
            '#id' => 'wpcf-group-name',
            '#value' => $this->update['id'] ? $this->update['name']:'',
            '#inline' => true,
            '#attributes' => array(
                'class' => 'large-text',
                'placeholder' => __( 'Enter group title', 'wpcf' ),
            ),
            '#validate' => array(
                'required' => array(
                    'value' => true,
                ),
            ),
            '#pattern' => $table_row,
        );
        $form['description'] = array(
            '#title' => __( 'Description', 'wpcf' ),
            '#type' => 'textarea',
            '#id' => 'wpcf-group-description',
            '#name' => 'wpcf[group][description]',
            '#value' => $this->update['id'] ? $this->update['description']:'',
            '#attributes' => array(
                'placeholder' =>  __( 'Enter a description for this group', 'wpcf' ),
                'class' => 'hidden js-wpcf-description',
            ),
            '#pattern' => $table_row,
            '#after' => sprintf(
                '<a class="js-wpcf-toggle-description hidden" href="#">%s</a>',
                __('Add description', 'wpcf')
            ),
            '#inline' => true,
        );

        $form['table-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
        );

        /**
         * fields
         */
        $form += $this->fields();

        $form['form-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        /**
         * setup common setting for forms
         */
        $form = $this->common_form_setup($form);

        /**
         * return form if current_user_can edit
         */
        if ( $this->current_user_can_edit) {
            return $form;
        }

        return wpcf_admin_common_only_show($form);
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
    public function box_where()
    {
        global $wp_roles;
        $options = array();
        $users_currently_supported = array();
        $form_types = array();
        foreach ( $wp_roles->role_names as $role => $name   ) {
            $options[$role]['#name'] = 'wpcf[group][supports][' . $role . ']';
            $options[$role]['#title'] = ucwords($role);
            $options[$role]['#default_value'] = ($this->update && !empty($this->update['show_for']) && in_array($role,
                $this->update['show_for'])) ? 1 : 0;
            $options[$role]['#value'] = $role;
            $options[$role]['#inline'] = TRUE;
            $options[$role]['#suffix'] = '<br />';
            $options[$role]['#id'] = 'wpcf-form-groups-show-for-' . $role;
            $options[$role]['#attributes'] = array('class' => 'wpcf-form-groups-support-post-type');
            if ($this->update && !empty($this->update['show_for']) && in_array($role,
                $this->update['show_for'])) {
                $users_currently_supported[] = ucwords($role);
            }
        }

        if (empty($users_currently_supported)) {
            $users_currently_supported[] = __('Displayed for all users roles', 'wpcf');
        }

        /*
         * Show for FILTER
         */
        $temp = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'wpcf[group][supports]',
            '#inline' => true,
        );
        /*
         *
         * Here we use unique function for all filters
         * Since Types 1.1.4
         */
        $form_users = _wpcf_filter_wrap('custom_post_types',
            __('Show For:', 'wpcf'),
            implode(', ', $users_currently_supported),
            __('Displayed for all users roles', 'wpcf'), $temp);

        /*
         * Now starting form
         */
        $access_notification = '';
        if (function_exists('wpcf_access_register_caps')){
            $access_notification = '<div class="message custom wpcf-notif"><span class="wpcf-notif-congrats">'
                . __('This groups visibility is also controlled by the Access plugin.', 'wpcf')  .'</span></div>';
        }
        $form['supports-table-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<p class="description">%s</p>',
                __('Each usermeta group can display different fields for user roles.', 'wpcf')
            ).$access_notification,
        );
        /*
         * Join filter forms
         */
        // User Roles
        $form['p_wrap_1_' . wpcf_unique_id(serialize($form_users))] = array(
            '#type' => 'markup',
            '#markup' => '<p class="wpcf-filter-wrap">',
        );
        $form = $form + $form_users;

        /**
         * setup common setting for forms
         */
        $form = $this->common_form_setup($form);

        /**
         * render form
         */
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    public function types_styling_editor() {
        $form = $this->add_admin_style( array() );

        $form = wpcf_form( __FUNCTION__, $form );
        echo $form->renderForm();
    }

    /**
     * deprecated
     */
    private function add_admin_style($form)
    {

            $admin_styles_value = $preview_profile = $edit_profile = '';
            if ( isset( $this->update['admin_styles'] ) ) {
                $admin_styles_value = $this->update['admin_styles'];
            }
            $temp = array();

            if ( $this->update ) {
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
                // require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta.php';
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
                require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
                //Get sample post
                $post = query_posts( 'posts_per_page=1' );


                if ( !empty( $post ) && count( $post ) != '' ) {
                    $post = $post[0];
                }
                $preview_profile = wpcf_admin_post_meta_box_preview( $post, $this->update, 1 );
                $group = $this->update;
                $group['fields'] = wpcf_admin_post_process_fields( $post, wpcf_ensarr( wpcf_getarr( $group, 'fields' ) ), true, false );
                $edit_profile = wpcf_admin_post_meta_box( $post, $group, 1, true );
                add_action( 'admin_enqueue_scripts', 'wpcf_admin_fields_form_fix_styles', PHP_INT_MAX  );
            }

            $temp[] = array(
                '#type' => 'radio',
                '#suffix' => '<br />',
                '#value' => 'edit_mode',
                '#title' => 'Edit mode',
                '#name' => 'wpcf[group][preview]', '#default_value' => '',
                '#before' => '<div class="wpcf-admin-css-preview-style-edit">',
                '#inline' => true,
                '#attributes' => array('onclick' => 'changePreviewHtml(\'editmode\')', 'checked' => 'checked')
            );

            $temp[] = array(
                '#type' => 'radio',
                '#title' => 'Read Only',
                '#name' => 'wpcf[group][preview]', '#default_value' => '',
                '#after' => '</div>',
                '#inline' => true,
                '#attributes' => array('onclick' => 'changePreviewHtml(\'readonly\')')
            );

            $temp[] = array(
                '#type' => 'textarea',
                '#name' => 'wpcf[group][admin_html_preview]',
                '#inline' => true,
                '#id' => 'wpcf-form-groups-admin-html-preview',
                '#before' => '<h3>Field group HTML</h3>'
            );

            $temp[] = array(
                '#type' => 'textarea',
                '#name' => 'wpcf[group][admin_styles]',
                '#inline' => true,
                '#value' => $admin_styles_value,
                '#default_value' => '',
                '#id' => 'wpcf-form-groups-css-fields-editor',
                '#after' => '
                <div class="wpcf-update-preview-btn"><input type="button" value="Update preview" onclick="wpcfPreviewHtml()" style="float:right;" class="button-secondary"></div>
                <h3>'.__('Field group preview', 'wpcf').'</h3>
                <div id="wpcf-update-preview-div">Preview here</div>
                <script type="text/javascript">
var wpcfReadOnly = ' .  json_encode( base64_encode( $preview_profile) ) . ';
var wpcfEditMode = ' .  json_encode( base64_encode($edit_profile) ) . ';
var wpcfDefaultCss = ' .  json_encode( base64_encode($admin_styles_value) ) . ';
        </script>
        ',
        '#before' => sprintf('<h3>%s</h3>', __('Your CSS', 'wpcf')),
        );

        $admin_styles = _wpcf_filter_wrap( 'admin_styles',
            __( 'Admin styles for fields:', 'wpcf' ), '', '', $temp,
            __( 'Open style editor', 'wpcf' ) );
        $form['p_wrap_1_' . wpcf_unique_id( serialize( $admin_styles ) )] = array(
            '#type' => 'markup',
            '#markup' => '<p class="wpcf-filter-wrap">',
        );
        $form = $form + $admin_styles;
        $form['adminstyles-table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</td></tr></tbody></table><br />',
        );
        return $form;
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
    public function save()
    {
        if (
            !isset($_POST['wpcf'])
            || !isset($_POST['wpcf']['group'])
            || !isset($_POST['wpcf']['group']['name'])
        ) {
            return false;
        }

        $_POST['wpcf']['group'] = apply_filters('wpcf_group_pre_save', $_POST['wpcf']['group']);

        $group_name = wp_kses_post($_POST['wpcf']['group']['name']);

        require_once WPCF_EMBEDDED_ABSPATH . '/classes/forms.php';
        $form  = new Enlimbo_Forms_Wpcf();

        if ( empty($group_name) ) {
            $form->triggerError();
            wpcf_admin_message( __( 'Group name can not be empty.', 'wpcf' ), 'error');
            return $form;
        }

        $new_group = false;

        $group_slug = sanitize_title($group_name);

        // Basic check


        if (isset($_REQUEST[$this->get_id])) {
            // Check if group exists
            $post = get_post(intval($_REQUEST[$this->get_id]));
            // Name changed
            if (strtolower($group_name) != strtolower($post->post_title)) {
                // Check if already exists
                $exists = get_page_by_title($group_name, 'OBJECT', TYPES_USER_META_FIELD_GROUP_CPT_NAME);
                if (!empty($exists)) {
                    $form->triggerError();
                    wpcf_admin_message(
                        sprintf(
                            __("A group by name <em>%s</em> already exists. Please use a different name and save again.", 'wpcf'),
                            apply_filters( 'the_title', $exists->post_title, $exists->ID )
                        ),
                        'error'
                    );
                    return $form;
                }
            }
            if (empty($post) || $post->post_type != TYPES_USER_META_FIELD_GROUP_CPT_NAME) {
                $form->triggerError();
                wpcf_admin_message(sprintf(__("Wrong group ID %d", 'wpcf'), intval($_REQUEST[$this->get_id])), 'error');
                return $form;
            }
            $group_id = $post->ID;

        } else {
            $new_group = true;
            // Check if already exists
            $exists = get_page_by_title($group_name, 'OBJECT', TYPES_USER_META_FIELD_GROUP_CPT_NAME);
            if (!empty($exists)) {
                $form->triggerError();
                wpcf_admin_message(
                    sprintf(
                        __("A group by name <em>%s</em> already exists. Please use a different name and save again.", 'wpcf'),
                        apply_filters( 'the_title', $exists->post_title, $exists->ID )
                    ),
                    'error'
                );
                return $form;
            }
        }

        // Save fields for future use
        $fields = array();
        if (!empty($_POST['wpcf']['fields'])) {
            foreach ($_POST['wpcf']['fields'] as $key => $field) {
                $field = wpcf_sanitize_field($field);
                $field = apply_filters('wpcf_field_pre_save', $field);

                if (!empty($field['is_new'])) {
                    // Check name and slug
                    if (wpcf_types_cf_under_control('check_exists',
                        sanitize_title($field['name']), TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta')) {
                        $form->triggerError();
                        wpcf_admin_message(sprintf(__('Field with name "%s" already exists',
                            'wpcf'), $field['name']), 'error');
                        return $form;
                    }
                    if (isset($field['slug']) && wpcf_types_cf_under_control('check_exists',
                        sanitize_title($field['slug']), TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta')) {
                        $form->triggerError();
                        wpcf_admin_message(sprintf(__('Field with slug "%s" already exists',
                            'wpcf'), $field['slug']), 'error');
                        return $form;
                    }
                }
                // Field ID and slug are same thing
                $field_id = wpcf_admin_fields_save_field( $field, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta' );
                if (!empty($field_id)) {
                    $fields[] = $field_id;
                }

            }
        }

        // Save group
        $roles = isset($_POST['wpcf']['group']['supports']) ? $_POST['wpcf']['group']['supports'] : array();
        /**
         * Admin styles
         */
        if ( isset( $_POST['wpcf']['group']['admin_styles'] ) ) {
            $admin_style = esc_html($_POST['wpcf']['group']['admin_styles']);
        }
        // Rename if needed
        if (isset($_REQUEST[$this->get_id])) {
            $_POST['wpcf']['group']['id'] = intval($_REQUEST[$this->get_id]);
        }

        $group_id = wpcf_admin_fields_save_group($_POST['wpcf']['group'], TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'user');

        // Set open fieldsets
        if ($new_group && !empty($group_id)) {
            $open_fieldsets = get_user_meta(get_current_user_id(), 'wpcf-group-form-toggle', true);
            if (isset($open_fieldsets[-1])) {
                $open_fieldsets[$group_id] = $open_fieldsets[-1];
                unset($open_fieldsets[-1]);
                update_user_meta(get_current_user_id(), 'wpcf-group-form-toggle', $open_fieldsets);
            }
        }

        // Rest of processes
        if (!empty($group_id)) {
            wpcf_admin_fields_save_group_fields($group_id, $fields, false, TYPES_USER_META_FIELD_GROUP_CPT_NAME);
            $this->save_group_showfor($group_id, $roles);
            /**
             * Admin styles
             */
            if (
                defined('TYPES_USE_STYLING_EDITOR')
                && TYPES_USE_STYLING_EDITOR
                && isset($admin_style)
            ) {
                wpcf_admin_fields_save_group_admin_styles($group_id, $admin_style);
            }
            $_POST['wpcf']['group']['fields'] = isset($_POST['wpcf']['fields']) ? $_POST['wpcf']['fields'] : array();

            do_action( 'types_fields_group_saved', $group_id );
            do_action( 'types_fields_group_user_saved', $group_id );

            // do not use this hook any longer
            do_action('wpcf_save_group', $_POST['wpcf']['group']);

            wp_safe_redirect(
                admin_url(sprintf('admin.php?page=wpcf-edit-usermeta&group_id=%d', $group_id))
            );
            exit;
        } else {
            wpcf_admin_message_store(__('Error saving group', 'wpcf'), 'error');
        }
    }

    /**
     * Saves group's user roles.
     *
     * @param type $group_id
     * @param type $post_types
     */
    private function save_group_showfor($group_id, $post_types)
    {
        if (empty($post_types)) {
            update_post_meta($group_id, '_wp_types_group_showfor', 'all');
            return true;
        }
        $post_types = ',' . implode(',', (array) $post_types) . ',';
        update_post_meta($group_id, '_wp_types_group_showfor', $post_types);
    }
}
