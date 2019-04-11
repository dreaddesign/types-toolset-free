<?php
require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.page.php';
if ( defined( 'DOING_AJAX' ) ) {
    require_once WPCF_ABSPATH.'/embedded/classes/validate.php';
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
abstract class Types_Admin_Edit_Fields extends Types_Admin_Page
{
    protected $update = false;
    protected $type = 'wpcf-fields';

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
        parent::__construct();
        /**
         * set type
         */
        if ( isset( $_REQUEST['type']) ) {
            $this->type = $_REQUEST['type'];
        }
        /**
         * actions
         */
        add_action('wp_ajax_wpcf_edit_field_choose', array($this, 'field_choose'));
        add_action('wp_ajax_wpcf_edit_field_insert', array($this, 'field_insert'));
        add_action('wp_ajax_wpcf_edit_field_select', array($this, 'field_select'));
        add_action('wp_ajax_wpcf_edit_field_add_existed', array($this, 'field_add_existed'));
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
    protected function common_form_setup($form)
    {
        $form['#form']['redirection'] = false;
        $form['#form']['callback'] = false;
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
    public function box_submitdiv()
    {
        $button_text = __( 'Save Field Group', 'wpcf' );
        $form = $this->submitdiv($button_text, array(), 'custom-field');
        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
    }


	/**
	 * @param $type
	 * @param array $form_data
	 *
	 * @return array
	 */
    protected function get_field_form_data( $type, $form_data = array() )
    {
        /**
         * this function replace: wpcf_fields_get_field_form_data()
         */

        require_once WPCF_ABSPATH . '/includes/conditional-display.php';

        $form = array();
        /**
         * row fir field data
         */
        $table_row_typeproof = '<tr class="js-wpcf-fields-typeproof"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
        $table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';

        // Get field type data
        if ( empty($field_data) ) {
            $field_data = wpcf_fields_type_action( $type );
            if ( empty( $field_data ) ) {
                return $form;
            }
        }


        // Set right ID if existing field
        if ( isset( $form_data['submitted_key'] ) ) {
            $id = $form_data['submitted_key'];
        } else {
            $id = $type . '-' . rand();
        }

        // Sanitize
        $form_data = wpcf_sanitize_field( $form_data );
        
        $required = (isset($form_data['data']['validate']['required']['active']) && $form_data['data']['validate']['required']['active'] === "1") ? __('- required','wpcf') : '';
        $form_data['id'] = $id;

        // Set title
        $title = !empty( $form_data['name'] ) ? $form_data['name'] : __( 'Untitled', 'wpcf' );
        $title = sprintf(
            '<span class="wpcf-legend-update">%s</span> <span class="description">(%s)</span> <span class="wpcf_required_data">%s</span>',
            stripslashes( $title ),
            $field_data['title'],
            $required
        );

        // Get init data
        $field_init_data = wpcf_fields_type_action( $type );

        // See if field inherits some other
        $inherited_field_data = false;
        if ( isset( $field_init_data['inherited_field_type'] ) ) {
            $inherited_field_data = wpcf_fields_type_action( $field_init_data['inherited_field_type'] );
        }

        $form_field = array();

        // Font Awesome Icon
        $icon = $this->render_field_icon( $field_init_data );

        /**
         * box id & class
         */
        $closed_postboxes = $this->get_closed_postboxes();
        $clasess = array(
            'postbox',
        );

        // close all elements except new added fields
        if( ! isset( $_REQUEST['type'] ) )
            $clasess[] = 'closed';

        $box_id = sprintf('types-custom-field-%s', $id);
        /* Only close boxes which user closed manually
        if ( !empty($closed_postboxes) ) {
		   if ( in_array($box_id, $closed_postboxes) ) {
			   $clasess[] = 'closed';
		   }
	    }
	    */

        // box title
        $form_field['box-open'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<div id="%s" class="%s"><div class="handlediv" title="%s"><br></div><h3 class="hndle ui-sortable-handle">%s%s</h3>',
                esc_attr($box_id),
                esc_attr(implode(' ', $clasess)),
                esc_attr__('Click to toggle', 'wpcf'),
                $icon,
                $title
            )
        );

        $form_field['table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table class="widefat inside js-wpcf-slugize-container">',
        );

        // Force name and description
        $form_field['name'] = array(
            '#type' => 'textfield',
            '#name' => 'name',
            '#attributes' => array(
                'class' => 'widefat wpcf-forms-set-legend wpcf-forms-field-name js-wpcf-slugize-source',
                'placeholder' => __( 'Enter field name', 'wpcf' ),
                'tooltip' => __('This will be the label for the field in the post editor.', 'wpcf'),
            ),
            '#validate' => array('required' => array('value' => true)),
            '#inline' => true,
            '#pattern' => $table_row_typeproof,
            '#title' => __('Field name', 'wpcf'),
        );

        $form_field['slug'] = array(
            '#type' => 'textfield',
            '#name' => 'slug',
            '#attributes' => array(
                'class' => 'widefat wpcf-forms-field-slug js-wpcf-slugize',
                'maxlength' => 255,
                'placeholder' => __( 'Enter field slug', 'wpcf' ),
                'tooltip' => __('This is the machine name of the field.', 'wpcf'),
            ),
            '#validate' => array('nospecialchars' => array('value' => true)),
            '#inline' => true,
            '#pattern' => $table_row_typeproof,
            '#title' => __('Field slug', 'wpcf'),
        );

        // existing field
        if ( isset( $form_data['submitted_key'] ) ) {
            $form_field['slug-pre-save'] = array(
                '#type' => 'hidden',
                '#name' => 'slug-pre-save',
            );
        }


        $options = $this->get_available_types($type);
        if ( empty( $options ) ) {
            $form_field['type'] = array(
                '#type' => 'markup',
                '#markup' => wpautop($type),
            );
        } else {
            $form_field['type'] = array(
                '#type' => 'select',
                '#name' => 'type',
                '#options' => $options,
                '#default_value' => $type,
                '#description' => __('Options for this filed will be available after group save.', 'wpcf'),
                '#attributes' => array(
                    'class' => 'js-wpcf-fields-type',
                    'data-message-after-change' => esc_attr__('Field options will be available after you save this group.', 'wpcf'),
                ),
            );
        }
        $form_field['type']['#title'] = __('Field type', 'wpcf');
        $form_field['type']['#inline'] = true;
        $form_field['type']['#pattern'] = $table_row_typeproof;

        // If insert form callback is not provided, use generic form data
        if ( function_exists( 'wpcf_fields_' . $type . '_insert_form' ) ) {
            $form_field_temp = call_user_func( 'wpcf_fields_' . $type . '_insert_form', $form_data, 'wpcf[fields][' . $id . ']' );
            if ( is_array( $form_field_temp ) ) {
                unset( $form_field_temp['name'], $form_field_temp['slug'] );
                /**
                 * add default patter
                 */
                foreach( $form_field_temp as $key => $data ) {
                    if ( isset($data['#pattern']) ) {
                        continue;
                    }
                    $form_field_temp[$key]['#pattern'] = $table_row;
                }
                $form_field = $form_field + $form_field_temp;
            }
        }

        $form_field['description'] = array(
            '#type' => 'textarea',
            '#name' => 'description',
            '#attributes' => array(
                'rows' => 5,
                'cols' => 1,
                'placeholder' => __( 'Enter field description', 'wpcf' ),
                'class' => 'widefat',
                'tooltip' => __( 'This optional text appears next to the field and helps users understand what this field is for.', 'wpcf'),
            ),
            '#inline' => true,
            '#pattern' => $table_row,
            '#title' => __('Description', 'wpcf'),
        );

        /**
         * add placeholder field
         */
        switch($type)
        {
        case 'audio':
        case 'colorpicker':
        case 'date':
        case 'email':
        case 'embed':
        case 'file':
        case 'image':
        case 'numeric':
        case 'phone':
        case 'skype':
        case 'textarea':
        case 'textfield':
        case 'url':
        case 'video':
            $form_field['placeholder'] = array(
                '#type' => 'textfield',
                '#name' => 'placeholder',
                '#inline' => true,
                '#title' => __( 'Placeholder', 'wpcf' ),
                '#attributes' => array(
                    'placeholder' =>  __('Enter placeholder', 'wpcf'),
                    'class' => 'widefat',
                    'tooltip' => __( 'This value is being displayed when the field is empty in the post editor.', 'wpcf' ),
                ),
                '#pattern' => preg_replace('/<tr>/', '<tr class="wpcf-border-top">', $table_row),
            );
            break;
        }

        /**
         * add default value
         */
        switch($type)
        {
        case 'audio':
        case 'email':
        case 'embed':
        case 'file':
        case 'image':
        case 'numeric':
        case 'phone':
        case 'textfield':
        case 'url':
        case 'video':
            $form_field['user_default_value'] = array(
                '#type' => 'textfield',
                '#name' => 'user_default_value',
                '#inline' => true,
                '#title' => __( 'Default Value', 'wpcf' ),
                '#attributes' => array(
                    'placeholder' =>  __('Enter default value', 'wpcf'),
                    'class' => 'widefat',
                    'tooltip' => __('This is the initial value of the field.', 'wpcf'),
            ),
                '#pattern' => $table_row,
            );
            break;
        case 'textarea':
        case 'wysiwyg':
            $form_field['user_default_value'] = array(
                '#type' => 'textarea',
                '#name' => 'user_default_value',
                '#inline' => true,
                '#title' => __( 'Default Value', 'wpcf' ),
                '#attributes' => array(
                    'style' => 'width:100%;margin:0 0 10px 0;',
                    'placeholder' =>  __('Enter default value', 'wpcf'),
                ),
                '#pattern' => $table_row,
            );
            break;
        }

        switch( $type ) {
            case 'audio':
            case 'file':
            case 'image':
            case 'video':
                $form_field['user_default_value']['#validate'] = array(
                    'url2' => array(
                        'active' => 1,
                        'message' => __( 'Please enter a valid URL address.', 'wpcf' )
                    )
                );
                break;

            case 'embed':
            case 'url':
                $form_field['user_default_value']['#validate'] = array(
                    'url' => array(
                        'active' => 1,
                        'message' => __( 'Please enter a valid URL address.', 'wpcf' )
                    )
                );
                break;

            case 'email':
                $form_field['user_default_value']['#validate'] = array( 'email' => array() );
                break;

            case 'numeric':
                $form_field['user_default_value']['#validate'] = array( 'number' => array() );
                break;
        }

        if ( wpcf_admin_can_be_repetitive( $type ) ) {
            $temp_warning_message = '';

			// We need to set the "repetitive" setting to a string '0' or '1', not numbers, because it will be used
	        // again later in this method (which I'm not going to refactor now) and because the form renderer
	        // is oversensitive. 
	        $is_repetitive_as_string = ( 1 == wpcf_getnest( $form_data, array( 'data', 'repetitive' ), '0' ) ) ? '1' : '0';
	        if( !array_key_exists( 'data', $form_data ) || !is_array( $form_data['data'] ) ) {
		        $form_data['data'] = array();
	        }
	        $form_data['data']['repetitive'] = $is_repetitive_as_string;

            $form_field['repetitive'] = array(
                '#type' => 'radios',
                '#name' => 'repetitive',
                '#title' => __( 'Single or repeating field?', 'wpcf' ),
                '#options' => array(
                    'repeat' => array(
                        '#title' => __( 'Allow multiple-instances of this field', 'wpcf' ),
                        '#value' => '1',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.wpcf-cd-warning\').hide(); jQuery(this).parent().find(\'.wpcf-cd-repetitive-warning\').show();'),
                        '#before' => '<li>',
                        '#after' => '</li>',
                        '#inline' => true,
                    ),
                    'norepeat' => array(
                        '#title' => __( 'This field can have only one value', 'wpcf' ),
                        '#value' => '0',
                        '#attributes' => array('onclick' => 'jQuery(this).parent().parent().find(\'.wpcf-cd-warning\').show(); jQuery(this).parent().find(\'.wpcf-cd-repetitive-warning\').hide();'),
                        '#before' => '<li>',
                        '#after' => '</li>',
                        '#inline' => true,
                    ),
                ),
                '#default_value' => $is_repetitive_as_string,
                '#after' => wpcf_admin_is_repetitive( $form_data ) ? '<div class="wpcf-message wpcf-cd-warning wpcf-error" style="display:none;"><p>' . __( "There may be multiple instances of this field already. When you switch back to single-field mode, all values of this field will be updated when it's edited.", 'wpcf' ) . '</p></div>' . $temp_warning_message : $temp_warning_message,
                '#pattern' => preg_replace('/<tr>/', '<tr class="wpcf-border-top">', $table_row),
                '#inline' => true,
                '#before' => '<ul>',
                '#after' => '</ul>',
            );
        }

        /**
        /* Add validation box
         */
        $validate_function = sprintf('wpcf_fields_%s', $type);
        if ( is_callable($validate_function) ) {
            $form_validate = $this->form_validation(
                'wpcf[fields][' . $id . '][validate]',
                call_user_func( $validate_function ),
                $form_data
            );

            foreach ( $form_validate as $k => $v ) {
                if ( 'hidden' != $v['#type'] && !isset($v['#pattern']) ) {
                    $v['#pattern'] = $table_row;
                }
                $form_field['wpcf-' . $id.$k] = $v;
            }
        }

        /**
         * WPML Translation Preferences
         *
         * only for post meta
         *
         */
        $form_field += $this->wpml($form_data);

        // Conditional display, Relevanssi integration and other modifications can be added here.
	    // Note that form_data may contain only meta_key when the field is newly
	    $form_field = apply_filters( 'wpcf_form_field', $form_field, $form_data, $type );

        /**
         * add Remove button
         */
        $form_field['remove-field'] = array(
            '#type' => 'markup',
            '#markup' => sprintf(
                '<a href="#" class="js-wpcf-field-remove wpcf-field-remove" data-message-confirm="%s"><i class="fa fa-trash"></i> %s</a>',
                esc_attr__( 'Are you sure?', 'wpcf' ),
                __('Remove field', 'wpcf')
            ),
            '#pattern' => '<tfoot><tr><td colspan="2"><ELEMENT></td></tr></tfoot>',
        );

        /**
         * close table
         */
        $form_field[$id.'table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</table>',
        );

        /**
         * close foldable field div
         */
        $form_field['box-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
        );

        // Process all form fields
        foreach ( $form_field as $k => $field ) {

            $name = sprintf('wpcf-%s[%s]', $id, $k);

            $form[$name] = $field;

            // Check if nested
            if ( isset( $field['#name'] ) && strpos( $field['#name'], '[' ) === false ) {
                $form[$name]['#name'] = 'wpcf[fields][' . $id . '][' . $field['#name'] . ']';
            } else if ( isset( $field['#name'] ) && strpos( $field['#name'], 'wpcf[fields]' ) === false ) {
                $form[$name]['#name'] = 'wpcf[fields][' . $id . ']' . $field['#name'];
            } else if( isset( $field['#name'] ) ) {
                $form[$name]['#name'] = $field['#name'];
            }

            if ( !isset( $field['#id'] ) ) {
                $form[$name]['#id'] = $type . '-' . $field['#type'] . '-' . rand();
            }
            if ( isset( $field['#name'] ) && isset( $form_data[$field['#name']] ) ) {
                $form[$name]['#value'] = $form_data[$field['#name']];
                $form[$name]['#default_value'] = $form_data[$field['#name']];
                if ( !isset($form[$name]['#pattern']) ) {
                    $form[$name]['#pattern'] = $table_row;
                }
                // Check if it's in 'data'
            } else if ( isset( $field['#name'] ) && isset( $form_data['data'][$field['#name']] ) ) {
                $form[$name]['#value'] = $form_data['data'][$field['#name']];
                $form[$name]['#default_value'] = $form_data['data'][$field['#name']];
                if ( !isset($form[$name]['#pattern']) ) {
                    $form[$name]['#pattern'] = $table_row;
                }
            }

            if( $k == 'slug-pre-save' ) {
                $form[$name]['#value'] = $form_data['slug'];
            }
        }
        /**
         * last setup of form
         */
        if ( empty( $form_data ) || isset( $form_data['is_new'] ) ) {
            $form['wpcf-' . $id]['is_new'] = array(
                '#type' => 'hidden',
                '#name' => 'wpcf[fields][' . $id . '][is_new]',
                '#value' => '1',
                '#attributes' => array(
                    'class' => 'wpcf-is-new',
                ),
            );
        }
        // Set type
        $form['wpcf-' . $id]['type'] = array(
            '#type' => 'hidden',
            '#name' => 'wpcf[fields][' . $id . '][type]',
            '#value' => $type,
            '#id' => $id . '-type',
        );



        /**
         * just return this form
         */
        return $form;
    }

    protected function button_add_new( $clasess = array() )
    {
        $clasess[] = 'wpcf-fields-add-new';
        $clasess[] = 'js-wpcf-fields-add-new';
        return array(
            'fields-button-add-'.rand() => array(
                '#type' => 'button',
                '#value' => '<span class="dashicons dashicons-plus"></span> ' . __('Add New Field', 'wpcf'),
                '#attributes' => array(
                    'class' => esc_attr(implode(' ', $clasess)),
                    'data-wpcf-dialog-title' => esc_attr__('Add New Field', 'wpcf'),
                    'data-wpcf-id' => esc_attr($this->ct['id']),
                    'data-wpcf-message-loading' => esc_attr__('Please Wait, Loading…', 'wpcf'),
                    'data-wpcf-nonce' => wp_create_nonce('wpcf-edit-'.$this->ct['id']),
	                // This can be wpcf-postmeta, wpcf-usermeta or wpcf-termmeta.
                    'data-wpcf-type' => $this->type,
	                'data-wpcf-page' => esc_attr( wpcf_getget( 'page' ) )
                ),
                '_builtin' => true,
                '#name' => 'fields-button-add',
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
    protected function fields_begin()
    {
        $form = array();
        $form += $this->button_add_new();
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
    private function get_available_types($type = false)
    {
        $allowed = array();
        if ( $type ) {
            include_once WPCF_INC_ABSPATH.'/fields.php';
            $allowed = wpcf_admin_custom_fields_change_type_allowed_matrix();
            if ( isset( $allowed[$type] ) ) {
                $allowed = $allowed[$type];
            } else {
                $allowed = array( $type);
            }
        }

        $options = array();
        $options_disabled = array();
        $fields_registered = wpcf_admin_fields_get_available_types();
        foreach ( $fields_registered as $field_slug => $data ) {
            $one = array(
                '#name' => $data['title'],
                '#value' => $field_slug,
                '#title' => $data['title'],
            );
            if ( !empty($allowed) && !in_array($field_slug, $allowed) ) {
                $one['#attributes'] = array(
                    'disabled' => 'disabled',
                );
                $one['#title'] .= sprintf(
                    ' - %s',
                    __('not allowed', 'wpcf')
                );
                $options_disabled[] = $one;

            } else {
                $options[] = $one;
            }
        }
        return array_merge($options, $options_disabled);
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
    protected function sort_by_field_name($a, $b)
    {
        if ( isset($a['name']) && isset($b['name']) ) {
            if ( $a['name'] != $b['name'] ) {
                return mb_strtolower($a['name']) < mb_strtolower($b['name'])? -1:1;
            }
            if ( isset($a['slug']) && isset($b['slug']) ) {
                if ( $a['slug'] != $b['slug'] ) {
                    return mb_strtolower($a['slug']) < mb_strtolower($b['slug'])? -1:1;
                }
            }
        }
        return 0;
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
    public function form_validation( $name, $field, $form_data = array() )
    {
        if ( !isset( $field['validate'] ) ) {
            return array();
        }
        $form = array();
        // Process methods
        foreach ( $field['validate'] as $k => $method ) {

            // Set additional method data
            if ( is_array( $method ) ) {
                $form_data['data']['validate'][$k]['method_data'] = $method;
                $method = $k;
            }

            if ( !Wpcf_Validate::canValidate( $method )
                || !Wpcf_Validate::hasForm( $method ) ) {
                continue;
            }
            // Get method form data
            if ( Wpcf_Validate::canValidate( $method ) && Wpcf_Validate::hasForm( $method ) ) {
                $field['#name'] = $name . '[' . $method . ']';
                $form_validate = call_user_func_array(
                    array('Wpcf_Validate', $method . '_form'),
                    array(
                        $field,
                        isset( $form_data['data']['validate'][$method] ) ? $form_data['data']['validate'][$method] : array()
                    )
                );

                // Set unique IDs
                $is_first = true;
                foreach ( $form_validate as $key => $element ) {
                    if ( isset( $element['#type'] ) ) {
                        $form_validate[$key]['#id'] = $element['#type'] . '-' . wpcf_unique_id( serialize( $element ) );
                    }
                    if ( $is_first && isset($element['#pattern'] ) ) {
                        $is_first = false;
                        $form_validate[$key]['#pattern'] = preg_replace(
                            '/<tr>/',
                            '<tr class="wpcf-border-top">',
                            $element['#pattern']
                        );
                    }
                }

                // Join
                $form = $form + $form_validate;
            }
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
    public function wpml($form_data)
    {
        $form = array();
        if (
            true
            && isset($form_data['meta_type'])
            && isset($form_data['type'])
            && isset($form_data['id'])
            && 'postmeta' == $form_data['meta_type']
            && function_exists( 'wpml_cf_translation_preferences' )
        ) {
            $custom_field = !empty( $form_data['slug'] ) ? wpcf_types_get_meta_prefix( $form_data ) . $form_data['slug'] : false;
            $suppress_errors = $custom_field == false ? true : false;
            $translatable = array('textfield', 'textarea', 'wysiwyg');
            $action = in_array( $form_data['type'], $translatable ) ? 'translate' : 'copy';
            $wpml_prefs = wpml_cf_translation_preferences(
                $form_data['id'],
                $custom_field,
                'wpcf',
                false,
                $action,
                false,
                $suppress_errors
            );
            $wpml_prefs = str_replace('<span style="color:#FF0000;">', '<span class="wpcf-form-error">', $wpml_prefs);
            $form['wpcf-' . $form_data['id'].'-wpml-preferences'] = array(
                '#title' => __( 'Translation preferences', 'wpcf' ),
                '#type' => 'markup',
                '#markup' => $wpml_prefs,
                '#pattern' => '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
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
    protected function add_description($description)
    {
        return array(
            /**
             * description
             */
            'description' => array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<p class="description js-wpcf-description">%s</p>',
                    $description
                ),
            ),
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
    public function fields()
    {
        $form = $this->fields_begin();
        /**
         * existing fields
         */

        $form['fields-open'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="js-wpcf-fields wpcf-fields meta-box-sortables ui-sortable">',
            '_builtin' => true,
        );

        // If it's this->update, display existing fields
        $existing_fields = array();

        if ( $this->update && isset( $this->update['fields'] ) ) {
            foreach ( $this->update['fields'] as $slug => $field ) {
                $field['submitted_key'] = $slug;
                $field['group_id'] = $this->update['id'];
                $form_field = $this->get_field_form_data( $field['type'], $field );
                if ( is_array( $form_field ) ) {
                    $form = $form + $form_field;
                }
                $existing_fields[] = $slug;
                $show_under_title = false;
            }
        }

        $class_bottom_elements = empty( $existing_fields )
            ? ' hidden'
            : '';

        $form += $this->button_add_new( array( 'js-wpcf-fields-add-new-last' . $class_bottom_elements ));

        /*
         * Second Submit
         */
        // container open
        $form['submit-bottom-container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="js-wpcf-second-submit-container wpcf-second-submit-container' . $class_bottom_elements.'">',
            '_builtin' => true,
        );

        // submit button
        $form['submit-bottom'] = array(
            '#type' => 'submit',
            '#value' => __('Save Field Group', 'wpcf'),
            '#attributes' => array(
                'class' => 'js-wpcf-second-submit button-primary wpcf-disabled-on-submit',
            ),
            '_builtin' => true,
            '#name' => 'fields-button-add-second',
        );

        // container close
        $form['submit-bottom-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );

        $form['fields-close'] = array(
            '#type' => 'markup',
            '#markup' => '</div>',
            '_builtin' => true,
        );


        /**
         * setup common setting for forms
         */
        $form = $this->common_form_setup($form);
        /**
         * return form array
         */
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
    public function field_choose()
    {
        /**
         * check nonce
         */
        if (
            !isset($_REQUEST['id'])
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['id'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], 'wpcf-edit-'.$_REQUEST['id'])
        ) {
            $this->verification_failed_and_die();
        }
        $form = array();
        $form += $this->add_description(
            __('You can choose from the available fields:', 'wpcf')
        );
        // Get field types
        $form['nonce'] = array(
            '#type' => 'hidden',
            '#value' => wp_create_nonce('fields_insert'),
            '#name' => 'wpcf-fields-add-nonce',
            '#id' => 'wpcf-fields-add-nonce',
        );
        $form = $this->get_fields_list($form);

        if( $this->previously_added_fields_available() ) {
            $form['switch-to-exists'] = array(
                '#name' => 'switch-to-exists',
                '#type' => 'button',
                '#value' => __('Choose from previously created fields', 'wpcf'),
                '#before' => sprintf('<p>%s ', __('or you can', 'wpcf')),
                '#after' => '</p>',
                '#attributes' => array(
                    'class' => 'wpcf-switch-to-exists js-wpcf-switch-to-exists',
                    'data-wpcf-id' => esc_attr($_REQUEST['id']),
                    'data-wpcf-type' => $this->type,
                ),
            );
        }


        $form = wpcf_form(__FUNCTION__, $form);
        echo $form->renderForm();
        die;
    }

    /**
     * Function to see if there is any previously added field available to add.
     * It returns true if there are fields and at least one of the fields is not already assigned to the group.
     *
     * @return bool
     */
    private function previously_added_fields_available() {
        $fields = wpcf_admin_fields_get_fields( true, true, false, $this->type );

        // abort if no fields created yet
        if( empty( $fields ) )
            return false;

        // already used fields in current group
        $already_used_fields = isset( $_REQUEST['current'] ) && is_array( $_REQUEST['current'] )
            ? $_REQUEST['current']
            : array();

        // if there's the same amount of fields and used fields we have no fields left to show,
        // as one field can't be assigned twice to a group
        if( count( $fields ) == count( $already_used_fields ) )
            return false;

        // add this point we definitely have previously added fields which weren't used yet.
        return true;
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
    public function field_select()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['id'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], 'fields_insert')
        ) {
            $this->verification_failed_and_die();
        }
        $fields = wpcf_admin_fields_get_fields( true, true, false, $this->type );
        $fields_registered = wpcf_admin_fields_get_available_types();
        if ( !empty( $fields ) ) {
            $form = array();
            $form += $this->add_description(
                __('You can choose from the previously created fields:', 'wpcf')
            );

            $form['nonce'] = array(
                '#type' => 'hidden',
                '#value' => wp_create_nonce('fields_insert'),
                '#name' => 'wpcf-fields-add-nonce',
                '#id' => 'wpcf-fields-add-nonce',
            );

            if ( count($fields) > 8 ) {
                $form['Search'] = array(
                    '#type' => 'textfield',
                    '#name' => 'wpcf-fields-search',
                    '#attributes' => array(
                        'class' => 'regular-text js-wpcf-fields-search',
                        'placeholder' => esc_attr__('Search', 'wpcf'),
                    ),
                );
            }

            uasort( $fields, array( $this, 'sort_by_field_name') );

            $current = isset($_REQUEST['current']) && is_array($_REQUEST['current'])? $_REQUEST['current']:array();
            foreach ( $fields as $key => $field ) {
                if ( isset( $update['fields'] ) && array_key_exists( $key, $update['fields'] ) ) {
                    continue;
                }
                if ( !empty( $field['data']['removed_from_history'] ) ) {
                    continue;
                }
                /**
                 * you can't add the same field twice or more ;-)
                 */
                if ( in_array($key, $current) ) {
                    continue;
                }

                $value = '';
                $value .= $this->render_field_icon( $fields_registered[$field['type']] );
                $value .= sprintf('<span>%s</span>', $field['name']);

                $form['fields-existing'.$key] = array(
                    '#type' => 'button',
                    '#name' => $field['id'],
                    '#value' => $value,
                    '#attributes' => array(
                        'class' => 'js-wpcf-field-button-use-existed',
                        'data-wpcf-field-id' => $key,
                        'data-wpcf-field-type' => $field['type'],
                        'data-wpcf-type' => $this->type,
                    ),
                );
            }
            $form['switch-to-exists'] = array(
                '#name' => 'switch-to-exists',
                '#type' => 'button',
                '#value' => __('Choose from the default fields', 'wpcf'),
                '#before' => sprintf('<p>%s ', __('or you can', 'wpcf')),
                '#after' => '</p>',
                '#attributes' => array(
                    'class' => 'wpcf-switch-to-new js-wpcf-switch-to-new',
                    'data-wpcf-id' => esc_attr($_REQUEST['id']),
                    'data-wpcf-type' => $this->type,
                ),
            );
            echo wpcf_form_simple($form);
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
    public function field_insert()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['type'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], 'fields_insert')
        ) {
            $this->verification_failed_and_die();
        }

        // We need to determine the field's meta_type because that will be eventually
	    // passed through the wpcf_form_field filter in get_field_from_data().
	    //
	    // This information is required by the Relevanssi integration.
        $field_kind = wpcf_getpost( 'field_kind', 'wpcf-postmeta', array( 'wpcf-postmeta', 'wpcf-usermeta', 'wpcf-termmeta' ) );
	    $faux_form_data = array(
	    	'meta_type' => substr( $field_kind, 5 ) // get rid of the wpcf- prefix.
	    );

	    $enlimbo_form = $this->get_field_form_data( $_REQUEST['type'], $faux_form_data );

        echo wpcf_form_simple( $enlimbo_form );
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
    public function field_add_existed()
    {
        /**
         * check nonce
         */
        if (
            0
            || !isset($_REQUEST['_wpnonce'])
            || !isset($_REQUEST['id'])
            || !wp_verify_nonce($_REQUEST['_wpnonce'], 'fields_insert')
        ) {
            $this->verification_failed_and_die();
        }
        /**
         * get field definition
         */
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
        $field = wpcf_admin_fields_get_field( sanitize_text_field( $_REQUEST['id'] ), false, true, false, $this->type );
        if ( !empty( $field ) ) {
            echo wpcf_form_simple($this->get_field_form_data($field['type'], $field));
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
    protected function get_fields_list($form = array())
    {
        $fields_registered = wpcf_admin_fields_get_available_types();
        foreach ( $fields_registered as $filename => $data ) {
            $value = '';
            $value = $this->render_field_icon( $data );
            $value .= $data['title'];
            $form[$filename] = array(
                '#type' => 'button',
                '#name' => basename( $filename, '.php' ),
                '#value' => $value,
                '#attributes' => array(
                    'data-wpcf-field-type' => basename( $filename, '.php' ),
                    'class' => 'js-wpcf-field-button-insert',
                    'data-wpcf-type' => $this->type,
                ),
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
    public function get_group_list($form = array(), $type = 'wpcf-fields')
    {
        $message = '';
        $groups = array();
        switch( $type ) {
	        case 'wpcf-fields':
		        $groups = wpcf_admin_fields_get_groups();
		        $message = __( 'There is no Post Field Group. Please define one first.', 'wpcf' );
		        break;
	        case 'wpcf-usermeta':
		        $groups = wpcf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
		        $message = __( 'There is no User Field Group. Please define one first.', 'wpcf' );
		        break;
	        case WPCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION:
		        $groups = wpcf_admin_fields_get_groups( Types_Field_Group_Term::POST_TYPE );
		        $message = __( 'There is no Term Field Group. Please define one first.', 'wpcf' );
		        break;
        }
        if ( empty($groups) && !empty($message)) {
            $form['message'] = array(
                '#type' => 'notice',
                '#markup' => $message,
                '#attributes' => array(
                    'type' => 'warning',
                ),
            );
            return $form;
        }
        $form['groups-ul-open'] = array(
            '#type' => 'markup',
            '#markup' => '<ul class="wpcf-list-of-items js-wpcf-list-of-items">',
        );
        foreach ($groups as $group_id => $group) {
            $form[$group['id']] = array(
                '#type' => 'checkbox',
                '#name' => 'groups',
                '#title' => $group['name'],
                '#id' => 'wpcf-group-'.$group['id'],
                '#value' => $group['id'],
                '#default_value' => false,
                '#before' => '<li>',
                '#after' => '</li>',
                '#inline' => true,
            );
        }
        $form['groups-ul-close'] = array(
            '#type' => 'markup',
            '#markup' => '</ul>',
        );
        return $form;
    }


	public function ajax_filter_dialog() {
		/**
		 * check nonce
		 */
		if( ! isset( $_REQUEST['id'] ) || ! isset( $_REQUEST['_wpnonce'] ) || ! isset( $_REQUEST['type'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], $_REQUEST['type'] ) ) {
			_e( 'Verification failed.', 'wpcf' );
			die;
		}

		if( isset( $_REQUEST['all_fields'] ) && ! is_array( $_POST['all_fields'] ) ) {
			parse_str( $_REQUEST['all_fields'], $_REQUEST['all_fields'] );
		}

		$form = array();
		$tabs = $this->get_tabs_for_filter_dialog();

		// html: open tabs menu
		$html_tabs_menu = '<ul class="wpcf-tabs-menu">';

		// css: class for first menu
		$first_menu_active = ' class="wpcf-tabs-menu-current"';

		// build tabs menu
		foreach( $tabs as $id => $tab ) {
			// html: tabs menu list item
			$html_tabs_menu .= '<li' . $first_menu_active . '>';
			$html_tabs_menu .= '<span data-open-tab="#' . $id . '">';
			$html_tabs_menu .= $tab['title'] . '</span></li>';

			// next menu won't be active
			$first_menu_active = '';
		}

		// html: close tabs menu
		$html_tabs_menu .= '</ul>';

		// form: add tabs menu
		$form['tabs-menu'] = array(
			'#type'   => 'markup',
			'#markup' => $html_tabs_menu,
		);

		// form: open tabs
		$form['tabs-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="wpcf-tabs">'
		);

		// build tabs
		foreach( $tabs as $id => $tab ) {
			// form: tab open
			$form[ 'tabs-tab-open-' . $id ] = array(
				'#type'   => 'markup',
				'#markup' => '<div id="' . $id . '">'
			);

			// form: tab content
			$this->form_add_filter_dialog( $id, $form );

			// form: tab close
			$form[ 'tabs-tab-close-' . $id ] = array(
				'#type'   => 'markup',
				'#markup' => '</div>'
			);
		}

		// form: open tabs
		$form['tabs-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);

		$form = wpcf_form( __FUNCTION__, $form );
		echo $form->renderForm();
		die;
	}


	protected function form_add_filter_dialog( $filter, &$form ) {
		// Nothing to do here
	}


	/**
	 * Get description of tabs that will be displayed on the filter dialog.
	 *
	 * @return array[]
	 */
	protected function get_tabs_for_filter_dialog() {
		return array();
	}

    /**
     * @param $field
     *
     * @return string
     */
    protected function render_field_icon( $field ) {

        $icon = '';

        if( isset( $field['font-awesome'] ) ) {
            $icon = sprintf(
                '<i class="fa fa-%s"></i> ',
                esc_attr( $field['font-awesome'] )
            );

            return $icon;
        } elseif( isset( $field['types-field-image'] ) ) {
            $icon = sprintf(
                '<i class="types-field-icon types-field-icon-%s"></i> ',
                esc_attr( $field['types-field-image'] )
            );

            return $icon;
        }

        return $icon;
    }

}

