<?php

/**
 * The script and style asset manager for Types implemented in a standard Toolset way.
 * 
 * Keeping this separate from Types_Assets also for performance reasons (this is not needed at all times).
 * 
 * @since 2.0
 */
final class Types_Asset_Manager extends Toolset_Assets_Manager {

	// Script handles
	//
	// NEVER EVER use handles defined here as hardcoded strings, they may change at any time.

	const SCRIPT_ADJUST_MENU_LINK = 'types-adjust-menu-link';
	const SCRIPT_SLUG_CONFLICT_CHECKER = 'types-slug-conflict-checker';

	const SCRIPT_PAGE_EDIT_POST_TYPE = 'types-page-edit-post-type';
	const SCRIPT_PAGE_EDIT_TAXONOMY = 'types-page-edit-taxonomy';

	// Registered in legacy Types

	const SCRIPT_JQUERY_UI_VALIDATION = 'wpcf-form-validation';
	const SCRIPT_ADDITIONAL_VALIDATION_RULES = 'wpcf-form-validation-additional';



	private static $types_instance;


	/**
	 * @return Types_Asset_Manager
	 */
	static public function get_instance() {
		if( null === self::$types_instance ) {
			self::$types_instance = new self();
		}

		return self::$types_instance;
	}
	
	
	protected function __initialize_styles() {
		return parent::__initialize_styles();
	}


	protected function __initialize_scripts() {

		$this->register_script(
			self::SCRIPT_ADJUST_MENU_LINK,
			TYPES_RELPATH . '/public/page/adjust_submenu_links.js',
			array( 'jquery', 'underscore' ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_SLUG_CONFLICT_CHECKER,
			TYPES_RELPATH . '/public/js/slug_conflict_checker.js',
			array( 'jquery', 'underscore' ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_PAGE_EDIT_POST_TYPE,
			TYPES_RELPATH . '/public/page/edit_post_type/main.js',
			array( 'jquery', 'underscore', self::SCRIPT_SLUG_CONFLICT_CHECKER, self::SCRIPT_UTILS ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_PAGE_EDIT_TAXONOMY,
			TYPES_RELPATH . '/public/page/edit_taxonomy/main.js',
			array( 'jquery', 'underscore', self::SCRIPT_SLUG_CONFLICT_CHECKER, self::SCRIPT_UTILS ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_JQUERY_UI_VALIDATION,
			WPCF_RES_RELPATH . '/js/jquery-form-validation/' . $this->choose_script_version( 'jquery.validate.min.js', 'jquery.validate.js'),
			array( 'jquery' ),
			'1.8.1'
		);


		$this->register_script(
			self::SCRIPT_ADDITIONAL_VALIDATION_RULES,
			$this->get_additional_validation_script_url(),
			array( 'jquery', self::SCRIPT_JQUERY_UI_VALIDATION ),
			TYPES_VERSION
		);


		return parent::__initialize_scripts();
	}


	/**
	 * Unfortunately, we need to have this public because of the Divi.
	 * And, unfortunately, we can't define it as a constant because PHP < 5.6 doesn't support that.
	 *
	 * @since 2.2.7
	 */
	public function get_additional_validation_script_url() {
		return WPCF_RES_RELPATH . '/js/jquery-form-validation/additional-methods.min.js';
	}


	/**
	 * Choose a production (usually minified) or debugging (non-minified) version of
	 * a script depending on the script debugging mode.
	 *
	 * See SCRIPT_DEBUG constant
	 *
	 * @param string $production_version File name of the production script version.
	 * @param string $debugging_version File name of the debugging script version.
	 *
	 * @return string
	 * @since 2.2.7
	 */
	private function choose_script_version( $production_version, $debugging_version ) {
		$is_debug_mode = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
		return ( $is_debug_mode ? $debugging_version : $production_version );
	}


	/**
	 * @param Toolset_Script $script
	 */
	public function register_toolset_script( $script ) {
		if ( ! isset( $this->scripts[ $script->handle ] ) ) {
			$this->scripts[ $script->handle ] = $script;
		}
	}


	/**
	 * @param Toolset_Style $style
	 */
	public function register_toolset_style( $style ) {
		if( !isset( $this->styles[ $style->handle ] ) ) {
			$this->styles[ $style->handle ] = $style;
		}
	}

}