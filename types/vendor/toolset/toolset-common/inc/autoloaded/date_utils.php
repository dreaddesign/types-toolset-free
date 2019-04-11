<?php

/**
 * Helper class for unified manipulation with timestamps, dates and date formats.
 *
 * On several occasions, we're dealing with time formats that are used for custom field date pickers. In this case,
 * the options for date formatting are far more limited than what PHP date() function offers by jQuery UI Datepicker
 * possibilities (see here: https://api.jqueryui.com/datepicker/#utility-formatDate).
 *
 * This class provides - among other things - a list of safe default date formats and methods for working with them,
 * including the conversion to jQuery UI Datepicker format string.
 *
 * @since 2.3
 */
class Toolset_Date_Utils {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() { }

	private function __clone() { }


	// Date format properties

	/** Human-readable description of a date format. */
	const DESCRIPTION = 'description';
	const TIME_FORMAT = 'time_format';
	const DISPLAY_IN_DIALOGS = 'display_in_dialogs';

	/**
	 * @var array Default supported date formats.
	 */
	private $supported_date_formats_defaults = array(
		'F j, Y' => array(
			self::DESCRIPTION => 'Month dd, yyyy',
			self::TIME_FORMAT => 'g:i a',
			self::DISPLAY_IN_DIALOGS => true
		),
		'Y/m/d' => array(
			self::DESCRIPTION => 'yyyy/mm/dd',
			self::TIME_FORMAT => 'g:i a',
			self::DISPLAY_IN_DIALOGS => true
		),
		'm/d/Y' => array(
			self::DESCRIPTION => 'mm/dd/yyyy',
			self::TIME_FORMAT => 'h:i a',
			self::DISPLAY_IN_DIALOGS => true
		),
		'd/m/Y' => array(
			self::DESCRIPTION => 'dd/mm/yyyy',
			self::TIME_FORMAT => 'G:i',
			self::DISPLAY_IN_DIALOGS => true
		),
		'd/m/y' => array(
			self::DESCRIPTION => 'dd/mm/yy',
			self::TIME_FORMAT => 'H:i',
			self::DISPLAY_IN_DIALOGS => true
		),
		'Y/n/j' => array(
			self::DESCRIPTION => 'yyyy/m/d',
			self::TIME_FORMAT => 'g:i a',
			self::DISPLAY_IN_DIALOGS => false
		),
		'd.m.Y' => array(
			self::DESCRIPTION => 'dd.mm.yyyy',
			self::TIME_FORMAT => 'G:i',
			self::DISPLAY_IN_DIALOGS => false
		),
		'j.n.Y' => array(
			self::DESCRIPTION => 'd.m.yyyy',
			self::TIME_FORMAT => 'H:i',
			self::DISPLAY_IN_DIALOGS => false
		),
		'Y-m-d' => array(
			self::DESCRIPTION => 'yyyy-mm-dd',
			self::TIME_FORMAT => 'G:i',
			self::DISPLAY_IN_DIALOGS => false
		),
		'j F Y' => array(
			self::DESCRIPTION => 'dd Month yyyy',
			self::TIME_FORMAT => 'g:i a',
			self::DISPLAY_IN_DIALOGS => false
		)
	);


	/**
	 * @var null|array Supported date formats, once generated from defaults, will be cached here.
	 */
	private $supported_date_formats = null;


	// Return values for get_supported_date_formats()
	const FORMAT_FULL = 'full';
	const FORMAT_VALUES_ONLY = 'values';
	const FORMAT_VALUES_DESCRIPTIONS_ARRAY = 'values_descriptions';


	/**
	 * Get an array of supported date formats.
	 *
	 * When called the first time, it will run the date format defaults through a filter and cache the result.
	 * This allows third-party plugins or custom code to add new possibilities (but at their own risk - Toolset expects
	 * that everything the filter returns is valid).
	 *
	 * @param string $format Determines how the result will be shaped:
	 *     - FORMAT_FULL returns an array with date formats as keys and arrays with date format attributes as values
	 *     - FORMAT_VALUES_ONLY returns a flat array of date formats
	 *     - FORMAT_VALUES_DESCRIPTIONS_ARRAY returns a "format => description" array (used in legacy code)
	 *
	 * @return array
	 */
	public function get_supported_date_formats( $format = self::FORMAT_FULL, $display_only = false ) {
		if( null == $this->supported_date_formats ) {

			/**
			 * toolset_supported_date_formats
			 *
			 * Allows for modifying the array of supported date formats. Check out $this->supported_date_formats_defaults
			 * to see how the array is shaped.
			 *
			 * @param array $supported_date_formats
			 * @since 2.3
			 */
			$this->supported_date_formats = apply_filters( 'toolset_supported_date_formats', $this->supported_date_formats_defaults );
		}

		$selected_date_formats = $this->supported_date_formats;

		// When displaying options, we don't want to show everything we support (too many options)
		if( $display_only ) {
			foreach( $selected_date_formats as $date_format => $attributes ) {
				if( ! toolset_getarr( $attributes, self::DISPLAY_IN_DIALOGS, true ) ) {
					unset( $selected_date_formats[ $date_format ] );
				}
			}
		}

		switch( $format ) {
			case self::FORMAT_VALUES_ONLY:
				return array_keys( $selected_date_formats );
			case self::FORMAT_VALUES_DESCRIPTIONS_ARRAY:
				$results = array();
				foreach( $selected_date_formats as $format => $format_args ) {
					$results[ $format ] = toolset_getarr( $format_args, self::DESCRIPTION, $format );
				}
				return $results;
			case self::FORMAT_FULL:
			default:
				return $selected_date_formats;
		}
	}


	/**
	 * Get the default, allways valid, date format if all else fails.
	 * @return string
	 */
	public function get_default_date_format() {
		return 'F j, Y';
	}


	/**
	 * Get the date format as configured in WordPress settings or a default one if the configured one isn't supported
	 * by Toolset.
	 *
	 * @return string
	 */
	public function get_supported_date_format() {
		$wp_date_format = get_option( 'date_format' );
		if ( ! $this->is_date_format_supported( $wp_date_format ) ) {
			return $this->get_default_date_format();
		}

		return $wp_date_format;
	}


	/**
	 * Check if a particular date format is supported by Toolset.
	 *
	 * @param string $format
	 * @return bool
	 */
	public function is_date_format_supported( $format ) {
		$supported_formats = $this->get_supported_date_formats( self::FORMAT_VALUES_ONLY );
		return in_array( $format, $supported_formats );
	}


	// Map for converting from PHP date format string to the jQuery UI Datepicker one.
	//
	// Careful about ordering of array elements. If a character is on the right side, it can never
	// be used on the left side afterwards.
	private static $date_format_map = array(
		'd' => 'dd',
		'j' => 'd',
		'D' => 'D',
		'l' => 'DD',
		'm' => 'mm',
		'n' => 'm',
		'M' => 'M',
		'F' => 'MM',
		'y' => 'y',
		'Y' => 'yy',
		'z' => 'o',
	);


	/**
	 * Convert a date format string from PHP to jQuery UI Datepicker.
	 *
	 * Only format characters specified in self::$date_format_map will be properly converted.
	 * The result is run through a filter.
	 *
	 * @param $php_date_format
	 * @return string
	 */
	public function convert_to_js_date_format( $php_date_format ) {

		$date_format = $php_date_format;

		foreach( self::$date_format_map as $from_string => $to_string ) {
			$date_format = str_replace( $from_string, $to_string, $date_format );
		}

		/**
		 * toolset_convert_date_format_to_js
		 *
		 * Applied on a date format string after it is converted for jQuery UI Datepicker.
		 *
		 * @param string $date_format Converted string with the JS date format.
		 * @param string $php_date_format Original PHP date format.
		 * @since 2.3
		 */
		$date_format = apply_filters( 'toolset_convert_date_format_to_js', $date_format, $php_date_format );

		return $date_format;
	}


	// 15/10/1582 00:00 - 31/12/3000 23:59
	const TIMESTAMP_LOWER_BOUNDARY = -12219292800;
    const TIMESTAMP_UPPER_BOUNDARY = 32535215940;


	/**
	 * Check if a timestamp is in the range supported by Toolset.
	 *
	 * @param int $timestamp
	 * @return bool
	 * @since 2.3
	 */
	public function is_timestamp_in_range( $timestamp ) {
		return self::TIMESTAMP_LOWER_BOUNDARY <= $timestamp && $timestamp <= self::TIMESTAMP_UPPER_BOUNDARY;
	}


	/**
	 * Process a date format string with custom escaping characters and turn it into a standard
	 * date format string as accepted by date().
	 *
	 * We needed to provide an alternative for backslash in date format strings (because WordPress
	 * gradually strips backslashes from postmeta, where [types] shortcodes might be stored).
	 *
	 * The custom escaping character is '%'. Here, it will be converted to '\'.
	 * If, for some strange reason, the user needs to print '%', they can use '%%' instead.
	 *
	 * @param $original_format_string
	 * @return string
	 * @since 2.3
	 */
	public function process_custom_escaping_characters_on_format_string( $original_format_string ) {

		// Match escaping characters (but not when there's more of them in a sequence)
		// and replace them by single backslashes.
		$format_string = preg_replace( '/([^%]|^)(%)((?!%))/', '\\1\\', $original_format_string );

		// Match '%%' and replace them with single character '%'.
		$format_string = preg_replace( '/(%{2})/', '%', $format_string );

		/**
		 * toolset_escape_date_format_string
		 *
		 * Allow for overriding the date format string after processing the custom escaping character.
		 *
		 * @param string $format_string The format string after processing.
		 * @param string $original_format_string The format string before processing.
		 * @param string $escaping_character The custom escaping character that has been replaced.
		 *
		 * @since 2.3
		 */
		$format_string = apply_filters( 'toolset_escape_date_format_string', $format_string, $original_format_string, '%' );

		return $format_string;
	}
}