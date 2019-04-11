<?php
if ( defined( 'WPT_UTILS' ) ) {
	return;
}

define( 'WPT_UTILS', true );

/**
 * utils.php
 *
 * A collection of .php utility functions for common use
 *
 * @package ToolsetCommon
 *
 * @since unknown
 */
if ( ! class_exists( 'Toolset_Utils', false ) ) {

	/**
	 * ToolsetUtils
	 *
	 * A collection of static methods to be used across Toolset plugins
	 *
	 * @since 1.7
	 */
	class Toolset_Utils {

		/**
		 * Check whether we are in the admin side.
		 *
		 * This relies heavily on is_admin(), which might produce false positives.
		 * As AJAX calls also happen in the backend, return FALSE when performing a Views frontend AJAX call.
		 *
		 * @return boolean
		 *
		 * @todo This is just a patch used for allowing CRED forms in Views frontend AJAX calls.
		 *     We shoudl decouple CRED forms that do need to render as in the frontend, and make them CRED-only if needed.
		 *
		 * @since 2.3.3
		 */
		public static function is_real_admin() {
			$is_maybe_frontend = (
				! is_admin()
				|| (
					defined( 'DOING_AJAX' )
					&& DOING_AJAX
					&& isset( $_REQUEST['action'] )
					&& in_array( $_REQUEST['action'], self::get_ajax_actions_array_to_exclude_on_frontend() )
				)
			);

			return ( ! $is_maybe_frontend );
		}

		/**
		 * Returns an array of ajax actions of third plugins to be excluded on frontend calling
		 *
		 * @return array
		 *
		 * @since 2.5.0
		 */
		public static function get_ajax_actions_array_to_exclude_on_frontend() {
			return array( 'wpv_get_view_query_results', 'wpv_get_archive_query_results', 'render_element_changed' );
		}

		/**
		 * help_box
		 *
		 * Creates the HTML version for the wpvToolsetHelp() javascript function
		 *
		 * @param array $data Data containing the attributes
		 *        text                    => The content to show inside the help box.
		 *        tutorial-button-text    => Optional button anchor text.
		 *        tutorial-button-url        => Optional button url.
		 *        link-text                => Optional link anchor text.
		 *        link-url                => Optional link url.
		 *        footer                    => 'true'|'false' Whether the help box should have a footer with a Close button (managed) and a "dismiss forever" button (not managed). Defaults to 'false'.
		 *        classname                => Additional classnames for the help box in a space-separated list.
		 *        close                    => 'true'|'false' Whether the help box should have a close button. Defaults to 'true'.
		 *        hidden                    => 'true'|'false' Whether the help box should be hidden by default. Defaults to 'false'.
		 *
		 * @since 1.7
		 */
		public static function help_box( $data = array() ) {
			if ( is_array( $data ) && ! empty( $data ) ) {
				$data_attr = '';
				foreach ( $data as $key => $value ) {
					if ( 'text' != $key ) {
						$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '"';
					}
				}
				?>
                <div class="js-show-toolset-message"<?php echo $data_attr; ?>>
					<?php
					if ( isset( $data['text'] ) ) {
						echo $data['text'];
					}
					?>
                </div>
				<?php
			}
		}

		public static function get_image_sizes( $size = '' ) {

			global $_wp_additional_image_sizes;

			$sizes = array();
			$get_intermediate_image_sizes = get_intermediate_image_sizes();

			// Create the full array with sizes and crop info
			foreach ( $get_intermediate_image_sizes as $_size ) {

				if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

					$sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
					$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
					$sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

					$sizes[ $_size ] = array(
						'width' => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop' => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}

			// Get only 1 size if found
			if ( $size ) {

				if ( isset( $sizes[ $size ] ) ) {
					return $sizes[ $size ];
				} else {
					return false;
				}
			}

			return $sizes;
		}

		/**
		 * Check if a value is numeric and represents a non-negative number (zero is also ok, floats are ok).
		 *
		 * @param $value
		 *
		 * @return bool
		 * @since m2m
		 */
		public static function is_nonnegative_numeric( $value ) {
			return ( is_numeric( $value ) && ( 0 <= $value ) );
		}


		/**
		 * Check if a value is numeric and represents an integer (not a float).
		 *
		 * @param $value
		 *
		 * @return bool
		 * @since m2m
		 */
		public static function is_integer( $value ) {
			// http://stackoverflow.com/questions/2559923/shortest-way-to-check-if-a-variable-contains-positive-integer-using-php
			return ( (int) $value == $value && is_numeric( $value ) );
		}


		/**
		 * Check if a value is numeric, represents an integer, and is non-negative.
		 *
		 * @param $value
		 *
		 * @return bool
		 */
		public static function is_nonnegative_integer( $value ) {
			return (
				self::is_nonnegative_numeric( $value )
				&& self::is_integer( $value )
			);
		}

		/**
		 * Check if a value is numeric, represents an integer (not a float) and positive.
		 *
		 * @param mixed $value
		 *
		 * @return bool
		 * @since m2m
		 */
		public static function is_natural_numeric( $value ) {
			return (
				self::is_nonnegative_integer( $value )
				&& ( 0 < (int) $value )
			);
		}


		/**
		 * Changes array of items into string of items, separated by comma and sql-escaped
		 *
		 * @see https://coderwall.com/p/zepnaw
		 *
		 * Taken from wpml_prepare_in().
		 *
		 * @param mixed|array $items item(s) to be joined into string
		 * @param string $format %s or %d
		 *
		 * @return string Items separated by comma and sql-escaped
		 * @since m2m
		 */
		public static function prepare_mysql_in( $items, $format = '%s' ) {
			global $wpdb;

			$items = (array) $items;
			$how_many = count( $items );
			$prepared_in = '';

			if ( $how_many > 0 ) {
				$placeholders = array_fill( 0, $how_many, $format );
				$prepared_format = implode( ",", $placeholders );
				$prepared_in = $wpdb->prepare( $prepared_format, $items );
			}

			return $prepared_in;
		}

		/**
		 * Check for a custom field value's "emptiness".
		 *
		 * "0" is also a valid value that we need to take into account.
		 *
		 * @param $field_value
		 ** @return bool
		 *
		 * @since 2.2.3
		 */
		public static function is_field_value_truly_empty( $field_value ) {
			$is_truly_empty = ( empty( $field_value ) && ! is_numeric( $field_value ) );

			return $is_truly_empty;
		}


		/**
		 * Return an ID of an attachment by searching the database with the file URL.
		 *
		 * First checks to see if the $url is pointing to a file that exists in
		 * the wp-content directory. If so, then we search the database for a
		 * partial match consisting of the remaining path AFTER the wp-content
		 * directory. Finally, if a match is found the attachment ID will be
		 * returned.
		 *
		 * Taken from:
		 *
		 * @link http://frankiejarrett.com/get-an-attachment-id-by-url-in-wordpress/
		 *
		 * @param string $url URL of the file.
		 *
		 * @return int|null Attachment ID if it exists.
		 * @since 2.2.9
		 */
		public static function get_attachment_id_by_url( $url ) {

			// Split the $url into two parts with the wp-content directory as the separator.
			$parsed_url = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );

			// Get the host of the current site and the host of the $url, ignoring www.
			$this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
			$file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );

			// Return nothing if there aren't any $url parts or if the current host and $url host do not match.
			$attachment_path = toolset_getarr( $parsed_url, 1 );
			if ( ! isset( $attachment_path ) || empty( $attachment_path ) || ( $this_host != $file_host ) ) {
				return null;
			}

			// Now we're going to quickly search the DB for any attachment GUID with a partial path match.
			// Example: /uploads/2013/05/test-image.jpg
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s",
				'%' . $attachment_path
			);

			$attachment = $wpdb->get_col( $query );

			if ( is_array( $attachment ) && ! empty( $attachment ) ) {
				return (int) array_shift( $attachment );
			}

			return null;
		}


		/**
		 * Set a value in a nested array, creating the structure as needed.
		 *
		 * @param array &$array The array to be modified.
		 * @param string[] $path Array of keys, each element means one level of nesting.
		 * @param mixed $value The value to be assigned to the last level.
		 *
		 * Example:
		 *
		 * $result = Toolset_Utils::set_nested_value( $result, array( 'a', 'b', 'c' ), 'x' );
		 *
		 * Then $result would be:
		 *
		 * array(
		 *     'a' => array(
		 *         'b' => array(
		 *             'c' => 'x'
		 *         )
		 *     )
		 * );
		 *
		 * If there are any other elements set, they will not be touched.
		 *
		 * @return array
		 */
		public static function set_nested_value( &$array, $path, $value ) {
			if ( ! is_array( $array ) || ! is_array( $path ) ) {
				throw new InvalidArgumentException();
			}

			if ( empty( $path ) ) {
				return $value;
			}

			$next_level = array_shift( $path );

			if ( ! array_key_exists( $next_level, $array ) ) {
				$array[ $next_level ] = array();
			}

			$array[ $next_level ] = self::set_nested_value( $array[ $next_level ], $path, $value );

			return $array;
		}


		/**
         * Safely resolve a lowercase callback name into a handler class name even if mb_convert_case() is not available.
         *
         * It will always work correctly with values that contain only alphabetic characters, numbers and underscores.
         * But nothing else should be used in callback names anyway.
         *
         * Example: "types_ajax_m2m_action" will become "Types_Ajax_M2M_Action"
         *
		 * @param string $callback
		 *
		 * @return string Name of the handler class.
         * @since 3.0
		 */
		public static function resolve_callback_class_name( $callback ) {

		    // Use the native solution if available (which will happen in the vast majority of most cases).
		    if( function_exists( 'mb_convert_case' ) && defined( 'MB_CASE_TITLE' ) ) {
		        return mb_convert_case( $callback, MB_CASE_TITLE );
            }

            // mb_convert_case() works this way - it also capitalizes first letters after numbers
			$name_parts = preg_split( "/_|[0-9]/", $callback );
			$parts_ucfirst = array_map( function( $part ) { return ucfirst( $part ); }, $name_parts );

			$result = '';
			foreach( $parts_ucfirst as $part ) {
				$result .= $part;
				$delimiter_position = strlen( $result );

				// Put back the delimiter (it could be a number or an underscore)
				if( $delimiter_position < strlen( $callback ) ) {
					$result .= $callback[ $delimiter_position ];
				}
			}

			return $result;
		}
	}

}

if ( ! function_exists( 'wp_json_encode' ) ) {

	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		/*
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
			$args = array( $data, $options, $depth );
		} elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			$args = array( $data, $options );
		} else {
			$args = array( $data );
		}

		$json = call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) ) {
			return $json;
		}

		try {
			$args[0] = _wp_json_sanity_check( $data, $depth );
		} catch ( Exception $e ) {
			return false;
		}

		return call_user_func_array( 'json_encode', $args );
	}

	if ( ! function_exists( '_wp_json_sanity_check' ) ) {

		function _wp_json_sanity_check( $data, $depth ) {
			if ( $depth < 0 ) {
				throw new Exception( 'Reached depth limit' );
			}

			if ( is_array( $data ) ) {
				$output = array();
				foreach ( $data as $id => $el ) {
					// Don't forget to sanitize the ID!
					if ( is_string( $id ) ) {
						$clean_id = _wp_json_convert_string( $id );
					} else {
						$clean_id = $id;
					}

					// Check the element type, so that we're only recursing if we really have to.
					if ( is_array( $el ) || is_object( $el ) ) {
						$output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
					} elseif ( is_string( $el ) ) {
						$output[ $clean_id ] = _wp_json_convert_string( $el );
					} else {
						$output[ $clean_id ] = $el;
					}
				}
			} elseif ( is_object( $data ) ) {
				$output = new stdClass;
				foreach ( $data as $id => $el ) {
					if ( is_string( $id ) ) {
						$clean_id = _wp_json_convert_string( $id );
					} else {
						$clean_id = $id;
					}

					if ( is_array( $el ) || is_object( $el ) ) {
						$output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
					} elseif ( is_string( $el ) ) {
						$output->$clean_id = _wp_json_convert_string( $el );
					} else {
						$output->$clean_id = $el;
					}
				}
			} elseif ( is_string( $data ) ) {
				return _wp_json_convert_string( $data );
			} else {
				return $data;
			}

			return $output;
		}

	}

	if ( ! function_exists( '_wp_json_convert_string' ) ) {

		function _wp_json_convert_string( $string ) {
			static $use_mb = null;
			if ( is_null( $use_mb ) ) {
				$use_mb = function_exists( 'mb_convert_encoding' );
			}

			if ( $use_mb ) {
				$encoding = mb_detect_encoding( $string, mb_detect_order(), true );
				if ( $encoding ) {
					return mb_convert_encoding( $string, 'UTF-8', $encoding );
				} else {
					return mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );
				}
			} else {
				return wp_check_invalid_utf8( $string, true );
			}
		}

	}
}

if ( ! class_exists( 'Toolset_ArrayUtils', false ) ) {

	Class Toolset_ArrayUtils {

		private $value = null;
		private $property = null;

		function __construct( $property = null, $value = null ) {
			$this->value = $value;
			$this->property = $property;
		}

		function filter_array( $element ) {
			if ( is_object( $element ) ) {

				if ( property_exists( $element, $this->property ) === false ) {
					return null;
				}

				return $element->{$this->property} === $this->value;
			} elseif ( is_array( $element ) ) {

				if ( isset( $element[ $this->property ] ) === false ) {
					return null;
				}

				return $element[ $this->property ] === $this->value;
			} else {

				throw new Exception( sprintf( "Element parameter should be an object or an array, %s given.", gettype( $element ) ) );
			}
		}

		public function remap_by_property( $data ) {
			return $data[ $this->property ];
		}

		function value_in_array( $array ) {
			if ( ! is_array( $array ) ) {
				return false;
			}

			return in_array( $this->value, array_values( $array ) );
		}

		function sort_string_ascendant( $a, $b ) {
			return strcmp( $a[ $this->property ], $b[ $this->property ] );
		}

	}

}


if ( ! class_exists( 'Toolset_ErrorHandler', false ) ) {

	/**
	 * ErrorHandler that can be used to catch internal PHP errors
	 * and convert to an ErrorException instance.
	 */
	abstract class Toolset_ErrorHandler {

		/**
		 * Active stack
		 *
		 * @var array
		 */
		protected static $stack = array();

		/**
		 * Check if this error handler is active
		 *
		 * @return bool
		 */
		public static function started() {
			return (bool) self::getNestedLevel();
		}

		/**
		 * Get the current nested level
		 *
		 * @return int
		 */
		public static function getNestedLevel() {
			return count( self::$stack );
		}

		/**
		 * Starting the error handler
		 *
		 * @param int $errorLevel
		 */
		public static function start( $errorLevel = E_WARNING ) {
			if ( ! self::$stack ) {
				set_error_handler( array( get_called_class(), 'addError' ), $errorLevel );
				register_shutdown_function( array( get_called_class(), 'handle_shutdown' ), true );
			}

			self::$stack[] = null;
		}

		/**
		 * Stopping the error handler
		 *
		 * @param  bool $throw Throw the ErrorException if any
		 *
		 * @return null|ErrorException
		 * @throws ErrorException If an error has been catched and $throw is true
		 */
		public static function stop( $throw = false ) {
			$errorException = null;

			if ( self::$stack ) {
				$errorException = array_pop( self::$stack );

				if ( ! self::$stack ) {
					restore_error_handler();
				}

				if ( $errorException && $throw ) {
					throw $errorException;
				}
			}

			return $errorException;
		}

		public static function handle_shutdown() {
			if ( self::is_fatal() ) {
				do_action( 'toolset-shutdown-hander' );
			}
			exit;
		}

		public static function is_fatal() {
			$error = error_get_last();
			$ignore = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED;
			if ( ( $error['type'] & $ignore ) == 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Stop all active handler
		 *
		 * @return void
		 */
		public static function clean() {
			if ( self::$stack ) {
				restore_error_handler();
			}

			self::$stack = array();
		}

		/**
		 * Add an error to the stack
		 *
		 * @param int $errno
		 * @param string $errstr
		 * @param string $errfile
		 * @param int $errline
		 *
		 * @return void
		 */
		public static function addError( $errno, $errstr = '', $errfile = '', $errline = 0 ) {
			if ( count( self::$stack ) ) {
				$stack = &self::$stack[ count( self::$stack ) - 1 ];
			} else {
				$stack = null;
			}
			$stack = new ErrorException( (string) $errstr, 0, (int) $errno, (string) $errfile, (int) $errline );
		}

	}

}

if ( ! function_exists( 'get_called_class' ) ) {

	/**
	 * PHP 5.2 support.
	 *
	 * get_called_class() is only in PHP >= 5.3, this is a workaround.
	 * This function is needed by WPDDL_Theme_Integration_Abstract (and others).
	 */
	function get_called_class() {
		$bt = debug_backtrace();
		$l = 0;
		$matches = null;
		do {
			$l ++;
			if ( isset( $bt[ $l ]['file'] ) ) {
				$lines = file( $bt[ $l ]['file'] );
				$callerLine = $lines[ $bt[ $l ]['line'] - 1 ];
				preg_match( '/([a-zA-Z0-9\_]+)::' . $bt[ $l ]['function'] . '/', $callerLine, $matches );
			}
		} while ( isset( $matches[1] ) && $matches[1] === 'parent' );

		return isset( $matches[1] ) ? $matches[1] : "";
	}

}


if ( ! function_exists( 'toolset_getarr' ) ) {


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
	 * @since 1.8
	 */
	function toolset_getarr( &$source, $key, $default = '', $valid = null ) {
		if ( isset( $source[ $key ] ) ) {
			$val = $source[ $key ];

			if ( is_callable( $valid ) && ! call_user_func( $valid, $val ) ) {
				return $default;
			} elseif ( is_array( $valid ) && ! in_array( $val, $valid ) ) {
				return $default;
			}

			return $val;
		} else {
			return $default;
		}
	}

}


if ( ! function_exists( 'toolset_getget' ) ) {

	/**
	 * Safely retrieve a key from $_GET variable.
	 *
	 * This is a wrapper for toolset_getarr(). See that for more information.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param null|array $valid
	 *
	 * @return mixed
	 * @since 1.9
	 */
	function toolset_getget( $key, $default = '', $valid = null ) {
		return toolset_getarr( $_GET, $key, $default, $valid );
	}

}


if ( ! function_exists( 'toolset_getpost' ) ) {

	/**
	 * Safely retrieve a key from $_POST variable.
	 *
	 * This is a wrapper for toolset_getarr(). See that for more information.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param null|array $valid
	 *
	 * @return mixed
	 * @since 1.9
	 */
	function toolset_getpost( $key, $default = '', $valid = null ) {
		return toolset_getarr( $_POST, $key, $default, $valid );
	}

}


if ( ! function_exists( 'toolset_ensarr' ) ) {

	/**
	 * Ensure that a variable is an array.
	 *
	 * @param mixed $array The original value.
	 * @param array $default Default value to use when no array is provided. This one should definitely be an array,
	 *     otherwise the function doesn't make much sense.
	 *
	 * @return array The original array or a default value if no array is provided.
	 *
	 * @since 1.9
	 */
	function toolset_ensarr( $array, $default = array() ) {
		return ( is_array( $array ) ? $array : $default );
	}

}


if ( ! function_exists( 'toolset_wraparr' ) ) {

	/**
	 * Wrap a variable value in an array if it's not array already.
	 *
	 * @param mixed $input
	 *
	 * @return array
	 * @since 1.9.1
	 */
	function toolset_wraparr( $input ) {
		return ( is_array( $input ) ? $input : array( $input ) );
	}

}


if ( ! function_exists( 'toolset_getnest' ) ) {

	/**
	 * Get a value from nested associative array.
	 *
	 * This function will try to traverse a nested associative array by the set of keys provided.
	 *
	 * E.g. if you have $source = array( 'a' => array( 'b' => array( 'c' => 'my_value' ) ) ) and want to reach 'my_value',
	 * you need to write: $my_value = wpcf_getnest( $source, array( 'a', 'b', 'c' ) );
	 *
	 * @param mixed|array $source The source array.
	 * @param string[] $keys Keys which will be used to access the final value.
	 * @param null|mixed $default Default value to return when the keys cannot be followed.
	 *
	 * @return mixed|null Value in the nested structure defined by keys or default value.
	 *
	 * @since 1.9
	 */
	function toolset_getnest( &$source, $keys = array(), $default = null ) {

		$current_value = $source;

		// For detecting if a value is missing in a sub-array, we'll use this temporary object.
		// We cannot just use $default on every level of the nesting, because if $default is an
		// (possibly nested) array itself, it might mess with the value retrieval in an unexpected way.
		$missing_value = new stdClass();

		while ( ! empty( $keys ) ) {
			$current_key = array_shift( $keys );
			$is_last_key = empty( $keys );

			$current_value = toolset_getarr( $current_value, $current_key, $missing_value );

			if ( $is_last_key ) {
				// Apply given default value.
				if ( $missing_value === $current_value ) {
					return $default;
				} else {
					return $current_value;
				}
			} elseif ( ! is_array( $current_value ) ) {
				return $default;
			}
		}

		return $default;
	}

}
