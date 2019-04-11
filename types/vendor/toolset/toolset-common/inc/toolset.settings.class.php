<?php

/**
* Toolset Settings class
* 
* It implements both ArrayAccess and dynamic properties. ArrayAccess is deprecated.
*
* @since 2.0
*
* @property string	$show_admin_bar_shortcut
* @property string	$shortcodes_generator
*/
class Toolset_Settings implements ArrayAccess {


    /**
	* WP Option Name for Views settings.
	*/
    const OPTION_NAME = 'toolset_options';


    /* ************************************************************************* *\
        SETTING NAMES
    \* ************************************************************************* */


	/**
	* Determine whether frontend admin bar menu should be displayed.
	*
	* String value, 'on' or 'off'.
	*
	* Defaults to 'on'.
	*
	* @since 2.0
	*/
	const ADMIN_BAR_CREATE_EDIT = 'show_admin_bar_shortcut';


	/**
	* Determine whether the backend shortcode generator admin bar menu should be displayed.
	*
	* String value, 'unset', 'disable', 'editor' or 'always'.
	*
	* Defaults to 'unset'.
	*
	* @since 2.0
	*/
	const ADMIN_BAR_SHORTCODES_GENERATOR = 'shortcodes_generator';
	
	/**
	* List of Types postmeta fields that we want to index in Relevanssi.
	*
	* Array.
	*
	* Defaults to an empty array.
	*
	* @since 2.2
	*/
	const RELEVANSSI_FIELDS_TO_INDEX = 'relevanssi_fields_to_index';

    /**
     * Bootstrap version that is expected to be used in a theme.
     *
     * Allowed values are:
     * - '2': Bootstrap 2.0
     * - '3': Bootstrap 3.0
     * - '3.toolset': Bootstrap 3.0, but load it from toolset common
     * - '-1': Site is not using Bootstrap (@since 1.9)
     * - '1' or missing value (or perhaps anything else, too): Bootstrap version not set
     *
     * @since unknown
     */
    const BOOTSTRAP_VERSION = 'toolset_bootstrap_version';



	/* ************************************************************************* *\
        SINGLETON
    \* ************************************************************************* */


	/**
	* @var Toolset_Settings Instance of Toolset_Settings.
	*/
	private static $instance = null;

	/**
	 * @var
	 * Used only to keep original value from the filter
	 */
	private $toolset_bootstrap_version_from_filter = null;


	/**
	* @return Toolset_Settings The instance of Toolset_Settings.
	*/
	public static function get_instance() {
		if( null == Toolset_Settings::$instance ) {
			Toolset_Settings::$instance = new Toolset_Settings();
		}
		return Toolset_Settings::$instance;
	}
	
	public static function clear_instance() {
		if ( Toolset_Settings::$instance ) {
			Toolset_Settings::$instance = null;
		}
	}


	/* ************************************************************************* *\
        DEFAULTS
    \* ************************************************************************* */


	/**
	* @var array Default setting values.
	* @todo reformat and turn strings into documented constants
	*/



	protected static $defaults = array(
		Toolset_Settings::ADMIN_BAR_CREATE_EDIT				=> 'on',
		Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR	=> 'unset',
		Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX		=> array(),
        Toolset_Settings::BOOTSTRAP_VERSION				    =>  3,
	);


	/**
	* @return array Associative array of default values for settings.
	*/
	public function get_defaults() {
		return Toolset_Settings::$defaults;
	}


	/**
	* Toolset_Settings constructor.
	*
	* @todo make this private
	*/
	protected function __construct() {

		add_action('ddl-before_init_layouts_plugin', array($this, 'check_is_this_first_layouts_activation') , 10);

		$this->load_settings();
        $this->set_default_bootstrap_version_from_views();

		add_filter( 'toolset-toolset_bootstrap_version_filter', array( $this, 'toolset_bootstrap_version_filter' ) );
		add_filter( 'toolset-toolset_bootstrap_version_manually_selected', array( $this, 'toolset_bootstrap_version_manually_selected' ) );

	}

	function check_is_this_first_layouts_activation(){

		$layouts = apply_filters( 'ddl-get_all_layouts_settings', array() );
		$bs_value_from_db = $this->toolset_bootstrap_version_manually_selected();

		if( false === $bs_value_from_db ){
			$set_version_in_db = ( count( $layouts ) === 0 ) ? '99' : '98';
			$set_version = ( count( $layouts ) === 0 ) ? '3.toolset' : '3';
			$this->__set("toolset_bootstrap_version", $set_version_in_db );
			$this->save();
			$this->toolset_bootstrap_version = $set_version;
		}
	}


	/*
	 * Get bootstrap version from Views and in case if option is not set from General tab
	 * as default set option from Views options.
	 */
	protected function set_default_bootstrap_version_from_views(){
		if(class_exists('WPV_Settings')){
			$views_settings = WPV_Settings::get_instance();

			$bootstrap_version_in_toolset_options = true;

			$get_raw_options = get_option( Toolset_Settings::OPTION_NAME );
			if( $get_raw_options && !isset( $get_raw_options['toolset_bootstrap_version'] ) ){
				$bootstrap_version_in_toolset_options = false;
			}

			if( (int) $views_settings->wpv_bootstrap_version !== 1 && false === $bootstrap_version_in_toolset_options ){
				$this->toolset_bootstrap_version = (int) $views_settings->wpv_bootstrap_version;
			}
		}
	}


	/* ************************************************************************* *\
        OPTION LOADING AND SAVING
    \* ************************************************************************* */


	private $settings = null;


	/**
	* Load settings from the database.
	*/
	private function load_settings() {
		$this->settings = get_option( self::OPTION_NAME );
		if ( ! is_array( $this->settings ) ) {
			$this->settings = array(); // Defaults will be used in this case.
		}


		/*
		 * Botstrap version filter
		 */
		$this->toolset_bootstrap_version = apply_filters( 'toolset_set_boostrap_option', $this->toolset_bootstrap_version );
		$this->override_bootstrap_option_selection();
	}

	private function convert_to_real_bs_versions($bs_version){
		$real_versions = array(
			"99"=>"3.toolset",
			"98"=>"3"
		);

		if(isset($real_versions[$bs_version])){
			return $real_versions[$bs_version];
		} else {
			return $bs_version;
		}
	}

	/**
	 * Comparing value from filter and value in database,
	 * we will force manually selected value (from db)
	 */
	private function override_bootstrap_option_selection(){
		if( has_filter( 'toolset_set_boostrap_option' ) ){
			// set filter value as backup
			$this->toolset_bootstrap_version_from_filter = $this->toolset_bootstrap_version;
			// get raw selection from database
			$selected_bs_version = $this->toolset_bootstrap_version_manually_selected();

			if( false !== $selected_bs_version && in_array( $selected_bs_version, array("99","98") ) ){
				// if user already selected something force that version
				$this->toolset_bootstrap_version = $this->toolset_bootstrap_version_from_filter;
			} else {
				$this->toolset_bootstrap_version = $selected_bs_version;
			}
		} else {
			$this->toolset_bootstrap_version = $this->convert_to_real_bs_versions($this->toolset_bootstrap_version);
		}

	}

	/**
	 * @return string/false
	 * Get unfiltered option value directly from db
	 */
	public function toolset_bootstrap_version_manually_selected(){
		$get_raw_options = get_option( Toolset_Settings::OPTION_NAME );
		if( $get_raw_options && isset( $get_raw_options['toolset_bootstrap_version'] ) ){
			$bs_version_in_db = $get_raw_options['toolset_bootstrap_version'];
			return $bs_version_in_db;
		}
		return false;
	}
	public function toolset_bootstrap_version_manually_selected_converted_value(){
		$get_db_value = $this->toolset_bootstrap_version_manually_selected();
		return $this->convert_to_real_bs_versions($get_db_value);
	}

	/**
	 * @return string/false
	 * Return value defined by filter
	 */
	public function toolset_bootstrap_version_filter(){
		if( isset( $this->toolset_bootstrap_version_from_filter ) ){
			return $this->toolset_bootstrap_version_from_filter;
		}
		return false;
	}


	/**
	* Persists settings in the database
	*
	* @todo Consider some optimalization - only update options that have changed.
	*/
	public function save() {
		update_option( self::OPTION_NAME, $this->settings );
	}



	/* ************************************************************************* *\
        ArrayAccess IMPLEMENTATION
    \* ************************************************************************* */


	/**
	* isset() for ArrayAccess interface.
	*
	* @param mixed $offset setting name
	* @return bool
	*/
	public function offsetExists( $offset ) {
		return isset( $this->settings[ $offset ] );
	}


	/**
	* Getter for ArrayAccess interface.
	*
	* @param mixed $offset setting name
	* @return mixed setting value
	*/
	public function offsetGet( $offset ) {
		if ( $offset ) {
			return $this->get( $offset );
		} else {
			return null;
		}
	}


	/**
	* Setter for ArrayAccess interface.
	*
	* @param mixed $offset
	* @param mixed $value
	*/
	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}


	/**
	* unset() for ArrayAccess interface.
	*
	* @param mixed $offset
	*/
	public function offsetUnset( $offset ) {
		if ( isset( $this->settings[ $offset ] ) ) {
			unset( $this->settings[ $offset ] );
		}
	}


	/* ************************************************************************* *\
        MAGIC PROPERTIES
    \* ************************************************************************* */


	/**
	* PHP dynamic setter.
	*
	* @param mixed $key
	* @return mixed
	*/
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	* PHP dynamic setter.
	*
	* @param string $key
	* @param mixed $value
	*/
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}


	/**
	* PHP dynamic fields unset() method support
	* @param string $key
	*/
	public function __unset( $key ) {
		if ( $this->offsetExists( $key ) ) {
			$this->offsetUnset( $key );
		}
	}

	/**
	* PHP dynamic support for isset($this->name)
	* @param string $key
	* @return boolean
	*/
	public function __isset( $key ) {
		return $this->offsetExists( $key );
	}


	/* ************************************************************************* *\
        GENERIC GET/SET METHODS
    \* ************************************************************************* */


	/**
	* Obtain a value for a setting (or all settings).
	*
	* @param string $key name of the setting to retrieve
	* @return mixed value of the key or an array with all key-value pairs
	*/
	public function get( $key = null ) {
		if ( $key ) {
			// Retrieve one setting
			$method_name = '_get_' . $key;
			if ( method_exists( $this, $method_name ) ) {
				// Use custom getter if it exists
				return $this->$method_name();
			} else {
				return $this->get_raw_value( $key );
			}
		} else {
			// Retrieve all settings
			return wp_parse_args( $this->settings, Toolset_Settings::$defaults );
		}
	}


	/**
	* Get "raw" value from settings or default settings, without taking custom getters into account.
	*
	* @param string $key Setting name
	* @return null|mixed Setting value or null if it's not defined anywhere.
	*/
	private function get_raw_value( $key ) {

		if ( isset( $this->settings[ $key ] ) ) {
			// Return user-set value, if available
			return $this->settings[ $key ];
		} elseif ( isset( Toolset_Settings::$defaults[ $key ] ) ) {
			// Use default value, if available
			return Toolset_Settings::$defaults[ $key ];
		} else {
			// There isn't any key like that
			return null;
		}
	}


	/**
	* Set Setting(s).
	*
	* Usage:
	*  One key-value pair
	*  set('key', 'value');
	*
	*  Multiple key-value pairs
	*  set( array('key1' => 'value1', 'key2' => 'value2' );
	*
	* @param mixed $param1 name of the setting or an array with name-value pairs of the settings (bulk set)
	* @param mixed $param2 value of the setting
	*/
	public function set( $param1, $param2 = null ) {
		if ( is_array( $param1 ) ) {
			foreach( $param1 as $key => $value ) {
				$this->settings[ $key ] = $value;
			}
		} else if ( 
			is_object( $param1 ) 
			&& is_a( $param1, 'Toolset_Settings' ) 
		) {
			// DO NOTHING.
			// It's assigned already.
		} else if ( 
			is_string( $param1 ) 
			|| is_integer( $param1 ) 
		) {
			$key = $param1;
			$value = $param2;
			// Use custom setter if it exists.
			$method_name = '_set_' . $key;
			if ( method_exists( $this, $method_name ) )  {
				$this->$method_name( $value );
			} else {
				// Fall back to array access mode
				$this->settings[ $key ] = $value;
			}

		}
	}


	/**
	* Find out whether we have any knowledge about setting of given name.
	*
	* Looks for it's value, default value or for custom getter.
	*
	* @param string $key Setting name.
	* @return bool True if setting seems to exist.
	*/
	public function has_setting( $key ) {
		return (
			isset( $this->settings[ $key ] )
			|| isset( Toolset_Settings::$defaults[ $key ] )
			|| method_exists( $this, '_get_' . $key )
		);
	}


	/* ************************************************************************* *\
        CUSTOM GETTERS AND SETTERS
    \* ************************************************************************* */

	/**
	* Safe show_admin_bar_shortcut getter, allways returns a valid value.
	*
	* @since 2.0
	*/
	protected function _get_show_admin_bar_shortcut() {
		$value = $this->get_raw_value( Toolset_Settings::ADMIN_BAR_CREATE_EDIT );
		if ( ! $this->_is_valid_show_admin_bar_shortcut( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::ADMIN_BAR_CREATE_EDIT ];
		}
		return $value;
	}

	/**
	* Safe show_admin_bar_shortcut setter.
	*
	* @since 2.0
	*/
	protected function _set_show_admin_bar_shortcut( $value ) {
		if ( $this->_is_valid_show_admin_bar_shortcut( $value ) ) {
			$this->settings[ Toolset_Settings::ADMIN_BAR_CREATE_EDIT ] = $value;
		}
	}
	
	/**
	* Helper validation for show_admin_bar_shortcut.
	*
	* @since 2.0
	*/
	protected function _is_valid_show_admin_bar_shortcut( $value ) {
		return in_array( $value, array( 'on', 'off' ) );
	}
	
	/**
	* Safe shortcodes_generator getter, allways returns a valid value.
	*
	* @since 2.0
	*/
	protected function _get_shortcodes_generator() {
		$value = $this->get_raw_value( Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR );
		if ( ! $this->_is_valid_shortcodes_generator( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR ];
		}
		return $value;
	}

	/**
	* Safe shortcodes_generator setter.
	*
	* @since 2.0
	*/
	protected function _set_shortcodes_generator( $value ) {
		if ( $this->_is_valid_shortcodes_generator( $value ) ) {
			$this->settings[ Toolset_Settings::ADMIN_BAR_SHORTCODES_GENERATOR ] = $value;
		}
	}
	
	/**
	* Helper validation for shortcodes_generator.
	*
	* @since 2.0
	*/
	protected function _is_valid_shortcodes_generator( $value ) {
		return in_array( $value, array( 'unset', 'disable', 'editor', 'always' ) );
	}
	
	/**
	* Safe shortcodes_generator getter, allways returns a valid value.
	*
	* @since 2.0
	*/
	protected function _get_relevanssi_fields_to_index() {
		$value = $this->get_raw_value( Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX );
		if ( ! $this->_is_valid_relevanssi_fields_to_index( $value ) ) {
			return Toolset_Settings::$defaults[ Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX ];
		}
		return $value;
	}

	/**
	* Safe shortcodes_generator setter.
	*
	* @since 2.0
	*/
	protected function _set_relevanssi_fields_to_index( $value ) {
		if ( $this->_is_valid_relevanssi_fields_to_index( $value ) ) {
			$this->settings[ Toolset_Settings::RELEVANSSI_FIELDS_TO_INDEX ] = $value;
		}
	}
	
	/**
	* Helper validation for shortcodes_generator.
	*
	* @since 2.0
	*/
	protected function _is_valid_relevanssi_fields_to_index( $value ) {
		return is_array( $value );
	}


}
