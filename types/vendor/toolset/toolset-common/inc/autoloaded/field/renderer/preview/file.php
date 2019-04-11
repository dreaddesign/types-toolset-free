<?php

/**
 * Preview renderer for all "file" fields.
 * 
 * By file field it is meant those fields that contain URL to any (generic) file. The result is the same as for
 * URL field, but with only filename being displayed as a link label.
 * 
 * @since 1.9.1
 */
final class Toolset_Field_Renderer_Preview_File extends Toolset_Field_Renderer_Preview_URL {

	/**
	 * @param string[] $url_components Result of parse_url().
	 * @return string Label of the resulting link.
	 * @since 1.9.1
	 */
	protected function get_link_label( $url_components ) {

		$file_name = sanitize_text_field( basename( toolset_getarr( $url_components, 'path' ) ) );

		return $file_name;
	}

}