<?php

class Toolset_Field_Renderer_Preview_URL extends Toolset_Field_Renderer_Preview_Base {

	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		if( is_string( $value ) && !empty( $value ) ) {
			
			$url_components = parse_url( $value );
			if( false == $url_components ) {
				return '';
			}

			$label = $this->get_link_label( $url_components );

			// Apply maximum item length on the link label, not the whole output
			$max_length = $this->get_maximum_item_length();
			if( 0 < $max_length && $max_length < strlen( $label ) ) {
				$label = substr( $label, 0, $max_length - 3 ) . '...';
			}

			// Build the actual link
			$link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $value ),
				sanitize_text_field( $label )
			);

			return $link;
		} else {
			return '';
		}
	}


	protected function get_maximum_total_length() {
		return 0;
	}


	/**
	 * @param string[] $url_components Result of parse_url().
	 * @return string Label of the resulting link.
	 * @since 1.9.1
	 */
	protected function get_link_label( $url_components ) {
		
		// Build link label only from host, path and query.
		$url_query = toolset_getarr( $url_components, 'query' );
		$url_query = ( empty( $url_query ) ? '' : '?' . $url_query );

		$url_path = toolset_getarr( $url_components, 'path' );
		$path_ends_with_slash = ( substr( $url_path, -1 ) == '/' );

		if( empty( $url_query ) && $path_ends_with_slash ) {
			// Omit last slash when it would be the last label character
			$url_path = substr( $url_path, 0, strlen( $url_path ) -1 );
		}

		$label = sprintf(
			'%s%s%s',
			toolset_getarr( $url_components, 'host' ),
			$url_path,
			$url_query
		);
		
		return $label;
	}

}