<?php
require_once dirname(__FILE__) . '/validation-cakephp.php';

/**
 * Validation class
 *
 * @version 1.0
 */

// DEPRECATED - remove from Types and maybe CRED

if(!class_exists('Wpcf_Validate')) {
	class Wpcf_Validate
	{
	
	    /**
	     * Holds generic messages.
	     * @var type 
	     */
	    public static $messages = null;
	    /**
	     * Holds function names.
	     * @var type 
	     */
	    private static $_cake_aliases = array(
	        'digits' => 'numeric',
	        'number' => 'numeric',
	        'alphanumeric' => 'alphaNumericWhitespaces',
	        'nospecialchars' => 'noSpecialChars',
	    );
	    /**
	     * Current validation has 'required' method.
	     * @var type 
	     */
	    private static $_is_required = false;
	    
	    private static $_validation_object = null;
	
	    /**
	     * Sets calls.
	     * 
	     * @param type $args
	     * @param type $value
	     * @return type 
	     */
	    public static function check($args, $value)
	    {
	        // Init validation object
	        if (is_null(self::$_validation_object)) {
	            self::$_validation_object = new Wpcf_Cake_Validation();
	        }
	        
	        // Init messages
	        if (is_null(self::$messages)) {
	            self::_set_messages();
	        }
	        // Check if there is 'required' method
	        if (array_key_exists('required', $args)) {
	            self::$_is_required = true;
	        }
	
	        // Loop over validation array
	        foreach ($args as $method => $v) {
	            // Use this class method
	            if (is_callable(array('Wpcf_Validate', $method))) {
	                $check = call_user_func_array(array('Wpcf_Validate', $method),
	                        array($v, $value));
	                // Use CakePHP method
	            } else if ((isset(self::$_cake_aliases[$method])
	                    && is_callable(array('Wpcf_Cake_Validation', self::$_cake_aliases[$method])))
	                    || is_callable(array('Wpcf_Cake_Validation', $method))) {
	                
	                // Check if validation pattern is set
	                if (isset($v['pattern'])) {
	                    $pattern = array_flip(explode('.', $v['pattern']));
	                    foreach ($pattern as $arg_key => $arg_value) {
	                        if (isset($v[$arg_key])) {
	                            $pattern[$arg_key] = $v[$arg_key];
	                        }
	                    }
	                    $pattern['check'] = $value;
	                    $v = $pattern;
	                    // Apply simple pattern (check, value)
	                } else {
	                    unset($v['active'], $v['message']);
	                    $v = array($value) + $v;
	                }
	
	                // Validate
	                if (isset(self::$_cake_aliases[$method]) && is_callable(array('Wpcf_Cake_Validation', self::$_cake_aliases[$method]))) {
	//                    $check = @call_user_func_array(array('Wpcf_Cake_Validation', self::$_cake_aliases[$method]),
	//                                    array_values($v));
	                    $check = @call_user_func_array(array(self::$_validation_object, self::$_cake_aliases[$method]),
	                                    array_values($v));
	                } else {
	//                    $check = @call_user_func_array(array('Wpcf_Cake_Validation', $method),
	//                                    array_values($v));
	                    $check = @call_user_func_array(array(self::$_validation_object, $method),
	                                    array_values($v));
	                }
	                if (!$check) {
	                    $check = array();
	                    $check['error'] = 1;
	                }
	                // No method available
	            } else {
	                return array('error' => 1, 'message' => 'No validation method');
	            }
	
	            // Set error
	            if (isset($check['error'])) {
	                // Don't return error if it's empty but not required
	                if ((!empty($value) && $method != 'required' && self::$_is_required)
	                        || (empty($value) && $method == 'required')) {
	                    $check['message'] = !empty($v['message']) ? $v['message'] : self::$messages[$method];
	                    return $check;
	                }
	            }
	        }
	
	        return true;
	    }
	
	    /**
	     * Checks if method is available.
	     * 
	     * @param type $method
	     * @return type 
	     */
	    public static function canValidate($method)
	    {
	        return (is_callable(array('Wpcf_Validate',  $method))
	        || (isset(self::$_cake_aliases[$method])
	        && is_callable(array('Wpcf_Cake_Validation', self::$_cake_aliases[$method])))
	        || is_callable(array('Wpcf_Cake_Validation',  $method)));
	    }
	
	    /**
	     * Checks if method has form data.
	     * 
	     * @param type $method
	     * @return type 
	     */
	    public static function hasForm($method)
	    {
	        return is_callable(array('Wpcf_Validate', $method . '_form'));
	    }
	
	    /**
	     * Inits messages.
	     */
	    private static function _set_messages()
	    {
	        // Set outside in /admin.php
	        self::$messages = wpcf_admin_validation_messages();
	    }
	
	    /**
	     * Return method invalid message.
	     * 
	     * @param type $method
	     * @return type 
	     */
	    public static function get_message($method)
	    {
	        if (is_null(self::$messages)) {
	            self::_set_messages();
	        }
	        if (isset(self::$messages[$method])) {
	            return self::$messages[$method];
	        }
	        return null;
	    }
	
	    /**
	     * Checks 'required'.
	     * 
	     * @param type $args
	     * @param type $value
	     * @return type 
	     */
	    public static function required($args, $value)
	    {
	        if (empty($value) && $value !== 0 && $value !== '0') {
	            return array(
	                'error' => 1,
	            );
	        }
	        return true;
	    }
	
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function required_form($field, $data = array())
	    {
	        $form = array();

		    $form['required-checkbox'] = self::merge_form_with_field_settings(
			    array(
				    '#type' => 'checkbox',
				    '#title' => __('Required', 'wpcf'),
				    '#name' => $field['#name'] . '[active]',
				    '#default_value' => isset($data['active']) ? 1 : 0,
				    '#inline' => true,
				    '#suffix' => '<br />',
			    ),
			    $data
		    );

	        $form['required-value'] = array(
	            '#type' => 'hidden',
	            '#value' => 'true',
	            '#name' => $field['#name'] . '[value]',
	        );

	        $form['required-message'] = self::get_custom_message($field,
	                        self::get_message('required'), $data);


		    return $form;
	    }
	
	    /**
	     * Checks 'email'.
	     * 
	     * @param type $args
	     * @param type $value
	     * @return type 
	     */
	    public static function email($args, $value)
	    {
	        if (!is_email($value)) {
	            return array(
	                'error' => 1,
	            );
	        }
	        return true;
	    }
	    
	    /**
	     * Checks 'rewriteslug'.
	     * 
	     * @param type $args
	     * @param type $value
	     * @return type 
	     */
	    public static function rewriteslug($args, $value)
	    {
	        if (preg_match('#[^a-zA-Z0-9\/\_\-\%]#', $value) === false) {
	            return array(
	                'error' => 1,
	            );
	        }
	        return true;
	    }
	
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function email_form($field, $data = array())
	    {
	        $form = array();
	        $form['email-checkbox'] = self::merge_form_with_field_settings(
		        array(
			        '#type' => 'checkbox',
			        '#title' => __('Email', 'wpcf'),
			        '#name' => $field['#name'] . '[active]',
			        '#default_value' => isset($data['active']) ? 1 : 0,
			        '#inline' => true,
			        '#suffix' => '<br />',
		        ),
		        $data
	        );
	
	        $form['email-message'] = self::get_custom_message($field,
	                        self::get_message('email'), $data);

		    return $form;
	    }
	
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function url_form($field, $data = array())
	    {
	        $form = array();
	        $form['url-checkbox'] = self::merge_form_with_field_settings(
		        array(
			        '#type' => 'checkbox',
			        '#title' => 'URL',
			        '#name' => $field['#name'] . '[active]',
			        '#default_value' => isset($data['active']) ? 1 : 0,
			        '#inline' => true,
			        '#suffix' => '<br />',
		        ),
		        $data
	        );
		    $form['url-checkbox'] = self::setForced($form['url-checkbox'], $field, $data);

	        $form['url-message'] = self::get_custom_message($field,
	                        self::get_message('url'), $data);
	        return $form;
	    }
	
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function date_form($field, $data = array())
	    {
	        $form = array();
	        $form['date-checkbox'] = self::merge_form_with_field_settings(
		        array(
		            '#type' => 'checkbox',
		            '#title' => __('Date', 'wpcf'),
		            '#name' => $field['#name'] . '[active]',
		            '#default_value' => isset($data['active']) ? 1 : 0,
		            '#inline' => true,
		            '#suffix' => '<br />',
		        ),
		        $data
	        );
	        $form['date-format'] = array(
	            '#type' => 'hidden',
	            '#value' => 'mdy',
	            '#name' => $field['#name'] . '[format]',
	        );
	        $form['date-pattern'] = array(
	            '#type' => 'hidden',
	            '#value' => 'check.format',
	            '#name' => $field['#name'] . '[pattern]',
	        );
	        $form['url-message'] = self::get_custom_message($field,
	                        self::get_message('date'), $data);
	        return $form;
	    }
	    
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function digits_form($field, $data = array())
	    {
	        $form = array();
	        $attributes = array();
	        $default_value = isset($data['active']) ? 1 : 0;
	        $form['digits-checkbox'] = array(
	            '#type' => 'checkbox',
	            '#title' => __('Digits', 'wpcf'),
	            '#name' => $field['#name'] . '[active]',
	            '#default_value' => $default_value,
	            '#inline' => true,
	            '#suffix' => '<br />',
	            '#attributes' => $attributes,
	        );
	        $form['digits-checkbox'] = self::setForced($form['digits-checkbox'], $field, $data);
	
	        $form['digits-message'] = self::get_custom_message($field,
	                        self::get_message('digits'), $data);
	        return $form;
	    }
	    
	    /**
	     * Returns form data.
	     * 
	     * @param type $field
	     * @param type $data
	     * @return array
	     */
	    public static function number_form($field, $data = array())
	    {
	        $form = array();
	        $attributes = array();
	        $default_value = isset($data['active']) ? 1 : 0;
	        $form['number-checkbox'] = self::merge_form_with_field_settings(
		        array(
		            '#type' => 'checkbox',
		            '#title' => __('Numeric', 'wpcf'),
		            '#name' => $field['#name'] . '[active]',
		            '#default_value' => $default_value,
		            '#inline' => true,
		            '#suffix' => '<br />',
		            '#attributes' => $attributes,
	            ),
		        $data
	        );

	        $form['number-checkbox'] = self::setForced($form['number-checkbox'], $field, $data);

	        $form['number-message'] = self::get_custom_message($field,
	                        self::get_message('number'), $data);

		    return $form;
	    }

		/**
		 * @param $args
		 * @param $value
		 *
		 * @return bool
		 */
		public static function skype( $args, $value )
		{
			return true;
		}

		/**
		 * Returns form data.
		 *
		 * @param type $field
		 * @param type $data
		 * @return array
		 */
		public static function skype_form( $field, $data = array() )
		{
			$form = array();
			$form['skype-checkbox'] = self::merge_form_with_field_settings(
				array(
					'#type' => 'checkbox',
					'#title' => __('Validation', 'wpcf' ),
					'#label' => __('Skype', 'wpcf'),
					'#name' => $field['#name'] . '[active]',
					'#default_value' => isset( $data['active'] ) ? 1 : 0,
					'#inline' => true,
				),
				$data
			);
			$form['skype-checkbox'] = self::setForced( $form['skype-checkbox'], $field, $data );
			$form['skype-message'] = self::get_custom_message( $field, self::get_message( 'skype' ), $data );
			return $form;
		}

		/**
		 * Makes a checkbox always being checked.
		 * For example a numeric field has always numeric validation checked,
		 *  the user can only change the validation error message
		 *
		 * @param $element
		 * @param $field
		 * @param array $data
		 *
		 * @return array
		 */
	    public static function setForced($element, $field, $data = array())
	    {
	        $attributes = array();
	        $default_value = isset($data['active']) ? 1 : 0;
	        if (!empty($data['method_data']['forced'])) {
	            if (!isset($element['#attributes'])) {
	                $element['#attributes'] = array();
	            }
	            $element['#attributes']['readonly'] = 'readonly';
	            $element['#attributes']['onclick'] = 'jQuery(this).attr(\'checked\', \'checked\');';
	            $element['#default_value'] = 1;
	        }
	        return $element;
	    }
	
	    /**
	     * Returns 'custom message' field.
	     * 
	     * @param type $field
	     * @param type $default
	     * @param type $data
	     * @return type 
	     */
	    public static function get_custom_message($field, $default, $data)
	    {
		    $validate_message_form = array(
			    '#type' => 'textfield',
		        // '#title' => __('Custom message', 'wpcf'),
			    '#name' => $field['#name'] . '[message]',
			    '#value' => !empty($data['message']) ? $data['message'] : $default,
			    '#inline' => true,
			    // '#suffix' => '<br /><br />',
		    );

		    // active or not
		    if( ( !isset( $data['method_data']['forced'] ) || $data['method_data']['forced'] == false )
			    && ( !isset($data['active'] ) || $data['active'] == 0 ) ) {
			    $validate_message_form['#attributes']['disabled'] = 'disabled';
		    }

		    return self::merge_form_with_field_settings( $validate_message_form, $data, 'message' );
	    }

		/**
		 * This function is called to apply individual settings of the field,
		 * for example adding/changing a css class, a label, or a output pattern
		 *
		 * example of how to add such individual settings:
		 * open in Types field setting files under /embedded/includes/fields/numeric.php
		 *
		 * @param $form
		 * @param $field_settings
		 * @param $control_or_message_input
		 *      'control' = updates the control input settings, mostly a checkbox
		 *      'message' = updates the validation error message input
		 *
		 * @return array
		 */
		public static function merge_form_with_field_settings( $form, $field_settings, $control_or_message_input = 'control' ) {
			// field settings overrides
			if( isset( $field_settings['method_data']['form-settings'][$control_or_message_input] ) ) {
				$form = array_replace_recursive(
					$form,
					$field_settings['method_data']['form-settings'][$control_or_message_input]
				);
			}

			return $form;
		}

		/**
		 * @param $args
		 * @param $value
		 *
		 * @return array|bool
		 */
		public static function negativeTimestamp( $args, $value ) {
			if ( !fields_date_timestamp_neg_supported() && intval( $value ) < 0 ) {
				return array(
					'error' => 1,
				);
			}
			return true;
		}
	
	}
}

/* IF PHP < 5.3 */
if ( !function_exists( 'array_replace_recursive' ) ) {
	function wpcf_recurse( $array, $array1 ) {
		foreach ($array1 as $key => $value)
		{
			// create new key in $array, if it is empty or not an array
			if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
			{
				$array[$key] = array();
			}

			// overwrite the value in the base array
			if (is_array($value))
			{
				$value = array_replace_recursive($array[$key], $value);
			}
			$array[$key] = $value;
		}
		return $array;
	}

	function array_replace_recursive( $array, $array1 ) {
		// handle the arguments, merge one by one
		$args = func_get_args();
		$array = $args[0];
		if (!is_array($array))
		{
			return $array;
		}
		for ($i = 1; $i < count($args); $i++)
		{
			if (is_array($args[$i]))
			{
				$array = wpcf_recurse($array, $args[$i]);
			}
		}
		return $array;
	}
}
