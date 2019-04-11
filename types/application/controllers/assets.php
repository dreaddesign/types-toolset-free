<?php

/**
 * Types assets manager.
 * 
 * All assets (like images and other files) should be defined here, referenced only by constants and their paths/URLs
 * retrieved only with methods of this class. 
 * 
 * That will make their management way easier when some paths or filenames need to be changed.
 * 
 * @since 2.0
 */
final class Types_Assets {
	
	// Constants with image paths relative to public/images.
	const IMG_AJAX_LOADER_OVERLAY = '/ajax-loader-overlay.gif';


	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Initialize the asset manager for script and styles. 
	 * 
	 * Must be called early, before init, else special measures are needed.
	 * 
	 * @since 2.0
	 */
	public function initialize_scripts_and_styles() {
		Types_Asset_Manager::get_instance();
	}
	

	private function __clone() { }

	private function __construct() { }


	/**
	 * For given image, return it's relative URL.
	 * 
	 * @param string $image Image defined as a constant of Types_Assets.
	 * @return string Relative URL of the image.
	 * @since 2.0
	 */
	public function get_image_url( $image ) {
		return TYPES_RELPATH . '/public/images' . $image;
	}

}