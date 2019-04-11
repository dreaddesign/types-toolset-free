<?php

/**
 * Manages assets in the Toolset Common Library.
 *
 * For now, it deals only with images - it allows to get their URL very easily without
 * relying on specific paths and path constants. Please use it for all new images.
 *
 * In production, the singleton pattern should be used.
 *
 * @since m2m
 */
class Toolset_Asset_Manager {

	const IMAGE_TOOLBOT = 'icon-help-message.png';
	const IMAGE_TOOLBOT_SVG = 'icon-help-message.svg';

	private static $instance;

	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self( new Toolset_Constants() );
		}

		return self::$instance;
	}


	/** @var Toolset_Constants $constants */
	private $constants;


	/**
	 * Toolset_Asset_Manager constructor.
	 *
	 * Do not use in production code.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants ) {
		$this->constants = $constants;
	}


	/**
	 * Get the URL of an image.
	 *
	 * @param string $image One of the image constants in Toolset_Asset_Manager. When you need to use an image
	 *     that doesn't have a constant here, add it first.
	 * @param bool $is_frontend Is the image intended for display on the frontend?
	 *     That may influence the used protocol of the URL (HTTP or HTTPS).
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get_image_url( $image, $is_frontend ) {
		if( ! is_string( $image ) || ! is_bool( $is_frontend ) ) {
			throw new InvalidArgumentException();
		}

		$base_url = (
			$is_frontend
				? $this->constants->constant( 'TOOLSET_COMMON_FRONTEND_URL' )
				: $this->constants->constant( 'TOOLSET_COMMON_URL' )
		);

		return trailingslashit( $base_url ) . 'res/images/' . $image;
	}

}
