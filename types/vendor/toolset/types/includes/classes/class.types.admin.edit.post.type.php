<?php

require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.page.php';
include_once WPCF_INC_ABSPATH.'/common-functions.php';

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
class Types_Admin_Edit_Post_Type extends Types_Admin_Page
{
    private $fields;

    public function __construct()
    {
        add_action('wp_ajax_wpcf_edit_post_get_child_fields_screen', array($this, 'prepare_field_select_screen'));
        add_action('wp_ajax_wpcf_edit_post_get_icons_list', array($this, 'get_icons_list'));
        add_action('wp_ajax_wpcf_edit_post_save_child_fields', array($this, 'save_child_fields'));
        add_action('wp_ajax_wpcf_edit_post_save_custom_fields_groups', array($this, 'save_custom_fields_groups'));
        add_filter('types_get_post_type_slug_from_request', array($this, 'get_post_type_slug_from_request'));
    }

    public function init_admin()
    {
        if ( is_admin() ) {
            include_once WPCF_INC_ABSPATH.'/classes/class.types.admin.fields.php';
            $this->fields = new Types_Admin_Fields();
        }

        $this->init_hooks();
        $this->get_id = 'wpcf-post-type';

        $this->post_type = 'post_type';

        $this->boxes = array(
            'submitdiv' => array(
                'callback' => array($this, 'box_submitdiv'),
                'title' => __('Save', 'wpcf'),
                'default' => 'side',
                'priority' => 'core',
            ),
            'types_labels' => array(
                'callback' => array($this, 'box_labels'),
                'title' => __('Labels', 'wpcf'),
                'default' => 'normal',
                'post_types' => 'custom',
                'priority' => 'core',
            ),
            'types_taxonomies' => array(
                'callback' => array($this, 'box_taxonomies'),
                'title' => __('Taxonomies to be used with <i class="js-wpcf-singular"></i>', 'wpcf'),
                'default' => 'normal',
                'priority' => 'core',
            ),
            'types_display_sections' => array(
                'callback' => array($this, 'box_display_sections'),
                'title' => __('Sections to display when editing <i class="js-wpcf-singular"></i>', 'wpcf'),
                'default' => 'normal',
                'priority' => 'low',
                'post_types' => 'custom',
            ),
            'types_options' => array(
                'callback' => array($this, 'box_options'),
                'title' => __('Options', 'wpcf'),
                'default' => 'normal',
                'post_types' => 'custom',
                'priority' => 'low',
            ),
        );

        $this->boxes = apply_filters('wpcf_meta_box_order_defaults', $this->boxes, $this->post_type);

        $this->boxes = apply_filters('wpcf_meta_box_post_type', $this->boxes);

        /**
         * WPML integration
         */
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $this->boxes['wpml'] = array(
                'callback' => array($this, 'wpml_box'),
                'title'    => __( 'Translation', 'wpcf' ),
                'default'  => 'normal',
                'priority' => 'low',
            );
        }

        /** This action is documented in includes/classes/class.types.admin.page.php  */
        add_action('wpcf_closedpostboxes', array($this, 'closedpostboxes'));

    }

    /**
     * Add/edit form
     */
    public function form()
    {
        $this->save();

	    // Flush rewrite rules if we're asked to do so.
	    //
	    // This must be done after all post types and taxonomies are registered, and they can be registered properly
	    // only on 'init'. So after making changes, we need to reload the page and THEN flush.
	    if( '1' == wpcf_getget( 'flush', '0' ) ) {
		    flush_rewrite_rules();
	    }


        global $wpcf;

        $id = false;
        $update = false;

        if ( isset( $_GET[$this->get_id] ) ) {
            $id = sanitize_text_field( $_GET[$this->get_id] );
        } elseif ( isset( $_POST[$this->get_id] ) ) {
            $id = sanitize_text_field( $_POST[$this->get_id] );
        }

        /**
         * get current post type
         */
        require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.post-type.php';
        $wpcf_post_type = new Types_Admin_Post_Type();
        $custom_post_type = $wpcf_post_type->get_post_type($id);
        if (empty($custom_post_type)) {
            wpcf_admin_message( __( 'Please save new Post Type first.', 'wpcf' ), 'error' );
            die;
        }
        $this->ct = $custom_post_type;

        $current_user_can_edit = WPCF_Roles::user_can_edit('custom-post-type', $this->ct);

        /**
         * sanitize _builtin
         */
        if ( !isset($this->ct['_builtin']) ) {
            $this->ct['_builtin'] = false;
        }

        /**
         * fix taxonomies assigment for builitin post types
         */
        if ( $this->ct['_builtin']) {
            $taxonomies = get_taxonomies( '', 'objects' );
            foreach( $taxonomies as $slug => $tax ) {
                foreach( $tax->object_type as $post_slug ) {
                    if ( $this->ct['slug'] == $post_slug) {
                        $this->ct['taxonomies'][$slug] = 1;
                    }
                }
            }
        }

        $form = $this->prepare_screen();

        if ( $current_user_can_edit && $this->ct['update'] ) {
            $form['id'] = array(
                '#type' => 'hidden',
                '#value' => $id,
                '#name' => 'ct[wpcf-post-type]',
                '_builtin' => true,
            );

	        $form['slug_conflict_check_nonce'] = array(
		        '#type' => 'hidden',
		        '#value' => wp_create_nonce( Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS ),
		        '#name' => 'types_check_slug_conflicts_nonce',
		        '_builtin' => true,
	        );
	        
            /**
             * update Taxonomy too
             */
            $custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
            foreach( $custom_taxonomies as $slug => $data ) {
                if ( !array_key_exists('supports', $data)) {
                    continue;
                }
                if ( !array_key_exists($id, $data['supports']) ) {
                    continue;
                }
                if (
                    array_key_exists('taxonomies', $this->ct)
                    && array_key_exists($slug, $this->ct['taxonomies'])
                ) {
                    continue;
                }
                unset($custom_taxonomies[$slug]['supports'][$id]);
            }
            update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $custom_taxonomies);
        }

        /*
         * menu icon
         */
        switch( $this->ct['slug'] ) {
            case 'page':
                $menu_icon = 'admin-page';
                break;
            case 'attachment':
                $menu_icon = 'admin-media';
                break;
            default:
                $menu_icon = isset( $this->ct['icon']) && !empty($this->ct['icon']) ? $this->ct['icon'] : 'admin-post';
                break;
        }

        /**
         * post icon field
         */
        $form['icon'] = array(
            '#type' => 'hidden',
            '#name' => 'ct[icon]',
            '#value' => $menu_icon,
            '#id' => 'wpcf-types-icon',
        );

        $form['form-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="post-body-content" class="%s">',
                $current_user_can_edit? '':'wpcf-types-read-only'
            ),
            '_builtin' => true,
        );

        $form['table-1-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table id="wpcf-types-form-name-table" class="wpcf-types-form-table widefat js-wpcf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'wpcf' ) . '</th></tr></thead><tbody>',
            '_builtin' => true,
        );
        $table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
        $form['name'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][name]',
            '#title' => __( 'Name plural', 'wpcf' ) . ' (<strong>' . __( 'required', 'wpcf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in plural!', 'wpcf' )
            . '.',
            '#value' => isset( $this->ct['labels']['name'] ) ? $this->ct['labels']['name'] : '',
            '#validate' => array(
                'required' => array('value' => 'true'),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#id' => 'name-plural',
            '#attributes' => array(
                'data-wpcf_warning_same_as_slug' => $wpcf->post_types->message( 'warning_singular_plural_match' ),
                'data-wpcf_warning_same_as_slug_ignore' => $wpcf->post_types->message( 'warning_singular_plural_match_ignore' ),
                'placeholder' => __('Enter Post Type name plural', 'wpcf' ),
                'class' => 'large-text',
            ),
            '_builtin' => true,
        );
        $form['name-singular'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][singular_name]',
            '#title' => __( 'Name singular', 'wpcf' ) . ' (<strong>' . __( 'required', 'wpcf' ) . '</strong>)',
            '#description' => '<strong>' . __( 'Enter in singular!', 'wpcf' )
            . '</strong><br />'
            . '.',
            '#value' => isset( $this->ct['labels']['singular_name'] ) ? $this->ct['labels']['singular_name'] : '',
            '#validate' => array(
                'required' => array('value' => 'true'),
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#id' => 'name-singular',
            '#attributes' => array(
                'placeholder' => __('Enter Post Type name singular', 'wpcf' ),
                'class' => 'js-wpcf-slugize-source large-text',
                'data-anonymous-post-type' => __( 'this Post Type', 'wpcf' ),
            ),
            '_builtin' => true,
        );

        /**
         * IF isset $_POST['slug'] it means form is not submitted
         */
        $attributes = array();
        if ( !empty( $_POST['ct']['slug'] ) ) {
            $reserved = wpcf_is_reserved_name( sanitize_text_field( $_POST['ct']['slug'] ), 'post_type' );
            if ( is_wp_error( $reserved ) ) {
                $attributes = array(
                    'class' => 'wpcf-form-error',
                    'onclick' => 'jQuery(this).removeClass(\'wpcf-form-error\');'
                );
            }
        }

        $form['slug'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[slug]',
            '#title' => __( 'Slug', 'wpcf' ) . ' (<strong>' . __( 'required', 'wpcf' ) . '</strong>)',
            '#value' => isset( $this->ct['slug'] ) ? $this->ct['slug'] : '',
            '#pattern' => $table_row,
            '#inline' => true,
            '#validate' => array(
                'required' => array('value' => 'true'),
                'nospecialchars' => array('value' => 'true'),
                'maxlength' => array('value' => '20'),
            ),
            '#attributes' => $attributes + array(
                'maxlength' => '20',
                'placeholder' => __('Enter Post Type slug', 'wpcf' ),
                'class' => 'js-wpcf-slugize large-text',
            ),
            '#id' => 'slug',
            '_builtin' => true,
        );

        // disable for inbuilt
        if ( $this->ct['_builtin'] ) {
            $form['slug']['#disable'] = 1;
            $form['slug']['#pattern'] = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><DESCRIPTION><AFTER></td></tr>';
            $form['slug']['#description'] = __('This option is not available for built-in post types.', 'wpcf');
        }

        $form['description'] = array(
            '#type' => 'textarea',
            '#name' => 'ct[description]',
            '#title' => __( 'Description', 'wpcf' ),
            '#value' => isset( $this->ct['description'] ) ? $this->ct['description'] : '',
            '#attributes' => array(
                'rows' => 4,
                'cols' => 60,
                'placeholder' => __('Enter Post Type description', 'wpcf' ),
                'class' => 'hidden js-wpcf-description',
            ),
            '#pattern' => $table_row,
            '#inline' => true,
            '#after' => sprintf(
                '<a class="js-wpcf-toggle-description hidden" href="#">%s</a>',
                __('Add description', 'wpcf')
            ),
        );
        /**
         * icons only for version 3.8 up
         */
        global $wp_version;
        if ( version_compare( '3.8', $wp_version ) < 1 ) {
            $form['choose-icon'] = array(
                '#name' => 'choose-icon',
                '#type' => 'button',
                '#value' => esc_attr__('Change icon', 'wpcf'),
                '#inline' => true,
                '#title' => __('Icon', 'wpcf'),
                '#pattern' => '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><DESCRIPTION><AFTER></td></tr>',
                '#attributes' => array(
                    'data-wpcf-nonce' => wp_create_nonce('post-type-dashicons-list'),
                    'data-wpcf-post-type' => esc_attr($this->ct['slug']),
                    'data-wpcf-message-loading' => esc_attr__('Please Wait, Loading…', 'wpcf'),
                    'data-wpcf-title' => esc_attr__('Choose icon', 'wpcf'),
                    'data-wpcf-cancel' => esc_attr__('Cancel', 'wpcf'),
                    'data-wpcf-value' => esc_attr($menu_icon),
                    'class' => 'js-wpcf-choose-icon',
                ),
                '#before' => sprintf(
                    '<div class="wpcf-types-menu-image dashicons-before dashicons-%s"><br></div>',
                    esc_attr($menu_icon)
                ),
            );
            /**
             * clear ability to change for builitin post types
             */
            if ( $this->ct['_builtin'] ) {
                $form['choose-icon']['#disable'] = 1;
                $form['choose-icon']['#description'] = __('This option is not available for built-in post types.', 'wpcf');
            }
        }
        $form['table-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
            '_builtin' => true,
        );

        $form['box-1-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        /**
         * return form if current_user_can edit
         */
        if ( $current_user_can_edit) {
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
    function box_submitdiv()
    {
        $button_text = __( 'Save Post Type', 'wpcf' );

        $form = array();

        $form['visibility-begin'] = array(
            '#type' => 'markup',
            '#markup' => ' <div class="misc-pub-section misc-pub-visibility" id="visibility">',
            '_builtin' => true,
        );

        $form['visibility-status'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '%s: <span id="post-visibility-display">%s</span>',
                __('Status', 'wpcf'),
                (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? __('Draft', 'wpcf'):__('Published', 'wpcf')
            ),
            '_builtin' => true,
        );

        $form['visibility-choose-begin'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                ' <a href="#visibility" class="edit-visibility hide-if-no-js"><span aria-hidden="true">%s</span> <span class="screen-reader-text">%s</span></a>',
                __('Edit', 'wpcf'),
                __('Edit status', 'wpcf')
            ),
            '_builtin' => true,
        );

        $form['visibility-edit-begin'] = array(
            '#type' => 'markup',
            '#markup' => '<div id="post-visibility-select" class="hide-if-js">',
            '_builtin' => true,
        );

        $form['visibility-choose-public'] = array(
            '#type' => 'radios',
            '#name' => 'ct[public]',
            '#options' => array(
                sprintf(
                    '<span class="title">%s</span>',
                    __('Published', 'wpcf')
                ) => 'public',
                sprintf(
                    '<span class="title">%s</span> <span class="description">(%s)</span>',
                    __('Draft', 'wpcf'),
                    __('not visible in admin menus, no user-interface to administrate posts, not queryable on front-end', 'wpcf')
                ) => 'hidden',
            ),
            '#default_value' => (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? 'hidden' : 'public',
            '#inline' => true,
        );

        $form['wpcf-types-form-visiblity-toggle-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="wpcf-types-form-visiblity-toggle" %s>',
                (isset( $this->ct['public'] ) && strval( $this->ct['public'] ) == 'hidden') ? ' class="hidden"' : ''
            ),
        );

        $menu_positions = array(
            'menu-dashboard'    => 2,
            'menu-posts'        => 5,
            'menu-media'        => 10,
            'menu-pages'        => 20,
            'menu-comments'     => 25,
            'menu-appearance'   => 60,
            'menu-plugins'      => 65,
            'menu-users'        => 70,
            'menu-tools'        => 75,
            'menu-settings'     => 80
        );

        $menu_position = 2;
        $options = array(
            __('--- not set ---') => ''
        );

        foreach( $GLOBALS['menu'] as $menu_item ) {
            // skip menu separators
            if( empty( $menu_item[0] ) || $menu_item[4] == 'wp-menu-separator' )
                continue;

            // update menu position
            if( array_key_exists( $menu_item[5], $menu_positions ) )
                $menu_position = $menu_positions[$menu_item[5]];

            $option_name = strip_tags( preg_replace( '#<([a-z]+).*?>.*?</\\1>#uis', '', $menu_item[0] ) );

            // don't show current cpt in list "Admin Menu position after:"
            if( 'edit.php?post_type=' . $this->ct['slug'] == $menu_item[2] )
                continue;

            // add menu item to options
            $options[$option_name] = $menu_position . '--wpcf-add-menu-after--' . $menu_item[2];
        }

        $form['wpcf-types-form-visiblity-toggle-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        $form['visibility-edit-end'] = array(
            '#type' => 'markup',
            '#markup' => '<p>
 <a href="#visibility" class="save-post-visibility hide-if-no-js button">OK</a>
 <a href="#visibility" class="cancel-post-visibility hide-if-no-js button-cancel">Cancel</a>
</p>
</div>',
            '_builtin' => true,
        );

        $form['visibility-end'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        /**
         * admin menu position
         */
        $form['menu_position'] = array(
            '#type' => 'select',
            '#name' => 'ct[menu_position]',
            '#title' => __( 'Admin Menu position after: ', 'wpcf' ),
            '#default_value' => isset( $this->ct['menu_position'] ) ? $this->ct['menu_position'] : '',
            // '#validate' => array('number' => array('value' => true)),
            '#inline' => true,
            '#pattern' => '<BEFORE><p><LABEL><ELEMENT><ERROR></p><AFTER>',
            '#options' => $options,
            '#before' => '<div class="misc-pub-section">',
            '#after' => '</div>',
            '#attributes' => array(
                'class' => 'js-wpcf-menu-position-after widefat',
                'data-wpcf-menu-position' => isset( $this->ct['menu_position'] ) ? $this->ct['menu_position'] : ''
            ),
        );
        /**
         * dashboard glance option to show counters on admin dashbord widget
         */
        if( $this->ct['slug'] != 'post' && $this->ct['slug'] != 'page' ) {
            $form['dashboard_glance'] = array(
                '#type' => 'checkbox',
                '#before' => '<div class="misc-pub-section">',
                '#after' => '</div>',
                '#name' => 'ct[dashboard_glance]',
                '#title' => __( 'Show number of entries on "At a Glance" admin widget.', 'wpcf' ),
                '#default_value' => !empty( $this->ct['dashboard_glance'] ),
            );
        }

        $form = $this->submitdiv($button_text, $form);

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
    public function box_options()
    {
        $form = array();
        $form['rewrite-enabled'] = array(
            '#type' => 'checkbox',
            '#title' => __( 'Rewrite', 'wpcf' ),
            '#name' => 'ct[rewrite][enabled]',
            '#description' => __( 'Rewrite permalinks with this format. False to prevent rewrite. Default: true and use post type as slug.', 'wpcf' ),
            '#default_value' => !empty( $this->ct['rewrite']['enabled'] ),
            '#inline' => true,
        );
        $form['rewrite-custom'] = array(
            '#type' => 'radios',
            '#name' => 'ct[rewrite][custom]',
            '#options' => array(
                __( 'Use the normal WordPress URL logic', 'wpcf' ) => 'normal',
                __( 'Use a custom URL format', 'wpcf' ) => 'custom',
            ),
            '#default_value' => empty( $this->ct['rewrite']['custom'] ) || $this->ct['rewrite']['custom'] != 'custom' ? 'normal' : 'custom',
            '#inline' => true,
            '#after' => '<br />',
        );
        $hidden = empty( $this->ct['rewrite']['custom'] ) || $this->ct['rewrite']['custom'] != 'custom' ? ' class="hidden"' : '';
        $form['rewrite-slug'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[rewrite][slug]',
            '#description' => __( 'Optional.', 'wpcf' ) . ' ' . __( "Prepend posts with this slug - defaults to post type's name.", 'wpcf' ),
            '#value' => isset( $this->ct['rewrite']['slug'] ) ? $this->ct['rewrite']['slug'] : '',
            '#inline' => true,
            '#before' => '<div id="wpcf-types-form-rewrite-toggle"' . $hidden . '>',
            '#after' => '</div>',
            '#validate' => array('rewriteslug' => array('value' => 'true')),
            '#attributes' => array(
                'class' => 'widefat',
            ),
        );
        $form['rewrite-with_front'] = array(
            '#type' => 'checkbox',
            '#title' => __( 'Allow permalinks to be prepended with front base', 'wpcf' ),
            '#name' => 'ct[rewrite][with_front]',
            '#description' => __( 'Example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/.', 'wpcf' ) . ' ' . __( 'Defaults to true.', 'wpcf' ),
            '#default_value' => !empty( $this->ct['rewrite']['with_front'] ),
            '#inline' => true,
        );
        $form['rewrite-feeds'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[rewrite][feeds]',
            '#title' => __( 'Feeds', 'wpcf' ),
            '#description' => __( 'Defaults to has_archive value.', 'wpcf' ),
            '#default_value' => !empty( $this->ct['rewrite']['feeds'] ),
            '#value' => 1,
            '#inline' => true,
        );
        $form['rewrite-pages'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[rewrite][pages]',
            '#title' => __( 'Pages', 'wpcf' ),
            '#description' => __( 'Defaults to true.', 'wpcf' ),
            '#default_value' => !empty( $this->ct['rewrite']['pages'] ),
            '#value' => 1,
            '#inline' => true,
        );
        $show_in_menu_page = isset( $this->ct['show_in_menu_page'] ) ? $this->ct['show_in_menu_page'] : '';
        $hidden = !empty( $this->ct['show_in_menu'] ) ? '' : ' class="hidden"';

        $has_archive_slug = isset( $this->ct['has_archive_slug'] ) ? $this->ct['has_archive_slug'] : '';
        $has_archive_slug_show = empty( $this->ct['has_archive'] )? ' class="hidden"':'';

        $form['vars'] = array(
            '#type' => 'checkboxes',
            '#name' => 'ct[vars]',
            '#inline' => true,
            '#options' => array(
                'has_archive' => array(
                    '#name' => 'ct[has_archive]',
                    '#default_value' => !empty( $this->ct['has_archive'] ),
                    '#title' => __( 'has_archive', 'wpcf' ),
                    '#description' => __( 'Allow to have custom archive slug for CPT.', 'wpcf' ) . '<br />' . __( 'Default: not set.', 'wpcf' ),
                    '#inline' => true,
                    '#after' => '<div id="wpcf-types-form-has_archive-toggle"' . $has_archive_slug_show . '><input type="text" name="ct[has_archive_slug]" class="regular-text" value="' . $has_archive_slug . '" /><div class="description wpcf-form-description wpcf-form-description-checkbox description-checkbox">' . __( 'Optional.', 'wpcf' ) . ' ' . __( 'Default is value of rewrite or CPT slug.', 'wpcf' ) . '</div></div>',
                ),
                'show_in_menu' => array(
                    '#name' => 'ct[show_in_menu]',
                    '#default_value' => !empty( $this->ct['show_in_menu'] ),
                    '#title' => __( 'show_in_menu', 'wpcf' ),
                    '#description' => __( 'Whether to show the post type in the admin menu and where to show that menu. Note that show_ui must be true.', 'wpcf' ) . '<br />' . __( 'Default: null.', 'wpcf' ),
                    '#after' => '<div id="wpcf-types-form-showinmenu-toggle"' . $hidden . '><input type="text" name="ct[show_in_menu_page]" class="regular-text" value="' . $show_in_menu_page . '" /><div class="description wpcf-form-description wpcf-form-description-checkbox description-checkbox">' . __( 'Optional.', 'wpcf' ) . ' ' . __( "Top level page like 'tools.php' or 'edit.php?post_type=page'", 'wpcf' ) . '</div></div>',
                    '#inline' => true,
                ),
                'show_ui' => array(
                    '#name' => 'ct[show_ui]',
                    '#default_value' => !empty( $this->ct['show_ui'] ),
                    '#title' => __( 'show_ui', 'wpcf' ),
                    '#description' => __( 'Generate a default UI for managing this post type.', 'wpcf' ) . '<br />' . __( 'Default: value of public argument.', 'wpcf' ),
                    '#inline' => true,
                ),
                'publicly_queryable' => array(
                    '#name' => 'ct[publicly_queryable]',
                    '#default_value' => !empty( $this->ct['publicly_queryable'] ),
                    '#title' => __( 'publicly_queryable', 'wpcf' ),
                    '#description' => __( 'Whether post_type queries can be performed from the front end.', 'wpcf' ) . '<br />' . __( 'Default: value of public argument.', 'wpcf' ),
                    '#inline' => true,
                ),
                'exclude_from_search' => array(
                    '#name' => 'ct[exclude_from_search]',
                    '#default_value' => !empty( $this->ct['exclude_from_search'] ),
                    '#title' => __( 'exclude_from_search', 'wpcf' ),
                    '#description' => __( 'Whether to exclude posts with this post type from search results.', 'wpcf' ) . '<br />' . __( 'Default: value of the opposite of the public argument.', 'wpcf' ),
                    '#inline' => true,
                ),
                'hierarchical' => array(
                    '#name' => 'ct[hierarchical]',
                    '#default_value' => !empty( $this->ct['hierarchical'] ),
                    '#title' => __( 'hierarchical', 'wpcf' ),
                    '#description' => __( 'Whether the post type is hierarchical. Allows Parent to be specified.', 'wpcf' ) . '<br />' . __( 'Default: false.', 'wpcf' ),
                    '#inline' => true,
                ),
                'can_export' => array(
                    '#name' => 'ct[can_export]',
                    '#default_value' => !empty( $this->ct['can_export'] ),
                    '#title' => __( 'can_export', 'wpcf' ),
                    '#description' => __( 'Can this post_type be exported.', 'wpcf' ) . '<br />' . __( 'Default: true.', 'wpcf' ),
                    '#inline' => true,
                ),
                'show_in_nav_menus' => array(
                    '#name' => 'ct[show_in_nav_menus]',
                    '#default_value' => !empty( $this->ct['show_in_nav_menus'] ),
                    '#title' => __( 'show_in_nav_menus', 'wpcf' ),
                    '#description' => __( 'Whether post_type is available for selection in navigation menus.', 'wpcf' ) . '<br />' . __( 'Default: value of public argument.', 'wpcf' ),
                    '#inline' => true,
                ),
            ),
        );
        $query_var = isset( $this->ct['query_var'] ) ? $this->ct['query_var'] : '';
        $hidden = !empty( $this->ct['query_var_enabled'] ) ? '' : ' class="hidden"';
        $form['query_var'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[query_var_enabled]',
            '#title' => 'query_var',
            '#description' => __( 'Disable to prevent queries like "mysite.com/?post_type=example". Enable to use queries like "mysite.com/?post_type=example". Enable and set a value to use queries like "mysite.com/?query_var_value=example"', 'wpcf' ) . '<br />' . __( 'Default: true - set to $post_type.', 'wpcf' ),
            '#default_value' => !empty( $this->ct['query_var_enabled'] ),
            '#after' => '<div id="wpcf-types-form-queryvar-toggle"' . $hidden . '><input type="text" name="ct[query_var]" value="' . $query_var . '" class="regular-text" /><div class="description wpcf-form-description wpcf-form-description-checkbox description-checkbox">' . __( 'Optional', 'wpcf' ) . '. ' . __( 'String to customize query var', 'wpcf' ) . '</div></div>',
            '#inline' => true,
        );
        $form['permalink_epmask'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[permalink_epmask]',
            '#title' => __( 'Permalink epmask', 'wpcf' ),
            '#description' => sprintf( __( 'Default value EP_PERMALINK. More info here %s.', 'wpcf' ),
            '<a href="http://core.trac.wordpress.org/ticket/12605" target="_blank">link</a>' ),
            '#value' => isset( $this->ct['permalink_epmask'] ) ? $this->ct['permalink_epmask'] : '',
            '#inline' => true,
        );

        $form['show_in_rest'] = array(
            '#type' => 'checkbox',
            '#name' => 'ct[show_in_rest]',
            '#default_value' => !empty( $this->ct['show_in_rest'] ),
            '#title' => __( 'show_in_rest', 'wpcf' ),
            '#description' => __( 'Whether to expose this post type in the REST API.', 'wpcf' ) . '<br />' . __( 'Default: false.', 'wpcf' ),
            '#inline' => true,
        );

        $form['rest_base'] = array(
            '#type' => 'textfield',
            '#name' => 'ct[rest_base]',
            '#title' => __( 'Rest Base', 'wpcf' ),
            '#description' => __( 'The base slug that this post type will use when accessed using the REST API.', 'wpcf' ) . '<br />' . __( 'Default: $post_type.', 'wpcf' ),
            '#value' => isset( $this->ct['rest_base'] ) ? $this->ct['rest_base'] : '',
            '#inline' => true,
        );

        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * post type properites
     */
    public function box_display_sections()
    {
        $form = array();
        $options = array(
            'title' => array(
                '#name' => 'ct[supports][title]',
                '#default_value' => !empty( $this->ct['supports']['title'] ),
                '#title' => __( 'Title', 'wpcf' ),
                '#description' => __( 'Text input field to create a post title.', 'wpcf' ),
                '#inline' => true,
                '#id' => 'wpcf-supports-title',
            ),
            'editor' => array(
                '#name' => 'ct[supports][editor]',
                '#default_value' => !empty( $this->ct['supports']['editor'] ),
                '#title' => __( 'Editor', 'wpcf' ),
                '#description' => __( 'Content input box for writing.', 'wpcf' ),
                '#inline' => true,
                '#id' => 'wpcf-supports-editor',
            ),
            'comments' => array(
                '#name' => 'ct[supports][comments]',
                '#default_value' => !empty( $this->ct['supports']['comments'] ),
                '#title' => __( 'Comments', 'wpcf' ),
                '#description' => __( 'Ability to turn comments on/off.', 'wpcf' ),
                '#inline' => true,
            ),
            'trackbacks' => array(
                '#name' => 'ct[supports][trackbacks]',
                '#default_value' => !empty( $this->ct['supports']['trackbacks'] ),
                '#title' => __( 'Trackbacks', 'wpcf' ),
                '#description' => __( 'Ability to turn trackbacks and pingbacks on/off.', 'wpcf' ),
                '#inline' => true,
            ),
            'revisions' => array(
                '#name' => 'ct[supports][revisions]',
                '#default_value' => !empty( $this->ct['supports']['revisions'] ),
                '#title' => __( 'Revisions', 'wpcf' ),
                '#description' => __( 'Allows revisions to be made of your post.', 'wpcf' ),
                '#inline' => true,
            ),
            'author' => array(
                '#name' => 'ct[supports][author]',
                '#default_value' => !empty( $this->ct['supports']['author'] ),
                '#title' => __( 'Author', 'wpcf' ),
                '#description' => __( 'Displays a dropdown menu for changing the post author.', 'wpcf' ),
                '#inline' => true,
            ),
            'excerpt' => array(
                '#name' => 'ct[supports][excerpt]',
                '#default_value' => !empty( $this->ct['supports']['excerpt'] ),
                '#title' => __( 'Excerpt', 'wpcf' ),
                '#description' => __( 'A text area for writing a custom excerpt.', 'wpcf' ),
                '#inline' => true,
            ),
            'thumbnail' => array(
                '#name' => 'ct[supports][thumbnail]',
                '#default_value' => !empty( $this->ct['supports']['thumbnail'] ),
                '#title' => __( 'Featured Image', 'wpcf' ),
                '#description' => __( 'Allows to upload a featured image to the post.', 'wpcf' ),
                '#inline' => true,
            ),
            'custom-fields' => array(
                '#name' => 'ct[supports][custom-fields]',
                '#default_value' => !empty( $this->ct['supports']['custom-fields'] ),
                '#title' => __( 'Custom Fields', 'wpcf' ),
                '#description' => __( "The native WordPress custom post fields list. If you don't select this, Types post fields will still display.", 'wpcf' ),
                '#inline' => true,
            ),
            'page-attributes' => array(
                '#name' => 'ct[supports][page-attributes]',
                '#default_value' => !empty( $this->ct['supports']['page-attributes'] ),
                '#title' => __( 'Page Attributes', 'wpcf' ),
                '#description' => __( 'Menu order and page parent (only available for hierarchical posts).', 'wpcf' ),
                '#inline' => true,
            ),
            'post-formats' => array(
                '#name' => 'ct[supports][post-formats]',
                '#default_value' => !empty( $this->ct['supports']['post-formats'] ),
                '#title' => __( 'Post Formats', 'wpcf' ),
                '#description' => __( 'A selector for the format to use for the post.', 'wpcf' ),
                '#inline' => true,
            ),
        );
        $form['supports'] = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#name' => 'ct[supports]',
            '#inline' => true,
        );
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Labels
     */
    public function box_labels()
    {
        $form = array();
        $labels = array(
            'add_new' => array(
                'title' => __( 'Add New', 'wpcf' ),
                'description' => __( 'The add new text. The default is Add New for both hierarchical and non-hierarchical types.', 'wpcf' ),
                'label' => __('Add New', 'wpcf'),
            ),
            'add_new_item' => array(
                'title' => __( 'Add New %s', 'wpcf' ),
                'description' => __( 'The add new item text. Default is Add New Post/Add New Page.', 'wpcf' ),
                'label' => __('Add New Item', 'wpcf'),
            ),
            'edit_item' => array(
                'title' => __( 'Edit %s', 'wpcf' ),
                'description' => __( 'The edit item text. Default is Edit Post/Edit Page.', 'wpcf' ),
                'label' => __('Edit Item', 'wpcf'),
            ),
            'new_item' => array(
                'title' => __( 'New %s', 'wpcf' ),
                'description' => __( 'The new item text. Default is New Post/New Page.', 'wpcf' ),
                'label' => __('New Item', 'wpcf'),
            ),
            'view_item' => array(
                'title' => __( 'View %s', 'wpcf' ),
                'description' => __( 'The view item text. Default is View Post/View Page.', 'wpcf' ),
                'label' => __('View Item', 'wpcf'),
            ),
            'search_items' => array(
                'title' => __( 'Search %s', 'wpcf' ),
                'description' => __( 'The search items text. Default is Search Posts/Search Pages.', 'wpcf' ),
                'label' => __('Search Items', 'wpcf'),
            ),
            'not_found' => array(
                'title' => __( 'No %s found', 'wpcf' ),
                'description' => __( 'The not found text. Default is No posts found/No pages found.', 'wpcf' ),
                'label' => __('Not Found', 'wpcf'),
            ),
            'not_found_in_trash' => array(
                'title' => __( 'No %s found in Trash', 'wpcf' ),
                'description' => __( 'The not found in trash text. Default is No posts found in Trash/No pages found in Trash.', 'wpcf' ),
                'label' => __('Not Found In Trash', 'wpcf'),
            ),
            'parent_item_colon' => array(
                'title' => __( 'Parent text', 'wpcf' ),
                'description' => __( "The parent text. This string isn't used on non-hierarchical types. In hierarchical ones the default is Parent Page.", 'wpcf' ),
                'label' => __('Parent Description', 'wpcf'),
            ),
            'all_items' => array(
                'title' => __( 'All items', 'wpcf' ),
                'description' => __( 'The all items text used in the menu. Default is the Name label.', 'wpcf' ),
                'label' => __('All Items', 'wpcf'),
            ),
            'enter_title_here' => array(
                'title' => __( 'Enter title here', 'wpcf' ),
                'description' => __( 'The text used as placeholder of post title. Default is the "Enter title here".', 'wpcf' ),
                'label' => __('Enter title here', 'wpcf'),
                'default_value' => __('Enter title here', 'wpcf'),
                'force_if_empty' => true,
            ),
        );
        $form['table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="wpcf-types-form-table widefat striped fixed"><tbody>',
            '_builtin' => true,
        );
        foreach ( $labels as $name => $data ) {
            /**
             * get value
             */
            $value = empty($this->ct['slug'])? $data['title']:(isset( $this->ct['labels'][$name] ) ? $this->ct['labels'][$name] : '');
            /**
             * force if empty
             */
            if (
                true
                && empty($value)
                && isset($data['force_if_empty'])
                && isset($data['default_value'])
                && $data['force_if_empty']
            ) {
                $value = $data['default_value'];
            }
            $form['labels-' . $name] = array(
                '#type' => 'textfield',
                '#name' => 'ct[labels][' . $name . ']',
                '#title' => $data['label'],
                '#description' => $data['description'],
                '#value' => $value,
                '#inline' => true,
                '#pattern' => '<tr><td><LABEL></td><td><ELEMENT><DESCRIPTION></td></tr>',
                '#attributes' => array(
                    'class' => 'widefat',
                ),
            );
        }
        $form['table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</tbody></table>',
            '_builtin' => true,
        );
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }

    /**
     * Render the content of the metabox "Taxonomies to be used with $post_type".
     *
     * @since unknown
     */
    public function box_taxonomies() {
	    $form = array();
	    $taxonomies = Types_Utils::get_editable_taxonomies();
	    $options = array();

	    foreach( $taxonomies as $taxonomy_slug => $taxonomy ) {

		    $options[ $taxonomy_slug ] = array(
			    '#name' => 'ct[taxonomies][' . $taxonomy_slug . ']',
			    '#title' => $taxonomy->labels->name,
			    '#default_value' => ( ! empty( $this->ct['taxonomies'][ $taxonomy_slug ] ) ),
			    '#inline' => true,
			    '#before' => '<li>',
			    '#after' => '</li>',
		    );
		    $options[ $taxonomy_slug ]['_builtin'] = $taxonomy->_builtin;

	    }

	    $form['taxonomies'] = array(
		    '#type' => 'checkboxes',
		    '#options' => $options,
		    '#name' => 'ct[taxonomies]',
		    '#inline' => true,
		    '#before' => '<ul class="wpcf-list">',
		    '#after' => '</ul>',
		    '_builtin' => true,
	    );
	    $form = wpcf_form( __FUNCTION__, $form );
	    echo $form->renderForm();
    }


    private function save()
    {
        global $wpcf;

        if ( !isset( $_POST['ct'] ) ) {
            return false;
        }
        $data = $_POST['ct'];
        $update = false;

        // Sanitize data
        $data['labels']['name'] = isset( $data['labels']['name'] )
            ? sanitize_text_field( $data['labels']['name'] )
            : '';

        $data['labels']['singular_name'] = isset( $data['labels']['singular_name'] )
            ? sanitize_text_field( $data['labels']['singular_name'] )
            : '';

        if (
            empty( $data['labels']['name'] )
            || empty( $data['labels']['singular_name'] )
        ) {
            wpcf_admin_message( __( 'Please set post type name', 'wpcf' ), 'error' );
            return false;
        }

        if ( isset( $data[$this->get_id] ) ) {
            $update = true;
            $data[$this->get_id] = sanitize_title( $data[$this->get_id] );
        } else {
            $data[$this->get_id] = null;
        }
        if ( isset( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['slug'] );
        } elseif(
            $_GET['wpcf-post-type'] == 'post'
            || $_GET['wpcf-post-type'] == 'page'
            || $_GET['wpcf-post-type'] == 'attachment'
        ) {
            $data['slug'] = sanitize_text_field( $_GET['wpcf-post-type'] );
        } else {
            $data['slug'] = null;
        }
        if ( isset( $data['rewrite']['slug'] ) ) {
            $data['rewrite']['slug'] = remove_accents( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = strtolower( $data['rewrite']['slug'] );
            $data['rewrite']['slug'] = trim( $data['rewrite']['slug'] );
        }
        $data['_builtin'] = false;

        // Set post type name
        $post_type = null;
        if ( !empty( $data['slug'] ) ) {
            $post_type = $data['slug'];
        } elseif ( !empty( $data[$this->get_id] ) ) {
            $post_type = $data[$this->get_id];
        } elseif ( !empty( $data['labels']['singular_name'] ) ) {
            $post_type = sanitize_title( $data['labels']['singular_name'] );
        }

        if ( empty( $post_type ) ) {
            wpcf_admin_message( __( 'Please set post type name', 'wpcf' ), 'error' );
            return false;
        }

        $data['slug'] = $post_type;
	    $post_type_option = new Types_Utils_Post_Type_Option();
        $custom_types = $post_type_option->get_post_types();
        $protected_data_check = array();

        if ( wpcf_is_builtin_post_types($data['slug']) ) {
            $data['_builtin'] = true;
            $update = true;
        } else {
            // Check reserved name
            $reserved = wpcf_is_reserved_name( $post_type, 'post_type' );
            if ( is_wp_error( $reserved ) ) {
                wpcf_admin_message( $reserved->get_error_message(), 'error' );
                return false;
            }

            // Check overwriting
            if ( ( !array_key_exists( $this->get_id, $data ) || $data[$this->get_id] != $post_type ) && array_key_exists( $post_type, $custom_types ) ) {
                wpcf_admin_message( __( 'Post Type already exists', 'wpcf' ), 'error' );
                return false;
            }

            /*
             * Since Types 1.2
             * We do not allow plural and singular names to be same.
             */
            if ( $wpcf->post_types->check_singular_plural_match( $data ) ) {
                wpcf_admin_message( $wpcf->post_types->message( 'warning_singular_plural_match' ), 'error' );
                return false;
            }

            // Check if renaming then rename all post entries and delete old type
            if ( !empty( $data[$this->get_id] )
                && $data[$this->get_id] != $post_type ) {
                    global $wpdb;
                    $wpdb->update( $wpdb->posts, array('post_type' => $post_type),
                        array('post_type' => $data[$this->get_id]), array('%s'),
                        array('%s')
                    );

                    /**
                     * update post meta "_wp_types_group_post_types"
                     */
                    $sql = $wpdb->prepare(
                        "select meta_id, meta_value from {$wpdb->postmeta} where meta_key = %s",
                        '_wp_types_group_post_types'
                    );
                    $all_meta = $wpdb->get_results($sql, OBJECT_K);
                    $re = sprintf( '/,%s,/', $data[$this->get_id] );
                    foreach( $all_meta as $meta ) {
                        if ( !preg_match( $re, $meta->meta_value ) ) {
                            continue;
                        }
                        $wpdb->update(
                            $wpdb->postmeta,
                            array(
                                'meta_value' => preg_replace( $re, ','.$post_type.',', $meta->meta_value ),
                            ),
                            array(
                                'meta_id' => $meta->meta_id,
                            ),
                            array( '%s' ),
                            array( '%d' )
                        );
                    }

                    /**
                     * update _wpcf_belongs_{$data[$this->get_id]}_id
                     */
                    $wpdb->update(
                        $wpdb->postmeta,
                        array(
                            'meta_key' => sprintf( '_wpcf_belongs_%s_id', $post_type ),
                        ),
                        array(
                            'meta_key' => sprintf( '_wpcf_belongs_%s_id', $data[$this->get_id] ),
                        ),
                        array( '%s' ),
                        array( '%s' )
                    );

                    /**
                     * update options "wpv_options"
                     */
                    $wpv_options = get_option( 'wpv_options', true );
                    if ( is_array( $wpv_options ) ) {
                        $re = sprintf( '/(views_template_(archive_)?for_)%s/', $data[$this->get_id] );
                        foreach( $wpv_options as $key => $value ) {
                            if ( !preg_match( $re, $key ) ) {
                                continue;
                            }
                            unset($wpv_options[$key]);
                            $key = preg_replace( $re, "$1".$post_type, $key );
                            $wpv_options[$key] = $value;
                        }
                        update_option( 'wpv_options', $wpv_options );
                    }

                    /**
                     * update option "wpcf-custom-taxonomies"
                     */
                    $wpcf_custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, true );
                    if ( is_array( $wpcf_custom_taxonomies ) ) {
                        $update_wpcf_custom_taxonomies = false;
                        foreach( $wpcf_custom_taxonomies as $key => $value ) {
                            if ( array_key_exists( 'supports', $value ) && array_key_exists( $data[$this->get_id], $value['supports'] ) ) {
                                unset( $wpcf_custom_taxonomies[$key]['supports'][$data[$this->get_id]] );
                                $update_wpcf_custom_taxonomies = true;
                            }
                        }
                        if ( $update_wpcf_custom_taxonomies ) {
                            update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $wpcf_custom_taxonomies );
                        }
                    }

                    // Sync action
                    do_action( 'wpcf_post_type_renamed', $post_type, $data[$this->get_id] );

                    // Set protected data
                    $protected_data_check = $custom_types[$data[$this->get_id]];
                    // Delete old type
                    unset( $custom_types[$data[$this->get_id]] );
                    $data[$this->get_id] = $post_type;
                } else {
                    // Set protected data
                    $protected_data_check = !empty( $custom_types[$post_type] ) ? $custom_types[$post_type] : array();
                }

            // Check if active
            if ( isset( $custom_types[$post_type]['disabled'] ) ) {
                $data['disabled'] = $custom_types[$post_type]['disabled'];
            }
        }

        // Sync taxes with custom taxes
        $taxes = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

        foreach ( $taxes as $id => $tax ) {
            if ( isset( $data['taxonomies'] ) && !empty( $data['taxonomies'] ) && array_key_exists( $id, $data['taxonomies'] ) ) {
                $taxes[$id]['supports'][$data['slug']] = 1;
            } else {
                if( isset( $taxes[$id]['supports'][$data['slug']] ) )
                    unset( $taxes[$id]['supports'][$data['slug']] );
            }
        }

        update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxes );

        // Preserve protected data
        foreach ( $protected_data_check as $key => $value ) {
            if ( strpos( $key, '_' ) !== 0 ) {
                unset( $protected_data_check[$key] );
            }
        }

        /**
         * save custom field group
         */
        /* removed types-608
        $post_to_groups = isset($_POST['ct']['custom-field-group'])?$_POST['ct']['custom-field-group']:array();
        $groups = $this->fields->get_groups_with_post_types();
        foreach( $groups as $group) {
            $post_types_to_save = $group['_wp_types_group_post_types'];
            // save
            if ( array_key_exists($group['id'], $post_to_groups)) {
                $post_types_to_save[] = $data['slug'];
            } else {
                if(($key = array_search($data['slug'], $post_types_to_save)) !== false) {
                    unset($post_types_to_save[$key]);
                }
                if (
                    false
                    || empty($post_types_to_save)
                    || (
                        true
                        && 1 == sizeof($post_types_to_save)
                        && 'all' == current($post_types_to_save)
                    )
                ) {
                    $post_types_to_save = array();
                    foreach( get_post_types() as $key => $value ) {
                        if ( $data['slug'] == $value) {
                            continue;
                        }
                        if ( in_array($value, $wpcf->excluded_post_types) ) {
                            continue;
                        }
                        $post_types_to_save[] = $value;
                    }
                }
            }
            wpcf_admin_fields_save_group_post_types($group['id'], $post_types_to_save);
        }
        */

        /**
         * set last edit time
         */
        $data[TOOLSET_EDIT_LAST] = time();

        /**
         * set last edit author
         */

        $data[WPCF_AUTHOR] = get_current_user_id();

        /**
         * add builid in
         */
        if ( $data['_builtin'] && !isset( $protected_data_check[$data['slug']])) {
            $protected_data_check[$data['slug']] = array();
        }

        // Merging protected data
        $custom_types[$post_type] = array_merge( $protected_data_check, $data );

        update_option( WPCF_OPTION_NAME_CUSTOM_TYPES, $custom_types );

        // WPML register strings
        if ( !$data['_builtin'] ) {
            wpcf_custom_types_register_translation( $post_type, $data );
        }

        // success message
        $msg = $update
            ? __( 'Post Type saved.', 'wpcf' )
            : __( 'New Post Type created.', 'wpcf' );

        wpcf_admin_message_store(
            $msg,
            'updated notice notice-success is-dismissible'
        );

	    flush_rewrite_rules();

        if ( !$data['_builtin'] ) {
            do_action( 'wpcf_custom_types_save', $data );
        }

        // Redirect
        wp_safe_redirect(
            esc_url_raw(
                add_query_arg(
                    array(
                        'page' => 'wpcf-edit-type',
                        $this->get_id => $post_type,
                        'wpcf-message' => 'view',
	                    // Flush rewrite rules after reload
	                    'flush' => '1'
                    ),
                    admin_url( 'admin.php' )
                )
            )
        );
        die();
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
    public function closedpostboxes( $screen_base )
    {
        if ( 'toolset_page_wpcf-edit-type' != $screen_base ) {
            return;
        }
        $option_name = sprintf('closedpostboxes_%s', $screen_base);
        $closedpostboxes = get_user_meta(get_current_user_id(), $option_name);
        if ( !empty($closedpostboxes) ) {
            return;
        }
        $closedpostboxes[] = 'types_labels';
        $closedpostboxes[] = 'types_options';
        update_user_option( get_current_user_id(), $option_name, $closedpostboxes, true);
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
    public function prepare_field_select_screen()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['parent'])
            || !isset($_REQUEST['child'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_nonce('child-post-fields', $_REQUEST['parent'], $_REQUEST['child']))
        ) {
            $this->verification_failed_and_die();
        }
        $parent = $_REQUEST['parent'];
        $child = $_REQUEST['child'];

        $post_type_parent = get_post_type_object( $parent );
        $post_type_child = get_post_type_object( $child );

        if ( null == $post_type_parent || null == $post_type_child ) {
            die( __( 'Wrong post types', 'wpcf' ) );
        }
        $relationships = get_option( 'wpcf_post_relationship', array() );
        if ( !isset( $relationships[$parent][$child] ) ) {
            $this->print_notice_and_die(
                __( 'Please save Post Type first to edit these fields.', 'wpcf' )
            );
        }
        $repetitive_warning_markup = array();
        $data = $relationships[$parent][$child];

        $form = array();
        $form['repetitive_warning_markup'] = $repetitive_warning_markup;
        $form['select'] = array(
            '#type' => 'radios',
            '#name' => 'fields_setting',
            '#options' => array(
                __( 'Title, all custom fields and parents', 'wpcf' ) => 'all_cf',
                __( 'Do not show management options for this post type', 'wpcf' ) => 'only_list',
                __( 'All fields, including the standard post fields', 'wpcf' ) => 'all_cf_standard',
                __( 'Specific fields', 'wpcf' ) => 'specific',
            ),
            '#attributes' => array(
                'display' => 'ul',
            ),
            '#default_value' => empty( $data['fields_setting'] ) ? 'all_cf' : $data['fields_setting'],
        );
        /**
         * check default, to avoid missing configuration
         */
        if ( !in_array($form['select']['#default_value'], $form['select']['#options']) ) {
            $form['select']['#default_value'] = 'all_cf';
        }
        /**
         * Specific options
         */
        $groups = wpcf_admin_get_groups_by_post_type( $child );
        $options_cf = array();
        $repetitive_warning = false;
        $repetitive_warning_txt = __( 'Repeating fields should not be used in child posts. Types will update all field values.', 'wpcf' );
        foreach ( $groups as $group ) {
            $fields = wpcf_admin_fields_get_fields_by_group( $group['id'] );
            foreach ( $fields as $key => $cf ) {
                $__key = wpcf_types_cf_under_control( 'check_outsider', $key ) ? $key : WPCF_META_PREFIX . $key;
                $options_cf[$__key] = array(
                    '#title' => $cf['name'],
                    '#name' => 'fields[' . $__key . ']',
                    '#default_value' => isset( $data['fields'][$__key] ) ? 1 : 0,
                    '#inline' => true,
                    '#before' => '<li>',
                    '#after' => '</li>',
                );
                // Repetitive warning
                if ( wpcf_admin_is_repetitive( $cf ) ) {
                    if ( !$repetitive_warning ) {
                        $repetitive_warning_markup = array(
                            '#type' => 'markup',
                            '#markup' => '<div class="message error" style="display:none;" id="wpcf-repetitive-warning"><p>' . $repetitive_warning_txt . '</p></div>',
                        );
                    }
                    $repetitive_warning = true;
                    $options_cf[$__key]['#after'] = !isset( $data['fields'][$__key] ) ? '<div class="message error" style="display:none;"><p>' : '<div class="message error"><p>';
                    $options_cf[$__key]['#after'] .= $repetitive_warning_txt;
                    $options_cf[$__key]['#after'] .= '</p></div></li>';
                    $options_cf[$__key]['#attributes'] = array(
                        'onclick' => 'jQuery(this).parent().find(\'.message\').toggle();',
                        'disabled' => 'disabled',
                    );
                }
            }
        }

        /**
         * build options for "Specific fields"
         */
        $options = array();
        /**
         * check and add built-in properites
         */
        require_once WPCF_INC_ABSPATH . '/post-relationship.php';
        $supports= wpcf_post_relationship_get_supported_fields_by_post_type($child);
        foreach ( $supports as $child_field_key => $child_field_data ) {
            $options[$child_field_data['name']] = array(
                '#title' => $child_field_data['title'],
                '#name' => sprintf('fields[%s]', $child_field_data['name']),
                '#default_value' => isset( $data['fields'][$child_field_data['name']] ) ? 1 : 0,
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
        }

        /**
         * add custom fields
         */
        $options = $options + $options_cf;
        $temp_belongs = wpcf_pr_admin_get_belongs( $child );
        foreach ( $temp_belongs as $temp_parent => $temp_data ) {
            if ( $temp_parent == $parent ) {
                continue;
            }
            $temp_parent_type = get_post_type_object( $temp_parent );
            $options[$temp_parent] = array(
                '#title' => $temp_parent_type->label,
                '#name' => 'fields[_wpcf_pr_parents][' . $temp_parent . ']',
                '#default_value' => isset( $data['fields']['_wpcf_pr_parents'][$temp_parent] ) ? 1 : 0,
                '#inline' => true,
                '#before' => '<li>',
                '#after' => '</li>',
            );
        }
        /**
         * remove "Specific fields" if there is no fields
         */
        if ( empty($options) ) {
            unset($form['select']['#options'][__('Specific fields', 'wpcf')]);
            if ('specific' == $form['select']['#default_value']) {
                $form['select']['#default_value'] = 'all_cf';
            }
        }

        // Taxonomies
        $taxonomies = get_object_taxonomies( $post_type_child->name, 'objects' );
        if ( !empty( $taxonomies ) ) {
            foreach ( $taxonomies as $tax_id => $taxonomy ) {
                $options[$tax_id] = array(
                    '#title' => sprintf( __('Taxonomy - %s', 'wpcf'), $taxonomy->label ),
                    '#name' => 'fields[_wpcf_pr_taxonomies][' . $tax_id . ']',
                    '#default_value' => isset( $data['fields']['_wpcf_pr_taxonomies'][$tax_id] ) ? 1 : 0,
                    '#inline' => true,
                    '#before' => '<li>',
                    '#after' => '</li>',
                );
            }
        }

        $form['specific'] = array(
            '#type' => 'checkboxes',
            '#name' => 'fields',
            '#options' => $options,
            '#default_value' => isset( $data['fields'] ),
            '#before' => sprintf(
                '<ul id="wpcf-specific" class="%s">',
                'specific' == $form['select']['#default_value']? '':'hidden'
            ),
            '#after' => '</ul>',
        );
        $form['nonce'] = array(
            '#type' => 'hidden',
            '#value' => wp_create_nonce($this->get_nonce('child-post-fields-save', $parent, $child)),
            '#name' => 'wpcf-fields-save-nonce',
            '#id' => 'wpcf-fields-save-nonce',
        );
        $form['parent'] = array(
            '#type' => 'hidden',
            '#value' => esc_attr($parent),
            '#name' => 'wpcf-parent',
            '#id' => 'wpcf-parent',
        );
        $form['child'] = array(
            '#type' => 'hidden',
            '#value' => esc_attr($child),
            '#name' => 'wpcf-child',
            '#id' => 'wpcf-child',
        );
        echo wpcf_form_simple( $form );
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
    public function save_child_fields()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['current'])
            || !isset($_REQUEST['parent'])
            || !isset($_REQUEST['child'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_nonce('child-post-fields-save', $_REQUEST['parent'], $_REQUEST['child']))
        ) {
            $this->verification_failed_and_die();
        }
        $parent = $_REQUEST['parent'];
        $child = $_REQUEST['child'];
        $fields = array();
        parse_str($_REQUEST['current'], $fields);

        $relationships = get_option( 'wpcf_post_relationship', array() );
        $relationships[$parent][$child]['fields_setting'] = sanitize_text_field( $fields['fields_setting'] );
        /**
         * sanitize
         */
        require_once WPCF_INC_ABSPATH . '/post-relationship.php';
        $relationships[$parent][$child]['fields'] = array();
        if (  isset( $fields['fields'] ) && is_array($fields['fields'])) {
            $allowed_keys = wpcf_post_relationship_get_specific_fields_keys($child);
            foreach( $fields['fields'] as $key => $value ) {

                // other parent cpts
                if ( '_wpcf_pr_parents' == $key ) {
                    $relationships[$parent][$child]['fields'][$key] = array();
                    foreach( array_keys($value) as $parents) {
                        $relationships[$parent][$child]['fields'][$key][$parents] = 1;
                    }
                }

                /**
                 * sanitize Taxonomy
                 */
                if ( '_wpcf_pr_taxonomies' == $key ) {
                    if ( is_array($value) ) {
                        $relationships[$parent][$child]['fields'][$key] = array();
                        foreach( array_keys($value) as $taxonomy) {
                            $taxonomy = get_taxonomy($taxonomy);
                            if ( is_object($taxonomy) ) {
                                $relationships[$parent][$child]['fields'][$key][$taxonomy->name] = 1;
                            }
                        }
                    }
                    continue;
                }
                if ( array_key_exists( $key, $allowed_keys) ) {
                    $relationships[$parent][$child]['fields'][$key] = 1;
                }
            }
        }
        update_option( 'wpcf_post_relationship', $relationships );
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
    public function get_icons_list()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_nonce('post-type-dashicons-list'))
        ) {
            $this->verification_failed_and_die();
        }
        $icons = array(
            'admin-appearance' => __('appearance', 'wpcf'),
            'admin-collapse' => __('collapse', 'wpcf'),
            'admin-comments' => __('comments', 'wpcf'),
            'admin-generic' => __('generic', 'wpcf'),
            'admin-home' => __('home', 'wpcf'),
            'admin-links' => __('links', 'wpcf'),
            'admin-media' => __('media', 'wpcf'),
            'admin-network' => __('network', 'wpcf'),
            'admin-page' => __('page', 'wpcf'),
            'admin-plugins' => __('plugins', 'wpcf'),
            'admin-post' => __('post', 'wpcf'),
            'admin-settings' => __('settings', 'wpcf'),
            'admin-site' => __('site', 'wpcf'),
            'admin-tools' => __('tools', 'wpcf'),
            'admin-users' => __('users', 'wpcf'),
            'album' => __('album', 'wpcf'),
            'align-center' => __('align center', 'wpcf'),
            'align-left' => __('align left', 'wpcf'),
            'align-none' => __('align none', 'wpcf'),
            'align-right' => __('align right', 'wpcf'),
            'analytics' => __('analytics', 'wpcf'),
            'archive' => __('archive', 'wpcf'),
            'arrow-down-alt2' => __('down alt2', 'wpcf'),
            'arrow-down-alt' => __('down alt', 'wpcf'),
            'arrow-down' => __('down', 'wpcf'),
            'arrow-left-alt2' => __('left alt2', 'wpcf'),
            'arrow-left-alt' => __('left alt', 'wpcf'),
            'arrow-left' => __('left', 'wpcf'),
            'arrow-right-alt2' => __('right alt2', 'wpcf'),
            'arrow-right-alt' => __('right alt', 'wpcf'),
            'arrow-right' => __('right', 'wpcf'),
            'arrow-up-alt2' => __('up alt2', 'wpcf'),
            'arrow-up-alt' => __('up alt', 'wpcf'),
            'arrow-up' => __('up', 'wpcf'),
            'art' => __('art', 'wpcf'),
            'awards' => __('awards', 'wpcf'),
            'backup' => __('backup', 'wpcf'),
            'book-alt' => __('book alt', 'wpcf'),
            'book' => __('book', 'wpcf'),
            'building' => __('building', 'wpcf'),
            'businessman' => __('businessman', 'wpcf'),
            'calendar-alt' => __('calendar alt', 'wpcf'),
            'calendar' => __('calendar', 'wpcf'),
            'camera' => __('camera', 'wpcf'),
            'carrot' => __('carrot', 'wpcf'),
            'cart' => __('cart', 'wpcf'),
            'category' => __('category', 'wpcf'),
            'chart-area' => __('chart area', 'wpcf'),
            'chart-bar' => __('chart bar', 'wpcf'),
            'chart-line' => __('chart line', 'wpcf'),
            'chart-pie' => __('chart pie', 'wpcf'),
            'clipboard' => __('clipboard', 'wpcf'),
            'clock' => __('clock', 'wpcf'),
            'cloud' => __('cloud', 'wpcf'),
            'controls-back' => __('back', 'wpcf'),
            'controls-forward' => __('forward', 'wpcf'),
            'controls-pause' => __('pause', 'wpcf'),
            'controls-play' => __('play', 'wpcf'),
            'controls-repeat' => __('repeat', 'wpcf'),
            'controls-skipback' => __('skip back', 'wpcf'),
            'controls-skipforward' => __('skip forward', 'wpcf'),
            'controls-volumeoff' => __('volume off', 'wpcf'),
            'controls-volumeon' => __('volume on', 'wpcf'),
            'dashboard' => __('dashboard', 'wpcf'),
            'desktop' => __('desktop', 'wpcf'),
            'dismiss' => __('dismiss', 'wpcf'),
            'download' => __('download', 'wpcf'),
            'editor-aligncenter' => __('align center', 'wpcf'),
            'editor-alignleft' => __('align left', 'wpcf'),
            'editor-alignright' => __('align right', 'wpcf'),
            'editor-bold' => __('bold', 'wpcf'),
            'editor-break' => __('break', 'wpcf'),
            'editor-code' => __('code', 'wpcf'),
            'editor-contract' => __('contract', 'wpcf'),
            'editor-customchar' => __('custom char', 'wpcf'),
            'editor-distractionfree' => __('distraction free', 'wpcf'),
            'editor-expand' => __('expand', 'wpcf'),
            'editor-help' => __('help', 'wpcf'),
            'editor-indent' => __('indent', 'wpcf'),
            'editor-insertmore' => __('insert more', 'wpcf'),
            'editor-italic' => __('italic', 'wpcf'),
            'editor-justify' => __('justify', 'wpcf'),
            'editor-kitchensink' => __('kitchen sink', 'wpcf'),
            'editor-ol' => __('ol', 'wpcf'),
            'editor-outdent' => __('outdent', 'wpcf'),
            'editor-paragraph' => __('paragraph', 'wpcf'),
            'editor-paste-text' => __('paste text', 'wpcf'),
            'editor-paste-word' => __('paste word', 'wpcf'),
            'editor-quote' => __('quote', 'wpcf'),
            'editor-removeformatting' => __('remove formatting', 'wpcf'),
            'editor-rtl' => __('rtl', 'wpcf'),
            'editor-spellcheck' => __('spellcheck', 'wpcf'),
            'editor-strikethrough' => __('strike through', 'wpcf'),
            'editor-textcolor' => __('text color', 'wpcf'),
            'editor-ul' => __('ul', 'wpcf'),
            'editor-underline' => __('underline', 'wpcf'),
            'editor-unlink' => __('unlink', 'wpcf'),
            'editor-video' => __('video', 'wpcf'),
            'edit' => __('edit', 'wpcf'),
            'email-alt' => __('email alt', 'wpcf'),
            'email' => __('email', 'wpcf'),
            'excerpt-view' => __('excerpt view', 'wpcf'),

	        // because https://core.trac.wordpress.org/ticket/30832
	        // but we don't have to offer it at all, actually it looks deprecated
            // 'exerpt-view' => __('excerpt view', 'wpcf'),

            'external' => __('external', 'wpcf'),
            'facebook-alt' => __('facebook alt', 'wpcf'),
            'facebook' => __('facebook', 'wpcf'),
            'feedback' => __('feedback', 'wpcf'),
            'flag' => __('flag', 'wpcf'),
            'format-aside' => __('aside', 'wpcf'),
            'format-audio' => __('audio', 'wpcf'),
            'format-chat' => __('chat', 'wpcf'),
            'format-gallery' => __('gallery', 'wpcf'),
            'format-image' => __('image', 'wpcf'),
            'format-links' => __('links', 'wpcf'),
            'format-quote' => __('quote', 'wpcf'),
            'format-standard' => __('standard', 'wpcf'),
            'format-status' => __('status', 'wpcf'),
            'format-video' => __('video', 'wpcf'),
            'forms' => __('forms', 'wpcf'),
            'googleplus' => __('google plus', 'wpcf'),
            'grid-view' => __('grid view', 'wpcf'),
            'groups' => __('groups', 'wpcf'),
            'hammer' => __('hammer', 'wpcf'),
            'heart' => __('heart', 'wpcf'),
            'id-alt' => __('id alt', 'wpcf'),
            'id' => __('id', 'wpcf'),
            'images-alt2' => __('images alt2', 'wpcf'),
            'images-alt' => __('images alt', 'wpcf'),
            'image-crop' => __('image crop', 'wpcf'),
            'image-flip-horizontal' => __('image flip horizontal', 'wpcf'),
            'image-flip-vertical' => __('image flip vertical', 'wpcf'),
            'image-rotate-left' => __('image rotate left', 'wpcf'),
            'image-rotate-right' => __('image rotate right', 'wpcf'),
            'index-card' => __('index card', 'wpcf'),
            'info' => __('info', 'wpcf'),
            'leftright' => __('left right', 'wpcf'),
            'lightbulb' => __('light bulb', 'wpcf'),
            'list-view' => __('list view', 'wpcf'),
            'location-alt' => __('location alt', 'wpcf'),
            'location' => __('location', 'wpcf'),
            'lock' => __('lock', 'wpcf'),
            'marker' => __('marker', 'wpcf'),
            'media-archive' => __('media archive', 'wpcf'),
            'media-audio' => __('media audio', 'wpcf'),
            'media-code' => __('media code', 'wpcf'),
            'media-default' => __('media default', 'wpcf'),
            'media-document' => __('media document', 'wpcf'),
            'media-interactive' => __('media interactive', 'wpcf'),
            'media-spreadsheet' => __('media spreadsheet', 'wpcf'),
            'media-text' => __('media text', 'wpcf'),
            'media-video' => __('media video', 'wpcf'),
            'megaphone' => __('megaphone', 'wpcf'),
            'menu' => __('menu', 'wpcf'),
            'microphone' => __('microphone', 'wpcf'),
            'migrate' => __('migrate', 'wpcf'),
            'minus' => __('minus', 'wpcf'),
            'money' => __('money', 'wpcf'),
            'nametag' => __('name tag', 'wpcf'),
            'networking' => __('networking', 'wpcf'),
            'no-alt' => __('no alt', 'wpcf'),
            'no' => __('no', 'wpcf'),
            'palmtree' => __('palm tree', 'wpcf'),
            'performance' => __('performance', 'wpcf'),
            'phone' => __('phone', 'wpcf'),
            'playlist-audio' => __('playlist audio', 'wpcf'),
            'playlist-video' => __('playlist video', 'wpcf'),
            'plus-alt' => __('plus alt', 'wpcf'),
            'plus' => __('plus', 'wpcf'),
            'portfolio' => __('portfolio', 'wpcf'),
            'post-status' => __('post status', 'wpcf'),
            'post-trash' => __('post trash', 'wpcf'),
            'pressthis' => __('press this', 'wpcf'),
            'products' => __('products', 'wpcf'),
            'randomize' => __('randomize', 'wpcf'),
            'redo' => __('redo', 'wpcf'),
            'rss' => __('rss', 'wpcf'),
            'schedule' => __('schedule', 'wpcf'),
            'screenoptions' => __('screen options', 'wpcf'),
            'search' => __('search', 'wpcf'),
            'share1' => __('share1', 'wpcf'),
            'share-alt2' => __('share alt2', 'wpcf'),
            'share-alt' => __('share alt', 'wpcf'),
            'share' => __('share', 'wpcf'),
            'shield-alt' => __('shield alt', 'wpcf'),
            'shield' => __('shield', 'wpcf'),
            'slides' => __('slides', 'wpcf'),
            'smartphone' => __('smartphone', 'wpcf'),
            'smiley' => __('smiley', 'wpcf'),
            'sort' => __('sort', 'wpcf'),
            'sos' => __('sos', 'wpcf'),
            'star-empty' => __('star empty', 'wpcf'),
            'star-filled' => __('star filled', 'wpcf'),
            'star-half' => __('star half', 'wpcf'),
            'store' => __('store', 'wpcf'),
            'tablet' => __('tablet', 'wpcf'),
            'tagcloud' => __('tag cloud', 'wpcf'),
            'tag' => __('tag', 'wpcf'),
            'testimonial' => __('testimonial', 'wpcf'),
            'text' => __('text', 'wpcf'),
            'tickets-alt' => __('tickets alt', 'wpcf'),
            'tickets' => __('tickets', 'wpcf'),
            'translation' => __('translation', 'wpcf'),
            'trash' => __('trash', 'wpcf'),
            'twitter' => __('twitter', 'wpcf'),
            'undo' => __('undo', 'wpcf'),
            'universal-access-alt' => __('universal access alt', 'wpcf'),
            'universal-access' => __('universal access', 'wpcf'),
            'update' => __('update', 'wpcf'),
            'upload' => __('upload', 'wpcf'),
            'vault' => __('vault', 'wpcf'),
            'video-alt2' => __('video alt2', 'wpcf'),
            'video-alt3' => __('video alt3', 'wpcf'),
            'video-alt' => __('video alt', 'wpcf'),
            'visibility' => __('visibility', 'wpcf'),
            'welcome-add-page' => __('add page', 'wpcf'),
            'welcome-comments' => __('comments', 'wpcf'),
            'welcome-edit-page' => __('edit page', 'wpcf'),
            'welcome-learn-more' => __('learn more', 'wpcf'),
            'welcome-view-site' => __('view site', 'wpcf'),
            'welcome-widgets-menus' => __('widgets menus', 'wpcf'),
            'welcome-write-blog' => __('write blog', 'wpcf'),
            'wordpress-alt' => __('wordpress alt', 'wpcf'),
            'wordpress' => __('wordpress', 'wpcf'),
            'yes' => __('yes', 'wpcf'),
        );
        printf(
            '<p><input type="text" class="js-wpcf-search large-text" placeholder="%s" /</p>',
            esc_attr__('Search', 'wpcf')
        );
        $current = isset($_REQUEST['slug']) && is_string($_REQUEST['slug'])? $_REQUEST['slug']:'';
        echo '<ul>';
        foreach ( $icons as $slug => $title ) {
            printf(
                '<li data-wpcf-icon="%s" class="%s"><a href="#" data-wpcf-icon="%s"><span class="dashicons-before dashicons-%s">%s</span></a></li>',
                esc_attr($slug),
                $current == $slug? 'selected':'',
                esc_attr($slug),
                esc_attr($slug),
                $title
            );
        }
        echo '</ul>';
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
    public function get_post_type_slug_from_request()
    {
        if ( !isset($_GET['wpcf-post-type']) ) {
            return '';
        }

        // get current post type
        require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.post-type.php';
        $wpcf_post_type = new Types_Admin_Post_Type();
		$get_post_type_slug_from_request = sanitize_text_field( $_GET['wpcf-post-type'] );
        $custom_post_type = $wpcf_post_type->get_post_type($get_post_type_slug_from_request);
        if ( isset($custom_post_type['slug']) ) {
            return $custom_post_type['slug'];
        }
        return '';
    }

	/**
	 * Render content of the WPML post type translation box.
	 *
	 * The box contains information about translatability of the post type, or a notice if the post type
	 * wasn't saved yet.
	 *
	 * Relies on wpml_custom_post_translation_options() which uses the wpcf-edit-type GET parameter to determine
	 * the post type slug.
	 *
	 * @since unknown
	 */
    public function wpml_box()
    {
        if ( !function_exists('wpml_custom_post_translation_options') ) {
            _e('Somethng wrong!', 'wpcf');
            return;
        }

	    $post_type_slug = $this->get_post_type_slug_from_request();

	    $is_add_new_page = empty( $post_type_slug );

	    if( $is_add_new_page ) {

		    printf(
			    '<div class="notice notice-success below-h2"><p>%s</p></div>',
			    __( 'You will be able to make this post type translatable once it is saved.', 'wpcf' )
		    );

	    } else {
		    echo wpml_custom_post_translation_options();
	    }
    }
}

