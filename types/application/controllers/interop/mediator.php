<?php

/**
 * Provide interoperability with other plugins or themes when needed.
 *
 * Each plugin or a theme that Types needs to (actively) support should
 * have a dedicated "interoperability handler" that, when initialized,
 * will provide such support (preferably via actions and filters).
 *
 * Having everything located in one class will make it very easy to
 * handle and implement future compatibility issues and it will
 * reduce memory usage by loading the code only when needed.
 *
 * Use this as a singleton in production code.
 *
 * @since 2.2.7
 */
class Types_Interop_Mediator {

	private static $instance;

	public static function initialize() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->initialize_interop_handlers();
		}

		// Not giving away the instance on purpose
	}


	/**
	 * Get definitions of all interop handlers.
	 *
	 * Each one has a method for checking whether the handler is needed
	 * and a name - there must be a corresponding class Types_Interop_Handler_{$name}
	 * implementing the Types_Interop_Handler_Interface.
	 *
	 * @return array
	 * @since 2.2.7
	 */
	private function get_interop_handler_definitions() {

		$interop_handlers = array(
			array(
				'is_needed' => array( $this, 'is_wpml_active' ),
				'class_name' => 'Wpml'
			),
			array(
				'is_needed' => array( $this, 'is_divi_active' ),
				'class_name' => 'Divi'
			),
			array(
				'is_needed' => array( $this, 'is_use_any_font_active' ),
				'class_name' => 'Use_Any_Font'
			),
			array(
				'is_needed' => array( $this, 'is_the7_active' ),
				'class_name' => 'The7'
			),
		);

		return $interop_handlers;
	}


	/**
	 * Load and initialize interop handlers if the relevant plugin/theme is active.
	 *
	 * @since 2.2.7
	 */
	public function initialize_interop_handlers() {

		/**
		 * types_get_interop_handler_definitions
		 *
		 * Allows for adjusting interop handlers. See Types_Interop_Mediator::get_interop_handler_definitions() for details.
		 *
		 * @since 2.2.17
		 */
		$interop_handlers = apply_filters( 'types_get_interop_handler_definitions', $this->get_interop_handler_definitions() );

		foreach ( $interop_handlers as $handler_definition ) {
			$is_needed = call_user_func( $handler_definition['is_needed'] );

			if ( $is_needed ) {
				$handler_class_name = 'Types_Interop_Handler_' . $handler_definition['class_name'];
				call_user_func( $handler_class_name . '::initialize' );
			}
		}
	}


	/**
	 * Check whether WPML is active and configured.
	 *
	 * @return bool
	 * @since 2.2.7
	 */
	protected function is_wpml_active() {

		global $sitepress;
		$is_wpml_active = (
			defined( 'ICL_SITEPRESS_VERSION' )
			&& ! ICL_PLUGIN_INACTIVE
			&& ! is_null( $sitepress )
			&& class_exists( 'SitePress' )
		);

		return $is_wpml_active;
	}


	/**
	 * Check whether the Divi theme is loaded.
	 *
	 * @return bool
	 */
	protected function is_divi_active() {
		return function_exists( 'et_setup_theme' );
	}


	/**
	 * Check whether the The7 theme is loaded.
	 *
	 * @return bool
	 */
	protected function is_the7_active() {
		return ( 'the7' === $this->get_parent_theme_slug() );
	}


	/**
	 * Check whether the Use Any Font plugin is loaded.
	 *
	 * @return bool
	 */
	protected function is_use_any_font_active() {
		return function_exists( 'uaf_activate' );
	}


	/**
	 * Retrieve a "slugized" theme name.
	 *
	 * @return string
	 * @since 2.2.16
	 */
	private function get_parent_theme_slug() {

		/**
		 * @var WP_Theme|null $theme It should be WP_Theme but experience tells us that sometimes the theme
		 * manages to send an invalid value our way.
		 */
		$theme = wp_get_theme();

		if( ! $theme instanceof WP_Theme ) {
			// Something went wrong but we'll try to recover.
			$theme_name = $this->get_theme_name_from_stylesheet();
		} elseif ( is_child_theme() ) {

			$parent_theme = $theme->parent();

			// Because is_child_theme() can return true while $theme->parent() still returns false, oh dear god.
			if( ! $parent_theme instanceof WP_Theme ) {
				$theme_name = $this->get_theme_name_from_stylesheet();
			} else {
				$theme_name = $parent_theme->get( 'Name' );
			}
		} else {
			$theme_name = $theme->get( 'Name' );
		}

		// Handle $theme->get() returning false when the Name header is not set.
		if( false === $theme_name ) {
			return '';
		}

		$slug = str_replace( '-', '_', sanitize_title( $theme_name ) );

		return $slug;
	}


	private function get_theme_name_from_stylesheet() {
		$theme_name = '';

		$stylesheet = get_stylesheet();
		if( is_string( $stylesheet ) && ! empty( $stylesheet ) ) {
			$theme_name = $stylesheet;
		}

		return $theme_name;
	}

}