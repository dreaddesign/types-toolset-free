<?php

/**
* Helper functions for the Toolset family
*
* @since unknown
*/

if ( ! defined( 'ICL_COMMON_FUNCTIONS' ) ) {
	// DEPRECATED - remove from Types and maybe CRED
	define( 'ICL_COMMON_FUNCTIONS', true );
}

if ( ! defined( 'TOOLSET_COMMON_FUNCTIONS' ) ) {
	define( 'TOOLSET_COMMON_FUNCTIONS', true );
}

/**
* toolset_form_control
*
* Renders Enlimbo form elements.
*
* Replaces wpv_form_control
*
* @since 1.9
*/

if ( ! function_exists( 'toolset_form_control' ) ) {
	function toolset_form_control( $elements ) {
		static $form = null;
		if ( is_null( $form ) ) {
			$form = new Toolset_Enlimbo_Forms_Control();
		}
		return $form->renderElements( $elements );
	}
}
if ( ! function_exists( 'wpv_form_control' ) ) {
	function wpv_form_control( $elements ) {
		return toolset_form_control( $elements );
	}
}

if ( ! function_exists( 'toolset_getarr_safe' ) ) {
    /**
     * Safely retrieve a key from given array (meant for $_POST, $_GET, etc).
     *
     * Checks if the key is set in the source array. If not, default value is returned. Optionally validates against array
     * of allowed values and returns default value if the validation fails.
     *
     * @param array $source The source array.
     * @param string $key The key to be retrieved from the source array.
     * @param mixed $default Default value to be returned if key is not set or the value is invalid. Optional.
     *     Default is empty string.
     * @param null|array $valid If an array is provided, the value will be validated against it's elements.
     *
     * @return mixed The value of the given key or $default.
     *
     * @since 1.7
     */
    function toolset_getarr_safe( &$source, $key, $default = '', $valid = null ) {
        if( isset( $source[ $key ] ) ) {
            $val = $source[ $key ];
            if( is_array( $valid ) && !in_array( $val, $valid ) ) {
                return $default;
            }

            return $val;
        } else {
            return $default;
        }
    }
}

/**
 * Calculates relative path for given file.
 * 
 * @param type $file Absolute path to file
 * @return string Relative path
 */
if ( ! function_exists( 'icl_get_file_relpath' ) ) {
	function icl_get_file_relpath( $file ) {
		// website url form DB
		$url = get_option('siteurl');
		// fix the protocol
		$base_root = set_url_scheme( $url );

		// normalise windows paths
		$path_to_file = wp_normalize_path($file);
		// get file directory
		$file_dir = wp_normalize_path( dirname( $path_to_file ) );
		// get the path to 'wp-content'
		$from_content_dir = wp_normalize_path( realpath( WP_CONTENT_DIR ) );
		// get wp-content dirname
		$content_dir = wp_normalize_path( basename(WP_CONTENT_DIR) );

		// remove absolute path part until 'wp-content' folder
		$path = str_replace( $from_content_dir, '', $file_dir);
		// add wp-content dir to path
		$path = wp_normalize_path( $content_dir.$path );

		// build url
		$relpath = $base_root . '/' . $path;

		return $relpath;
	}

}

/**
 * Fix WP's multiarray parsing.
 * 
 * @param type $arg
 * @param type $defaults
 * @return type 
 */
if ( ! function_exists( 'wpv_parse_args_recursive' ) ) {
	function wpv_parse_args_recursive( $arg, $defaults ) {
		$temp = false;
		if ( isset( $arg[0] ) ) {
			$temp = $arg[0];
		} else if ( isset( $defaults[0] ) ) {
			$temp = $defaults[0];
		}
		$arg = wp_parse_args( $arg, $defaults );
		if ( $temp ) {
			$arg[0] = $temp;
		}
		foreach ( $defaults as $default_setting_parent => $default_setting ) {
			if ( !is_array( $default_setting ) ) {
				if ( !isset( $arg[$default_setting_parent] ) ) {
					$arg[$default_setting_parent] = $default_setting;
				}
				continue;
			}
			if ( !isset( $arg[$default_setting_parent] ) ) {
				$arg[$default_setting_parent] = $defaults[$default_setting_parent];
			}
			$arg[$default_setting_parent] = wpv_parse_args_recursive( $arg[$default_setting_parent],
					$defaults[$default_setting_parent] );
		}

		return $arg;
	}
}

if ( ! function_exists( 'wpv_filter_parse_date' ) ) {

    /**
     * Helper function for parsing dates.
     *
     * Possible inputs:
     *
     * NOW()
     * TODAY()    (time at 00:00 today)
     * FUTURE_DAY(1)
     * PAST_DAY(1)
     * THIS_MONTH()   (time at 00:00 on first day of this month)
     * FUTURE_MONTH(1)
     * PAST_MONTH(1)
     * THIS_YEAR()   (time at 00:00 on first day of this year)
     * FUTURE_YEAR(1)
     * PAST_YEAR(1)
     * SECONDS_FROM_NOW(1)
     * MONTHS_FROM_NOW(1)
     * YEARS_FROM_NOW(1)
     * DATE(dd,mm,yyyy)
     * DATE(dd,mm,yyyy)    as per Views
     * DATE('dd/mm/yyyy', 'd/m/Y')
     * DATE('mm/dd/yyyy', 'm/d/Y')
     *
     * @param int timestamp $date_format
     */
    function wpv_filter_parse_date($date_format)
    {
        $date_format = stripcslashes($date_format);
        $occurences = preg_match_all('/(\\w+)\(([^\)]*)\)/', $date_format, $matches);

        if ($occurences > 0) {
            for ($i = 0; $i < $occurences; $i++) {
                $date_func = $matches[1][$i];
                // remove comma at the end of date value in case is left there
                $date_value = isset( $matches[2] ) ? rtrim( $matches[2][$i], ',' ) : '';
                $resulting_date = false;

                switch (strtoupper($date_func)) {
                    case "NOW":
                        $resulting_date = current_time('timestamp');
                        break;
                    case "TODAY":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y'));
                        break;
                    case "FUTURE_DAY":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m'), date_i18n('d') + $date_value, date_i18n('Y'));
                        break;
                    case "PAST_DAY":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m'), date_i18n('d') - $date_value, date_i18n('Y'));
                        break;
                    case "THIS_MONTH":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m'), 1, date_i18n('Y'));
                        break;
                    case "FUTURE_MONTH":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m') + $date_value, 1, date_i18n('Y'));
                        break;
                    case "PAST_MONTH":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m') - $date_value, 1, date_i18n('Y'));
                        break;
                    case "THIS_YEAR":
                        $resulting_date = adodb_mktime(0, 0, 0, 1, 1, date_i18n('Y'));
                        break;
                    case "FUTURE_YEAR":
                        $resulting_date = adodb_mktime(0, 0, 0, 1, 1, date_i18n('Y') + $date_value);
                        break;
                    case "PAST_YEAR":
                        $resulting_date = adodb_mktime(0, 0, 0, 1, 1, date_i18n('Y') - $date_value);
                        break;
                    case "SECONDS_FROM_NOW":
                        $resulting_date = current_time('timestamp') + $date_value;
                        break;
                    case "MONTHS_FROM_NOW":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m') + $date_value, date_i18n('d'), date_i18n('Y'));
                        break;
                    case "YEARS_FROM_NOW":
                        $resulting_date = adodb_mktime(0, 0, 0, date_i18n('m'), date_i18n('d'), date_i18n('Y') + $date_value);
                        break;
                    case "DATE":
                        $date_object = wpv_filter_get_date_and_format($date_value);
                        $date_value = $date_object->date;
                        $format = $date_object->format;
                        $resulting_date = wpv_filter_parse_date_get_resulting_date( $date_value, $format );
                        break;

                }
                if ($resulting_date != false) {
                    $date_format = str_replace($matches[0][$i], $resulting_date, $date_format);
                }
            }
        }

        return $date_format;
    }
}

if ( ! function_exists( 'wpv_filter_parse_date_get_resulting_date' ) )
{
	function wpv_filter_parse_date_get_resulting_date( $date_value, $format )
	{
		$date_value = (string) $date_value;

		if( !$format && strpos($date_value, ',') !== false ){

			$date_parts = explode(',', $date_value);
			$ret = adodb_mktime(0, 0, 0, $date_parts[1], $date_parts[0], $date_parts[2]);
			return $ret;
		}
		else
		{
			// just in case the Parser is not loaded yet
			if( class_exists('Toolset_DateParser') === false )
			{
				require_once(dirname(__FILE__) . "/expression-parser/parser.php");
			}

			$date_string = trim( trim( str_replace(',', '/', $date_value), "'" ) );

			$date = Toolset_DateParser::parseDate( $date_string, $format );
			if( is_object($date) && method_exists( $date, 'getTimestamp' ) )
			{
				$timestamp = $date->getTimestamp();// NOTE this timestamp construction should be compatible with the adodb_xxx functions
				return $timestamp;
			}

			return $date_value;
		}
	}
}

if ( ! function_exists( 'wpv_filter_get_date_and_format' ) )
{
	function wpv_filter_get_date_and_format($date_value)
	{

		$date_value = str_replace("'", '', $date_value);

		$ret = new stdClass();
		$ret->date = $date_value;
		$ret->format = false;

		$last = strrpos( $date_value, ',' );

		if( $last === false ) return $ret;

		$temp = trim( trim( substr($date_value, $last ), ',' ) );

		if( is_numeric( $temp ) )
		{
			return $ret;
		}
		else{
			$ret->date = trim( substr($date_value, 0, $last ) );
			$ret->format = trim(  trim( $temp, ',') );

			return $ret;
		}

		return $date_value;
	}
}

/*
 * Extra check for date for shortcode in shortcode. Called as filter in wpv_condition bellow.
 *
 * @note As of 1.9 this is not used in Views anymore
 */
if ( ! function_exists( 'wpv_add_time_functions' ) ) {
	function wpv_add_time_functions( $value ) {
		return wpv_filter_parse_date( $value );
	}
}

/**
 * Condition function to evaluate and display given block based on expressions
 * 'args' => arguments for evaluation fields
 * 
 * Supported actions and symbols:
 * 
 * Integer and floating-point numbers
 * Math operators: +, -, *, /
 * Comparison operators: &lt;, &gt;, =, &lt;=, &gt;=, !=
 * Boolean operators: AND, OR, NOT
 * Nested expressions - several levels of brackets
 * Variables defined as shortcode parameters starting with a dollar sign
 * empty() function that checks for blank or non-existing fields
 * 
 * 
 * @note As of 1.9, this is not used in Views anymore, seems to be used on the toolset-forms library
 */

if ( ! function_exists( 'wpv_condition' ) ) {
	function wpv_condition( $atts, $post_to_check = null ) {
		extract(
				shortcode_atts( array('evaluate' => FALSE), $atts )
		);

		// Do not overwrite global post
	//    global $post;

		// if in admin, get the post from the URL
		if ( is_admin() ) {
			if ( empty($post_to_check->ID) ) {
				// Get post
				if ( isset( $_GET['post'] ) ) {
					$post_id = (int) $_GET['post'];
				} else if ( isset( $_POST['post_ID'] ) ) {
					$post_id = (int) $_POST['post_ID'];
				} else {
					$post_id = 0;
				}
				if ( $post_id ) {
					$post = get_post( $post_id );
				}
			} else {
				$post = $post_to_check;
			}
		}
		if ( empty($post->ID) ) {
			global $post;
		}
		$has_post = true;
		if ( empty($post->ID) ) {
			// Will not execute any condition that involves custom fields
			$has_post = false;
		}
		
		if ( $has_post ) {
			do_action( 'wpv_condition', $post );
		}

		$logging_string = "Original expression: " . $evaluate;

		add_filter( 'wpv-extra-condition-filters', 'wpv_add_time_functions' );
		$evaluate = apply_filters( 'wpv-extra-condition-filters', $evaluate );
		
		$logging_string .= "; After extra conditions: " . $evaluate;

		// evaluate empty() statements for variables
		if ( $has_post ) {
			$empties = preg_match_all( "/empty\(\s*\\$(\w+)\s*\)/", $evaluate, $matches );
			if ( $empties && $empties > 0 ) {
				for ( $i = 0; $i < $empties; $i++ ) {
					$match_var = get_post_meta( $post->ID, $atts[$matches[1][$i]], true );
					$is_empty = '1=0';

					// mark as empty only nulls and ""  
		//            if ( is_null( $match_var ) || strlen( $match_var ) == 0 ) {
					if ( is_null( $match_var )
							|| ( is_string( $match_var ) && strlen( $match_var ) == 0 )
							|| ( is_array( $match_var ) && empty( $match_var ) ) ) {
						$is_empty = '1=1';
					}
					$evaluate = str_replace( $matches[0][$i], $is_empty, $evaluate );
					$logging_string .= "; After empty: " . $evaluate;
				}
			}
		}
		
		// find variables that are to be used as strings.
		// eg '$f1'
		// will replace $f1 with the actual field value
		if ( $has_post ) {
			$strings_count = preg_match_all( '/(\'[\$\w^\']*\')/', $evaluate, $matches );
			if ( $strings_count && $strings_count > 0 ) {
				for ( $i = 0; $i < $strings_count; $i++ ) {
					$string = $matches[1][$i];
					// remove single quotes from string literals to get value only
					$string = (strpos( $string, '\'' ) === 0) ? substr( $string, 1,
									strlen( $string ) - 2 ) : $string;
					if ( strpos( $string, '$' ) === 0 ) {
						$variable_name = substr( $string, 1 ); // omit dollar sign
						if ( isset( $atts[$variable_name] ) ) {
							$string = get_post_meta( $post->ID, $atts[$variable_name], true );
							$evaluate = str_replace( $matches[1][$i], "'" . $string . "'", $evaluate );
							$logging_string .= "; After variables I: " . $evaluate;
						}
					}
				}
			}
		}

		// find string variables and evaluate
		$strings_count = preg_match_all( '/((\$\w+)|(\'[^\']*\'))\s*([\!<>\=]+)\s*((\$\w+)|(\'[^\']*\'))/',
				$evaluate, $matches );

		// get all string comparisons - with variables and/or literals
		if ( $strings_count && $strings_count > 0 ) {
			for ( $i = 0; $i < $strings_count; $i++ ) {

				// get both sides and sign
				$first_string = $matches[1][$i];
				$second_string = $matches[5][$i];
				$math_sign = $matches[4][$i];

				// remove single quotes from string literals to get value only
				$first_string = (strpos( $first_string, '\'' ) === 0) ? substr( $first_string,
								1, strlen( $first_string ) - 2 ) : $first_string;
				$second_string = (strpos( $second_string, '\'' ) === 0) ? substr( $second_string,
								1, strlen( $second_string ) - 2 ) : $second_string;

				// replace variables with text representation
				if ( strpos( $first_string, '$' ) === 0 && $has_post ) {
					$variable_name = substr( $first_string, 1 ); // omit dollar sign
					if ( isset( $atts[$variable_name] ) ) {
						$first_string = get_post_meta( $post->ID,
								$atts[$variable_name], true );
					} else {
						$first_string = '';
					}
				}
				if ( strpos( $second_string, '$' ) === 0 && $has_post ) {
					$variable_name = substr( $second_string, 1 );
					if ( isset( $atts[$variable_name] ) ) {
						$second_string = get_post_meta( $post->ID,
								$atts[$variable_name], true );
					} else {
						$second_string = '';
					}
				}

				// don't do string comparison if variables are numbers 
				if ( !(is_numeric( $first_string ) && is_numeric( $second_string )) ) {
					// compare string and return true or false
					$compared_str_result = wpv_compare_strings( $first_string,
							$second_string, $math_sign );

					if ( $compared_str_result ) {
						$evaluate = str_replace( $matches[0][$i], '1=1', $evaluate );
					} else {
						$evaluate = str_replace( $matches[0][$i], '1=0', $evaluate );
					}
				} else {
					$evaluate = str_replace( $matches[1][$i], $first_string, $evaluate );
					$evaluate = str_replace( $matches[5][$i], $second_string, $evaluate );
				}
				$logging_string .= "; After variables II: " . $evaluate;
			}
		}

		// find remaining strings that maybe numeric values.
		// This handles 1='1'
		$strings_count = preg_match_all( '/(\'[^\']*\')/', $evaluate, $matches );
		if ( $strings_count && $strings_count > 0 ) {
			for ( $i = 0; $i < $strings_count; $i++ ) {
				$string = $matches[1][$i];
				// remove single quotes from string literals to get value only
				$string = (strpos( $string, '\'' ) === 0) ? substr( $string, 1, strlen( $string ) - 2 ) : $string;
				if ( is_numeric( $string ) ) {
					$evaluate = str_replace( $matches[1][$i], $string, $evaluate );
					$logging_string .= "; After variables III: " . $evaluate;
				}
			}
		}


		// find all variable placeholders in expression
		if ( $has_post ) {
			$count = preg_match_all( '/\$(\w+)/', $evaluate, $matches );

			$logging_string .= "; Variable placeholders: " . var_export( $matches[1],
							true );

			// replace all variables with their values listed as shortcode parameters
			if ( $count && $count > 0 ) {
				// sort array by length desc, fix str_replace incorrect replacement
				$matches[1] = wpv_sort_matches_by_length( $matches[1] );

				foreach ( $matches[1] as $match ) {
					if ( isset( $atts[$match] ) ) {
						$meta = get_post_meta( $post->ID, $atts[$match], true );
						if ( empty( $meta ) ) {
							$meta = "0";
						}
					} else {
						$meta = "0";
					}
					$evaluate = str_replace( '$' . $match, $meta, $evaluate );
					$logging_string .= "; After variables IV: " . $evaluate;
				}
			}
		}

		$logging_string .= "; End evaluated expression: " . $evaluate;

		toolset_wplog( $logging_string, 'debug', __FILE__, 'wpv_condition', 307 );
		// evaluate the prepared expression using the custom eval script
		$result = wpv_evaluate_expression( $evaluate );
		
		if ( $has_post ) {
			do_action( 'wpv_condition_end', $post );
		}

		// return true, false or error string to the conditional caller
		return $result;
	}
}

if ( ! function_exists( 'wpv_eval_check_syntax' ) ) {
	function wpv_eval_check_syntax( $code ) {
		try {
			return @eval( 'return true;' . $code );
		} catch( ParseError $parse_error ) {
			// PHP7 compatibility, eval() changed it's behaviour:
			//
			// http://php.net/manual/en/function.eval.php
			// As of PHP 7, if there is a parse error in the evaluated code, eval() throws a ParseError exception.
			// Before PHP 7, in this case eval() returned FALSE and execution of the following code continued normally.
			return false;
		}
	}
}

/**
 * 
 * Sort matches array by length so evaluate longest variable names first
 * 
 * Otherwise the str_replace would break a field named $f11 if there is another field named $f1
 * 
 * @param array $matches all variable names
 */
if ( ! function_exists( 'wpv_sort_matches_by_length' ) ) {
	function wpv_sort_matches_by_length( $matches ) {
		$length = count( $matches );
		for ( $i = 0; $i < $length; $i++ ) {
			$max = strlen( $matches[$i] );
			$max_index = $i;

			// find the longest variable
			for ( $j = $i + 1; $j < $length; $j++ ) {
				if ( strlen( $matches[$j] ) > $max ) {
					$max = $matches[$j];
					$max_index = $j;
				}
			}

			// swap
			$temp = $matches[$i];
			$matches[$i] = $matches[$max_index];
			$matches[$max_index] = $temp;
		}

		return $matches;
	}
}

/**
 * Boolean function for string comparison
 *
 * @param string $first first string to be compared
 * @param string $second second string for comparison
 * 
 * 
 */
if ( ! function_exists( 'wpv_compare_strings' ) ) {
	function wpv_compare_strings( $first, $second, $sign ) {
		// get comparison results
		$comparison = strcmp( $first, $second );

		// verify cases 'less than' and 'less than or equal': <, <=
		if ( $comparison < 0 && ($sign == '<' || $sign == '<=') ) {
			return true;
		}

		// verify cases 'greater than' and 'greater than or equal': >, >=
		if ( $comparison > 0 && ($sign == '>' || $sign == '>=') ) {
			return true;
		}

		// verify equal cases: =, <=, >=
		if ( $comparison == 0 && ($sign == '=' || $sign == '<=' || $sign == '>=') ) {
			return true;
		}

		// verify != case
		if ( $comparison != 0 && $sign == '!=' ) {
			return true;
		}

		// or result is incorrect
		return false;
	}
}

/**
 * 
 * Function that prepares the expression and calls eval()
 * Validates the input for a list of whitechars and handles internal errors if any
 * 
 * @param string $expression the expression to be evaluated 
 */
if ( ! function_exists( 'wpv_evaluate_expression' ) ) {
	function wpv_evaluate_expression( $expression ){
		//Replace AND, OR, ==
		$expression = strtoupper( $expression );
		$expression = str_replace( "AND", "&&", $expression );
		$expression = str_replace( "OR", "||", $expression );
		$expression = str_replace( "NOT", "!", $expression );
		$expression = str_replace( "=", "==", $expression );
		$expression = str_replace( "<==", "<=", $expression );
		$expression = str_replace( ">==", ">=", $expression );
		$expression = str_replace( "!==", "!=", $expression ); // due to the line above
		// validate against allowed input characters
		$count = preg_match( '/[0-9+-\=\*\/<>&\!\|\s\(\)]+/', $expression, $matches );

		// find out if there is full match for the entire expression	
		if ( $count > 0 ) {
			if ( strlen( $matches[0] ) == strlen( $expression ) ) {
				$valid_eval = wpv_eval_check_syntax( "return $expression;" );
				if ( $valid_eval ) {
					return eval( "return $expression;" );
				} else {
					return __( "Error while parsing the evaluate expression",
									'wpv-views' );
				}
			} else {
				return __( "Conditional expression includes illegal characters",
								'wpv-views' );
			}
		} else {
			return __( "Correct conditional expression has not been found",
							'wpv-views' );
		}

	}
}



if( !function_exists( 'toolset_array_merge_recursive_distinct' ) ) {

	/**
	 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
	 * this happens (documented behavior):
	 *
	 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('org value', 'new value'));
	 *
	 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array, as is the
	 * case with array_merge, i.e.:
	 *
	 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('new value'));
	 *
	 * Parameters are passed by reference, though only for performance reasons. They're not
	 * altered by this function.
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 *
	 * @since 2.2
	 */
	function toolset_array_merge_recursive_distinct( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset ( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged [ $key ] = toolset_array_merge_recursive_distinct( $merged [ $key ], $value );
			} else {
				$merged [ $key ] = $value;
			}
		}

		return $merged;
	}

}