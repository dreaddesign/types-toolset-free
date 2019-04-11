<?php

/**
* Toolset_Shortcode_Transformer
*
* Generic class to manage the Toolset shortcodes transformation to allow usage of different shortocode formats.
*
* @since 2.5.7
*/
    
class Toolset_Shortcode_Transformer {

	public function init_hooks() {
		add_filter( 'the_content', array( $this, 'replace_shortcode_placeholders_with_brackets' ), 4 );

		add_filter( 'toolset_transform_shortcode_format', array( $this, 'replace_shortcode_placeholders_with_brackets' ) );
    }

	/**
	 * In Views 2.5.0, we introduced support for shortcodes using placeholders instead of bracket. The selected placeholder
	 * for the left bracket "[" was chosen to be the "{!{" and the selected placeholder for the right bracket "]" was chosen
	 * to be the "}!}". This was done to allow the use of Toolset shortcodes inside the various page builder modules fields.
	 * Here, we are replacing early the instances of the placeholders with the normal brackets, in order for them to be
	 * treated as normal shortcodes.
	 *
	 * @param $content
	 *
	 * @return mixed
	 *
	 * @since 2.5.0
	 * @since 2.5.7  It was moved from Views to Toolset Common to allow shortcode transformation even if Views is disabled.
	 */
	public function replace_shortcode_placeholders_with_brackets( $content ) {
		$content = str_replace( '{!{', '[', $content );
		$content = str_replace( '}!}', ']', $content );
		return $content;
	}
    
}