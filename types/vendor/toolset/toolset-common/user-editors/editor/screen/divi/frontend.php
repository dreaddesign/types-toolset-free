<?php

/**
 * Editor class for the frontend editor screen of Divi Builder.
 *
 * Handles all the functionality needed to allow the Divi Builder to work with Content Template editing on the frontend.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Screen_Divi_Frontend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Divi_Frontend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();
	}

	public function initialize() {
		/**
		 * Filter Divi Builder ajax request exceptions to also include Views the selected Views ajax actions.
		 */
		add_filter( 'et_builder_load_requests', array( $this, 'add_divi_builder_request_expections' ) );

		add_filter( 'et_get_option_et_divi_divi_use_excerpt', array( $this, 'prevent_the_excerpt_truncation' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_third_party_css', array( $this, 'set_custom_css_for_ct' ), 10, 2 );
	}

	/**
	 * Divi allows only a specific set of AJAX calls to access its data. The good thing is that it also provides a filter
	 * to add third party actions in this set of allowed calls. Here we are adding ours to enable custom search and pagination
	 * output to render Divi Builder content, when they are done using AJAX.
	 *
	 * @param array   $load_requests The set of allowed actions.
	 *
	 * @return array  The enriched array of allowed actions.
	 */
	public function add_divi_builder_request_expections( $load_requests ) {

		$load_requests['action'][] = 'wpv_get_view_query_results';
		$load_requests['action'][] = 'wpv_get_archive_query_results';

		return $load_requests;
	}

	/**
	 * Divi is using the custom function "truncate_post" to generate the archive post content. This custom function,
	 * though, checks for a Divi settings option to decide what to output. Here we are using a filter to override the
	 * saved value for this option and adjust it in a way that the "truncate_post" always returns the CT content.
	 *
	 * @param string   $option_value  The value of the option.
	 * @param string   $option_name   The name of the option.
	 *
	 * @return string  The filtered option value.
	 */
	public function prevent_the_excerpt_truncation( $option_value, $option_name ) {
		if ( 'et_divi_divi_use_excerpt' === $option_name ) {
			$option_value = 'on';
		}

		return $option_value;
	}

	/**
	 * Set the custom CSS of a Content Template built with Divi Builder, when a page assigned to that CT is loaded.
	 *
	 * @param string   $custom_css   The page's custom CSS.
	 * @param int      $template_id  The Content Template id.
	 *
	 * @return string  The filtered custom CSS containing the Custom CSS of the CT used.
	 */
	public function set_custom_css_for_ct( $custom_css, $template_id ) {
		if ( $this->is_divi_builder_used_in_ct( $template_id ) ) {
			$custom_css .= get_post_meta( $template_id, '_et_pb_custom_css', true );
		}

		return $custom_css;
	}

	/**
	 * Detect if Divi builder is used on the defined CT.
	 *
	 * @param  int   $template_id   The id of the CT.
	 *
	 * @return bool  Return true if Divi Builder is used for the defined CT or false otherwise.
	 */
	public function is_divi_builder_used_in_ct( $template_id ) {
		$divi_builder_enabled = false;
		if ( ! empty( $template_id ) ) {
			$builder_selected = get_post_meta( $template_id, '_toolset_user_editors_editor_choice', true );
			if ( $this->constants->constant( 'DIVI_SCREEN_ID' ) === $builder_selected ) {
				$divi_builder_enabled = true;
			}
		}

		return $divi_builder_enabled;
	}
}
