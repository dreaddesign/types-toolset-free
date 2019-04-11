<?php

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

abstract class Types_Admin_Page
{
    protected $post_type = null;
    protected $boxes = array();
    protected $ct = null;

	/** @var string|null GET parameter name where the ID of the field group. */
    protected $get_id = null;
    protected $current_user_can_edit = false;
    protected $_errors = false;
    protected $screen = false;

    public function __construct()
    {
    }

    abstract public function init_admin();

    protected function init_hooks()
    {
        if ( defined( 'DOING_AJAX' ) ) {
            return;
        }
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
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
    public function add_meta_boxes()
    {
        $screen = get_current_screen();
        if ( empty($screen) || !isset($screen->base) ) {
            return;
        }
        $defaults = array(
            'advanced' => array(),
            'normal' => array(),
            'side' => array(),
        );

        $user_settings = get_user_meta(get_current_user_id(), 'meta-box-order_'.$screen->base);
        if ( !empty( $user_settings ) && isset( $user_settings[0])) {
            $new = array();
            foreach ( array_keys($defaults) as $key ) {
                if ( isset( $user_settings[0][$key] ) ) {
                    $new[$key] = explode(',', $user_settings[0][$key]);
                }
            }
            $user_settings = $new;
        }

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
        do_action('wpcf_closedpostboxes', $screen->base);

        foreach ($this->boxes as $id => $box) {
            $defaults[$box['default']][] = $id;
        }

        $order = wp_parse_args( $user_settings, $defaults );

        /**
         * check all was used?
         */
        $used = array();
        foreach( $order as $context => $data ) {
            foreach( $data as $key ) {
                $used[$key] = 1;
            }
        }
        foreach( $defaults as $context => $data ) {
            foreach( $data as $key ) {
                if ( isset($used[$key]) ) {
                    continue;
                }
                $order[$context][] = $key;
            }
        }

        foreach( $order as $context => $ids ) {
            foreach( $ids as $id ) {
                if (empty($id) || !isset($this->boxes[$id])) {
                    continue;
                }
                $callback = $this->boxes[$id]['callback'];
                /**
                 * do not add box for builitin post types
                 */
                if (
                    isset($this->ct)
                    && isset($this->ct['_builtin'])
                    && $this->ct['_builtin']
                    && isset($this->boxes[$id]['post_types'])
                    && 'custom' == $this->boxes[$id]['post_types']
                ) {
                    $callback = array($this, 'not_for_builitin');
                }
                add_meta_box(
                    $id,
                    $this->boxes[$id]['title'],
                    $callback,
                    $screen->base,
                    $context,
                    isset($this->boxes[$id]['priority'])?  $this->boxes[$id]['priority']:'default'
                );
            }
        }
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
    protected function prepare_screen()
    {
        $post = null;

        do_action( 'add_meta_boxes', $this->post_type, $post );

        /** This action is documented in wp-admin/edit-form-advanced.php */
        do_action( 'do_meta_boxes', $this->post_type, 'normal', $post );
        /** This action is documented in wp-admin/edit-form-advanced.php */
        do_action( 'do_meta_boxes', $this->post_type, 'advanced', $post );
        /** This action is documented in wp-admin/edit-form-advanced.php */
        do_action( 'do_meta_boxes', $this->post_type, 'side', $post );

        add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

        /**
         * WP control for meta boxes
         */
        include_once ABSPATH.'/wp-admin/includes/meta-boxes.php';
        wp_enqueue_script( 'post' );

        $markup = wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false, false );
        $markup .= wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false, false );
        return array(
            '#form' => array(),
            'postbox-controll' => array(
                '#type' => 'markup',
                '#markup' => $markup,
                '_builtin' => true,
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
    protected function submitdiv($button_text, $form = array(), $type = 'custom-post-type', $built_in = false )
    {
        if ( WPCF_Roles::user_can_edit($type, $this->ct) ) {
            $form['submit-div-open'] = array(
                '#type' => 'markup',
                '#markup' => '<div class="submitbox" id="submitpost"><div id="major-publishing-actions">',
                '_builtin' => true,
            );

            if(
                ( isset( $_GET['group_id'] ) || isset( $_GET['wpcf-tax'] ) )
                && isset( $_GET['page'] )
                && ! $built_in
            ) {
                switch( $_GET['page'] ) {
                    case 'wpcf-edit':           // post fields
                        $action = 'delete_group';
                        break;
                    case 'wpcf-edit-usermeta':  // user fields
                        $action = 'delete_usermeta_group';
                        break;
                    case 'wpcf-termmeta-edit':  // term fields
                        $action = 'delete_term_group';
                        break;
                    case 'wpcf-edit-tax':       // taxonomy
                        $action = 'delete_taxonomy';
                        break;
                }
                if( isset( $action ) ) {
                    $args = array(
                        'action' => 'wpcf_ajax',
                        'wpcf_action' => $action,
                        '_wpnonce' => wp_create_nonce( $action ),
                        'wpcf_warning' => urlencode(__('Are you sure?', 'wpcf'))
                    );

                    if( isset( $_GET['group_id'] ) ) {
                        $args['group_id'] = sanitize_text_field( $_GET['group_id'] );
                        $args['wpcf_ajax_update'] = 'wpcf_list_ajax_response_'.sanitize_text_field( $_GET['group_id'] );
                        $delete_id_addition = sanitize_text_field( $_GET['group_id'] );
                    } else if( isset( $_GET['wpcf-tax'] ) ) {
                        $args['wpcf-tax'] = sanitize_text_field( $_GET['wpcf-tax'] );
                        $args['wpcf_ajax_update'] = 'wpcf_list_ajax_response_'.sanitize_text_field( $_GET['wpcf-tax'] );
                        $delete_id_addition = sanitize_text_field( $_GET['wpcf-tax'] );
                    }

                    $args['wpcf_ref'] = isset( $_GET['ref'] )
                        ? $_GET['ref']
                        : 'list';

                    $form['delete'] = array(
                        '#type' => 'markup',
                        '#markup' => sprintf(
                            '<div id="delete-action"><a href="%s" class="submitdelete wpcf-ajax-link wpcf-group-delete-link" id="wpcf-list-delete-%d"">%s</a></div>',
                            esc_url(
                                add_query_arg(
                                    $args,
                                    admin_url('admin-ajax.php')
                                )
                            ),
                            $delete_id_addition,
                            __('Delete', 'wpcf')
                        )
                    );
                }
            }

            $form['submit-div-open-publish'] = array(
                '#type' => 'markup',
                '#markup' => '<div id="publishing-action"><span class="spinner"></span>'
            );

            $form['submit'] = array(
                '#type' => 'submit',
                '#name' => 'wpcf-submit', // do not use only 'submit', because there is JQuery bug which prevents using form.submit() if input submit has name='submit'
                '#value' => $button_text,
                '#attributes' => array(
                    'class' => 'button-primary wpcf-disabled-on-submit',
                ),
                '_builtin' => true,
            );
            /**
             * add data attribute for _builtin post type
             */
            if ( isset($this->ct['_builtin']) && $this->ct['_builtin'] ) {
                $form['submit']['#attributes']['data-post_type_is_builtin'] = '_builtin';
            }
            $form['submit-div-close'] = array(
                '#type' => 'markup',
                '#markup' => '</div><div class="clear"></div></div></div>',
                '_builtin' => true,
            );
        }
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
    public function not_for_builitin()
    {
        if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wpcf-edit-tax' ) {
            $this->print_notice( __('These options are not available for built-in taxonomies.', 'wpcf'), 'add-wpcf-inside');
        } else {
            $this->print_notice( __('These options are not available for built-in post types.', 'wpcf'), 'add-wpcf-inside');
        }
    }


    public function add_box_howto($boxes)
    {
        $displaying_custom_content = include( WPCF_ABSPATH . '/marketing/displaying-custom-content/title-content.php' );

        $boxes[__FUNCTION__] = array(
            'callback' => array($this, 'box_howto'),
            'title' => $displaying_custom_content['title'],
            'default' => 'side',
            'priority' => 'low',
        );
        return $boxes;
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
    public function box_howto($medium = 'types')
    {
        $displaying_custom_content = include( WPCF_ABSPATH . '/marketing/displaying-custom-content/title-content.php' );
        echo $displaying_custom_content['content'];
    }

	/**
	 * Generate wrapper around form setup data.
	 *
	 * @param string $type
	 * @param array $data
	 * @param bool $render_closing_hr If true, render a hr tag at the end of the form section.
	 *
	 * @return array
	 */
    protected function filter_wrap( $type, $data = array(), $button_only = false, $render_closing_hr = true )
    {
        $data = wp_parse_args(
            $data,
            array(
                'nonce' => '',
                'title' => '',
                'value' => '',
                'value_default' => '',
            )
        );

        $form = array();
        $unique_id = wpcf_unique_id( serialize( func_get_args() ) );

        /**
         * form open
         */
        $form[$unique_id.'-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="%s" class="wpcf-filter-container js-wpcf-filter-container">',
                esc_attr($unique_id)
            ),
        );

        /**
         * header
         */
        if( ! $button_only )
            $form[$unique_id.'-header'] = array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<h3>%s</h3>',
                    $data['title']
                ),
            );

        /**
         * Description
         */
        if( ! $button_only && isset( $data['description'] ) ) {
            $form[$unique_id.'-description'] = array(
                '#type' => 'markup',
                '#markup' => wpautop($data['description']),
            );
        }

        /**
         * content
         */
        if( ! $button_only )
            $form[$unique_id.'-content'] = array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<span class="js-wpcf-filter-ajax-response">%s</span>',
                    empty($data['value'])? $data['value_default']:$data['value']
                ),
                '#inline' => true,
            );

        /**
         * button
         */
        if ( $this->current_user_can_edit ) {
            $form[$unique_id.'-button'] = array(
                '#type' => 'button',
                '#name' => esc_attr($unique_id.'-button'),
                '#value' => __('Edit', 'wpcf'),
                '#attributes' => array(
                    'class' => 'js-wpcf-filter-button-edit wpcf-filter-button-edit',
                    'data-wpcf-type' => esc_attr($type),
	                'data-wpcf-page' => esc_attr( wpcf_getget( 'page' ) ),
                    'data-wpcf-nonce' => wp_create_nonce($type),
                ),
                '#inline' => true,
                '#before' => '<div class="wpcf-filter-button-edit-container">',
                '#after' => '</div>',
            );
            foreach( $data as $key => $value ) {
                if ( preg_match('/^data\-wpcf\-/', $key) ) {
                    $form[$unique_id.'-button']['#attributes'][$key] = esc_attr($value);
                }
            }
        }

        /**
         * form close
         */
	    $close_clear = ( $button_only || !$render_closing_hr )
		    ? ''
		    : '<hr class="clear" />';

	    $form[$unique_id.'-close'] = array(
		    '#type' => 'markup',
		    '#markup' => $close_clear . '</div>'
	    );

	    return $form;
    }

	/**
	 * Get name of the nonce for working with the field group (used for saving, not sure where else).
	 *
	 * @param int $id ID of the field group (can be zero if creating new field group).
	 * @return string Nonce name.
	 */
    protected function get_nonce_action($id)
    {
        return esc_attr(
            sprintf(
                '_wpnonce_wpcf_%s_%s',
                $this->post_type,
                $id
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
    protected function verification_failed_and_die($int = 0, $message = false)
    {
        if ( $message ) {
            echo $message;
        } else {
            _e('Verification failed.', 'wpcf');
            if ($int && intval($int)) {
                printf(' (%d)', $int);
            }
        }
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
    protected function triggerError()
    {
        $this->_errors = true;
    }

    function _wpcf_filter_wrap($id)
    {
        return array(
            $id => array(
                '#type' => 'markup',
                '#markup' => 'todo: '.$id,
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
    protected function get_closed_postboxes()
    {
        /**
         * These aren't the screen looking for - Move along.
         */
        if ( defined( 'DOING_AJAX' ) ) {
            return array();
        }
        if ( empty($this->screen) && function_exists('get_current_screen') ) {
            $this->screen = get_current_screen();
        }
        return apply_filters(
            'types_get_closed_postboxes',
            get_user_meta(
                get_current_user_id(),
                sprintf('closedpostboxes_%s', $this->screen->id),
                true
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
    protected function get_nonce()
    {
        if ( 0 == func_num_args() ) {
            return 'types_default_nonce';
        }

        $args = func_get_args();
        return implode( '-', $args );
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
    protected function get_type_from_request($request_name)
    {
        $post_type = '';
        if (isset($_REQUEST[$request_name]) ) {
	        $post_type_option = new Types_Utils_Post_Type_Option();
            $post_types = $post_type_option->get_post_types();
            if ( array_key_exists($_REQUEST[$request_name], $post_types) ) {
                $post_type = sanitize_text_field( $_REQUEST[$request_name] );
            }
        }
        return $post_type;
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
    protected function sort_by_title($data_to_sort)
    {
        foreach( $data_to_sort as $key => $data ) {
            $data_to_sort[$key]['temp_key'] = $key;
        }
        usort($data_to_sort, array($this, 'sort_by_title_helper'));
        $sorted = array();
        foreach( $data_to_sort as $one) {
            $sorted[$one['temp_key']] = $one;
            unset($sorted[$one['temp_key']]['temp_key']);
        }
        return $sorted;
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
    private function sort_by_title_helper($a, $b)
    {
        if (
            0
            || !is_array($a)
            || !is_array($b)
            || !isset($a['#title'])
            || !isset($b['#title'])
        ) {
            return 0;
        }
        return strcmp(mb_strtolower($a['#title']), mb_strtolower($b['#title']));
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
    protected function print_notice_and_die($notice)
    {
        $this->print_notice($notice);
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
    protected function print_notice($notice, $add_wrap = 'no-wrap', $print = true )
    {
        $form = array();
        if ( 'add-wpcf-inside' == $add_wrap ) {
            $form['wrap-begin'] = array(
                '#type' => 'markup',
                '#markup' => '<div class="wpcf-inside">',
            );
        }
        $form['alert'] = array(
            '#type' => 'notice',
            '#markup' => $notice,
        );
        if ( 'add-wpcf-inside' == $add_wrap ) {
            $form['wrap-end'] = array(
                '#type' => 'markup',
                '#markup' => '</div>',
            );
        }
        $form = wpcf_form(__FUNCTION__, $form);

        if( $print ) {
            echo $form->renderForm();
        } else {
            return $form->renderForm();
        }

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
     *
     * @deprecated Use Types_Utils::object_to_array_deep() instead.
     */
    protected function object_to_array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }
}

