<?php

/*
 * Libraries
 * - CakePHP library for PHP validation
 * - jQuery Validation plugin for JS validation
 *
 * Flow
 * - Hooks to form filtering to collect data
 * - Filters data-wpt-validation (adds array of rules) to form element
 * - Queues scripts if any field is conditional
 * - JS is initialized and checks performed
 * - On form submission PHP checks are performed also (used in specific context,
 * on client's side (CRED or Types) for e.g. aborting saving/processing form)
 */

/**
 * Class description
 *
 * @author Srdjan
 */
class WPToolset_Forms_Validation {

    private $__formID;
    private $__formSET;
    protected $_cake;
    protected $_rules_map = array(
        'rangelength' => 'between',
        'number' => 'numeric'
    );

    function __construct($formID, $formSET) {
        $this->__formID = trim( $formID, '#' );
        $this->__formSET = $formSET;

        // Register
        wp_register_script( 'wptoolset-form-jquery-validation', WPTOOLSET_FORMS_RELPATH . '/lib/js/jquery-form-validation/jquery.validate.js', array('jquery'), WPTOOLSET_FORMS_VERSION, true );
        wp_register_script( 'wptoolset-form-jquery-validation-additional', WPTOOLSET_FORMS_RELPATH . '/lib/js/jquery-form-validation/additional-methods.min.js', array('wptoolset-form-jquery-validation'), WPTOOLSET_FORMS_VERSION, true );
	    wp_register_script( 'wptoolset-form-validation', WPTOOLSET_FORMS_RELPATH . '/js/validation.js', array( 'wptoolset-form-jquery-validation-additional', 'underscore', 'toolset-utils', 'toolset-event-manager', 'icl_editor-script' ), WPTOOLSET_FORMS_VERSION, true );

        $my_formID = str_replace( "-", "_", $formID );
        wp_localize_script( 'wptoolset-form-validation', 'cred_settings_' . $my_formID, array(
            'site_url' => get_site_url(),
            'form_id' => $formID,
            'use_ajax' => (!Toolset_Utils::is_real_admin() && isset( $formSET->form['use_ajax'] ) && $formSET->form['use_ajax'] == 1) ? true : false,
            'operation_ko' => __( 'There was an error while submitting the form', 'wpv-views' ),
            'delay_message' => __( 'You are being redirectd. Please Wait.', 'wpv-views' )
                )
        );

        // Filter JS validation data
        add_action( 'wptoolset_forms_field_js_validation_data_' . $this->__formID, array($this, 'filterJsValidation') );
        // Filter form field PHP validation
        add_filter( 'wptoolset_form_' . $this->__formID . '_validate_field', array($this, 'filterFormField'), 10, 2 );

		/**
		 * @deprecated 2.4.0
		 * @deprecated 1.9.0 CRED
		 */
        add_action( 'wptoolset_field_class', array($this, 'wptoolset_field_class_deprecated') );

		/**
	     * Adds necessary CSS classes to fields with validation output data.
	     *
		 * @since 2.4.0
	     * @since 1.9.0 CRED
	     */
        add_filter('toolset_field_additional_classes', array($this, 'actionFieldClass'), 10, 2);

        // Render settings
        add_action( 'admin_print_footer_scripts', array($this, 'renderJsonData'), 30 );
        add_action( 'wp_footer', array($this, 'renderJsonData'), 30 );
        add_action( 'wp_footer', array($this, 'loadCustomAssets'), 30 );

        wp_enqueue_script( 'wptoolset-form-validation' );
    }

    /**
     * loadCustomAssets
     */
    public function loadCustomAssets() {
        echo '<div class="wpt-modal"></div>';
    }

    /**
     * Adjusts validation data for JS processing (data-wpt-validate HTML attribute)
     *
     * @param type $rules
     * @return type
     */
    public function filterJsValidation($rules) {
        foreach ( $rules as $unfiltered_rule => $rule ) {
            // Possible change of rule (like DateITA)
            $filtered_rule = apply_filters( 'wptoolset_validation_rule_js', $unfiltered_rule );
            if ( $filtered_rule != $unfiltered_rule ) {
                $rules[$filtered_rule] = $rule;
                unset( $rules[$unfiltered_rule] );
                continue;
            }
        }
        foreach ( $rules as $unfiltered_rule => &$rule ) {
            $rule['args'] = apply_filters( 'wptoolset_validation_args_js', $rule['args'], $unfiltered_rule );
            // Remove value in args - search string '$value' or unset first element
            $replace = array_search( '$value', $rule['args'] );
            if ( $replace !== false ) {
                unset( $rule['args'][$replace] );
            } else {
                array_shift( $rule['args'] );
            }
//            unset( $rule['message'] );
        }
        return $rules;
    }

    /**
     * Form PHP validation.
     *
     * Called from Form_Factory or save_post hook.
     * Form Factory should check if element has 'error' property (WP_Error)
     * and use WP_Error::get_error_message() to display error message
     *
     * @param type $element
     * @param type $value
     * @return type
     */
    public function filterFormField($element, $value) {
        $rules = $this->_parseRules( $element['#validate'], $value );
        // If not required but empty - skip
        if ( !isset( $rules['required'] ) && ( is_null( $value ) || $value === false || $value === '' ) ) {
            return true;
        }
        try {
            $errors = array();
            foreach ( $rules as $rule => $args ) {
                if ( !$this->validate( $rule, $args['args'] ) ) {
                    $errors[] = $args['message'];
                }
            }
            if ( !empty( $errors ) ) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $element['error'] = new WP_Error( __CLASS__ . '::' . __METHOD__, 'Field not validated', $errors );
        }
        return $element;
    }

    /**
     * Bulk PHP validation.
     *
     * @param FieldFactory $field Field instance.
     * @return \WP_Error|boolean
     * @throws Exception
     */
    public function validateField($field) {
        $value = apply_filters( 'wptoolset_validation_value_' . $field->getType(), $field->getValue() );
        $rules = $this->_parseRules( $field->getValidationData(), $value );

        // If not required but empty - skip
        if ( !isset( $rules['required'] ) && $this->is_field_semantically_empty( $value, $field->getType() ) ) {
            return true;
        }

        try {
            $errors = array();
            foreach ( $rules as $rule => $args ) {
                if ( !$this->validate( $rule, $args['args'] ) ) {

                    /**
                     * toolset_common_validation_add_field_name_to_error
                     *
                     * Allow to avoid using the field name in the validation error message.
                     *
                     * @param boolean $var show field title in message. Default is true.
                     */
                    if ( apply_filters( 'toolset_common_validation_add_field_name_to_error', true ) ) {
                        $errors[] = $field->getTitle() . ' ' . $args['message'];
                    } else {
                        $errors[] = $args['message'];
                    }
                }
            }
            if ( !empty( $errors ) ) {
                throw new Exception();
            }
        } catch (Exception $e) {
            return new WP_Error( __CLASS__ . '::' . __METHOD__, 'Field not validated', $errors );
        }
        return true;
    }

    /**
     * Check that the semantic (display) value is empty, opposed to checking the raw data from *meta database row.
     *
     * @param mixed|string $value Raw field value.
     * @param string $field_type Field type slug.
     * @return bool
     * @since 2.3
     */
    protected function is_field_semantically_empty($value, $field_type) {
        switch ($field_type) {
            case 'skype':
                // Check the emptiness of skype name only, ignore the rest.
                return $this->is_field_semantically_empty( toolset_getarr( $value, 'skypename' ), 'textfield' );
            default:
                return ( is_null( $value ) || $value === false || $value === '' );
        }
    }

    protected function _parseRules($rules, $value) {
        $_rules = array();
        foreach ( $rules as $rule => $args ) {
            $rule = apply_filters( 'wptoolset_validation_rule_php', $rule );
            $args['args'] = apply_filters( 'wptoolset_validation_args_php', $args['args'], $rule );
            // Set value in args - search string '$value' or replace first element
            $replace = array_search( '$value', $args['args'] );
            if ( $replace !== false ) {
                $args['args'][$replace] = $value;
            } else {
                $args['args'][0] = $value;
            }
            $_rules[$rule] = $args;
        }
        return $_rules;
    }

    /**
     * Single rule PHP validation.
     *
     * Accepts e.g. validate('maxlength', array($value, '15'))
     *
     * @param type $method
     * @param type $args
     * @return boolean
     */
    public function validate($rule, $args) {
        $validator = $this->_cake();
        $rule = $this->_map_rule_js_to_php( $rule );

        if ( 'skype' == $rule ) {
            return $validator->custom( $args[0]['skypename'], '/^([a-zA-Z0-9\,\.\-\_]+)$/' );
        }

        if ( is_callable( array($validator, $rule) ) ) {
            return call_user_func_array( array($validator, $rule), $args );
        }
        return false;
    }

    /**
     * Loads CakePHP Validation class.
     *
     * @return type
     */
    protected function _cake() {
        if ( is_null( $this->_cake ) ) {
            require_once WPTOOLSET_FORMS_ABSPATH . '/lib/CakePHP-Validation.php';
            $this->_cake = new WPToolset_Cake_Validation;
        }
        return $this->_cake;
    }

    /**
     * Maps rules between JS and PHP.
     *
     * @param type $rule
     * @return type
     */
    protected function _map_rule_js_to_php($rule) {
        return isset( $this->_rules_map[$rule] ) ? $this->_rules_map[$rule] : $rule;
    }

    /**
     * Renders JSON data.
     */
    public function renderJsonData() {
        printf( '<script type="text/javascript">wptValidationForms.push("#%s");</script>', $this->__formID ? $this->__formID : uniqid( 'form_' ) );
    }

	/**
	 * Callback for a deprecated action.
	 *
	 * @since 2.4.0
	 */
	public function wptoolset_field_class_deprecated() {
		_doing_it_wrong(
			'wptoolset_field_class',
			__( 'This action was deprecated in CRED 1.9.0.', 'wpv-views' ),
			'1.9.0'
		);
	}

	/**
     * Check validation for a field and generate the related classnames for its metaform.
	 *
	 * @param string $classes The classnames fo the field
	 * @param array  $config  The field configuration
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since 2.4.0 Turn into a filter callback, hence make it return instead of echo.
	 */
    public function actionFieldClass($classes, $config) {
        if ( !empty( $config['validation'] ) ) {
            foreach ( $config['validation'] as $rule => $data ) {
                $classes .= " wpt-validation-{$rule}";
            }
        }
		return $classes;
    }

}
