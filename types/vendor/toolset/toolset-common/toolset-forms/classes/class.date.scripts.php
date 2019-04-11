<?php

class WPToolset_Field_Date_Scripts {

    public function __construct() {

        global $pagenow;

	    $is_frontend = ( !Toolset_Utils::is_real_admin() );

	    $current_admin_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;
	    $field_group_edit_pages = array( 'wpcf-edit-usermeta', 'wpcf-edit', 'wpcf-termmeta-edit' );
	    $is_types_edit_page = in_array( $current_admin_page, $field_group_edit_pages );

	    $backend_field_edit_pages = array(
		    'profile.php', 'post-new.php', 'user-edit.php', 'user-new.php', 'post.php', 'admin-ajax.php',
		    'edit-tags.php', 'term.php'
	    );
	    $is_edit_page = ( !$is_frontend && in_array( $pagenow, $backend_field_edit_pages ) );

	    /**
	     * Allows for overriding the conditions for enqueuing scripts for date field.
	     *
	     * @param bool $enqueue_scripts If true, the scripts will be enqueued disregarding other conditions.
	     */
	    $is_activated_by_filter = apply_filters( 'toolset_forms_enqueue_date_scripts', false );

	    $is_cred_active = defined('CRED_FE_VERSION');

        if ( $is_frontend || $is_types_edit_page || $is_edit_page || $is_activated_by_filter ) {
            add_action( 'admin_enqueue_scripts', array( $this,'date_enqueue_scripts' ) );
            if ( $is_cred_active ) {
                add_action( 'wp_enqueue_scripts', array( $this, 'date_enqueue_scripts' ) );
            }
        }
        $this->localization_slug = false;
    }

    public function date_enqueue_scripts() {

    	// Prevent loading scripts on custom field group edit screen
	    if ( Toolset_Utils::is_real_admin() ) {
		    $screen = get_current_screen();
		    if ( 'types_page_wpcf-edit' == $screen->id ) {
			    return;
		    }
	    }

        wp_register_script(
		    'wptoolset-forms',
		    WPTOOLSET_FORMS_RELPATH . '/js/main.js',
		    array( 'jquery', 'underscore', 'suggest' ),
		    WPTOOLSET_FORMS_VERSION,
		    true
	    );

	    wp_register_script(
		    'wptoolset-field-date',
		    WPTOOLSET_FORMS_RELPATH . '/js/date.js',
		    array( 'jquery-ui-datepicker', 'wptoolset-forms' ),
		    WPTOOLSET_FORMS_VERSION,
		    true
	    );

	    $this->maybe_localize_datepicker();

	    wp_enqueue_script( 'wptoolset-field-date' );

	    $date_format = self::getDateFormat();
	    $js_date_format = $this->_convertPhpToJs( $date_format );
	    $calendar_image = WPTOOLSET_FORMS_RELPATH . '/images/calendar.gif';
	    $calendar_image = apply_filters( 'wptoolset_filter_wptoolset_calendar_image', $calendar_image );
	    $calendar_image_readonly = WPTOOLSET_FORMS_RELPATH . '/images/calendar-readonly.gif';
	    $calendar_image_readonly = apply_filters( 'wptoolset_filter_wptoolset_calendar_image_readonly', $calendar_image_readonly );
	    $js_data = array(
		    'buttonImage' => $calendar_image,
		    'buttonText' => __( 'Select date', 'wpv-views' ),
		    'dateFormat' => $js_date_format,
		    'dateFormatPhp' => $date_format,
		    'dateFormatNote' => esc_js( sprintf( __( 'Input format: %s', 'wpv-views' ), $date_format ) ),
		    'yearMin' => intval( self::timetodate( Toolset_Date_Utils::TIMESTAMP_LOWER_BOUNDARY, 'Y' ) ) + 1,
		    'yearMax' => self::timetodate( Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY, 'Y' ),
		    'ajaxurl' => admin_url( 'admin-ajax.php', null ),
		    'readonly' => esc_js( __( 'This is a read-only date input', 'wpv-views' ) ),
		    'readonly_image' => $calendar_image_readonly,
		    'datepicker_style_url' => WPTOOLSET_FORMS_RELPATH . '/css/wpt-jquery-ui/jquery-ui-1.11.4.custom.css'
	    );
	    wp_localize_script( 'wptoolset-field-date', 'wptDateData', $js_data );

    }


	/**
	 * Localize the datepicker manually if we're in WordPress below 4.7.
	 *
	 * From 4.7 on it is localized by the core automatically, so this method does nothing in that case.
	 *
	 * @since 2.3
	 */
    private function maybe_localize_datepicker() {

	    $wp_version = get_bloginfo( 'version' );
	    if ( version_compare( $wp_version, '4.7' ) != - 1 ) {
		    return;
	    }

	    $date_utils = Toolset_Date_Utils::get_instance();

	    if ( $date_utils->is_date_format_supported( self::getDateFormat() ) ) {

		    // Not calling get_user_locale() because this code will never be reached in WordPress 4.7.
		    $locale = get_locale();
		    $lang = str_replace( '_', '-', $locale );

		    $is_registered = $this->try_registering_datepicker_localization_script( $lang );
		    if( $is_registered ) {
			    $this->localization_slug = $lang;
		    } else {
			    $lang = substr( $lang, 0, 2 );
			    $is_registered = $this->try_registering_datepicker_localization_script( $lang );
			    if( $is_registered ) {
				    $this->localization_slug = $lang;
			    }
		    }
	    }

	    if ( $this->localization_slug ) {
		    wp_enqueue_script( 'jquery-ui-datepicker-local-' . $this->localization_slug );
	    }
    }


	/**
	 * Check whether a datepicker localization script is available for given language code and if so,
	 * and register it.
	 *
	 * Registers the script as "jquery-ui-datepicker-local-{$language_code}".
	 *
	 * @param string $language_code
	 * @return bool True if the script was registered, false if not.
	 * @since 2.3
	 */
	private function try_registering_datepicker_localization_script( $language_code ) {
    	$script_path =  '/js/i18n/jquery.ui.datepicker-' . $language_code . '.js';
    	if( ! file_exists( WPTOOLSET_FORMS_ABSPATH . $script_path ) ) {
    		return false;
	    }

		wp_register_script(
			"jquery-ui-datepicker-local-{$language_code}",
			WPTOOLSET_FORMS_RELPATH . $script_path,
			array( 'jquery-ui-core', 'jquery', 'jquery-ui-datepicker' ),
			WPTOOLSET_FORMS_VERSION,
			true
		);

    	return true;
	}


	/**
	 * @param $date_format
	 *
	 * @return mixed
	 * @deprecated Use Toolset_Date_Utils::convert_to_js_date_format() instead.
	 */
    protected function _convertPhpToJs( $date_format )
    {
    	$date_utils = Toolset_Date_Utils::get_instance();
        return $date_utils->convert_to_js_date_format( $date_format );
    }

	/**
	 * @return mixed|string|void
	 * @deprecated Use Toolset_Date_Utils::get_supported_date_format() instead.
	 */
	public static function getDateFormat() {
		$date_utils = Toolset_Date_Utils::get_instance();
		return $date_utils->get_supported_date_format();
	}

    public static function timetodate( $timestamp, $format = null )
    {
        if ( is_null( $format ) ) {
            $format = self::getDateFormat();
        }
        return self::_isTimestampInRange( $timestamp ) ? @adodb_date( $format, $timestamp ) : false;
    }


	/**
	 * @param $timestamp
	 *
	 * @return bool
	 * @deprecated Use Toolset_Date_Utils::is_timestamp_in_range().
	 */
    public static function _isTimestampInRange( $timestamp )
    {
    	$date_utils = Toolset_Date_Utils::get_instance();
        return $date_utils->is_timestamp_in_range( $timestamp );
    }
}