<?php

/**
 * Preview renderer for image fields.
 *
 * Tries to be clever about WP attachments and use thumbnails instead of full size images whenever possible.
 *
 * For displaying the full image on clicking on the preview, you need to enqueue the 'wpcf-js' script and
 * the 'wptoolset-forms-admin' style
 *
 * @since 1.9.1
 */
final class WPCF_Field_Renderer_Preview_Image extends WPCF_Field_Renderer_Preview_Base {


	/** Maximum width and height of an image. */
	const DEFAULT_MAX_IMAGE_SIZE = '60px';


	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		if( !is_string( $value ) || empty( $value ) ) {
			return '';
		}

		$original_image_url = $value;
		$image_url = $this->try_finding_attachment( $original_image_url );
		$image_size = esc_attr( $this->get_maximum_image_size() );

		$output = sprintf(
			'<div class="js-wpt-file-preview wpt-file-preview">
				<img src="%s" style="max-width: %s; max-height: %s; border: 1px grey solid;" data-full-src="%s" />
			</div>',
			esc_url( $image_url ),
			$image_size,
			$image_size,
			esc_url( $original_image_url )
		);

		return $output;
	}


	/**
	 * For given URL, try to find an attachment with this file and if successful, try finding thumbnail URL.
	 *
	 * Returns original URL on failure.
	 *
	 * @param string $url Image URL
	 * @return string URl of the thumbnail or original image.
	 * @since 1.9.1
	 */
	protected function try_finding_attachment( $url ) {
		$attachment_id = Types_Utils::get_attachment_id_by_url( $url );
		if( 0 == $attachment_id ) {
			return $url;
		}

		$attachment_src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
		if( false == $attachment_src ) {
			return $url;
		}

		return ( is_string( $attachment_src[0] ) ? $attachment_src[0] : $url );
	}


	protected function get_value_separator() {
		return '&nbsp;';
	}


	protected function get_maximum_image_size() {
		return wpcf_getarr( $this->args, 'maximum_image_size', self::DEFAULT_MAX_IMAGE_SIZE );
	}

	/**
	 * @inheritdoc
	 * @return int
	 */
	protected function get_maximum_total_length() {
		return 0;
	}

}
