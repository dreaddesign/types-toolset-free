<?php

/**
 * Field renderer that uses toolset-forms to render a field.
 *
 * @since 1.9
 */
class Toolset_Field_Renderer_Toolset_Forms extends Toolset_Field_Renderer_Abstract {


	protected $form_id;

	protected $hide_field_title = false;

	protected $purpose;


	public function __construct( $field, $form_id = '' ) {
		parent::__construct( $field );

		$this->form_id = $form_id;
	}


	/**
	 * Additional setup of the renderer.
	 *
	 * @param $args array Following arguments are supported:
	 *     @type string $form_id
	 *     @type bool $hide_field_title
	 */
	public function setup( $args = array() ) {

		$this->form_id = toolset_getarr( $args, 'form_id', $this->form_id );

		$this->hide_field_title = (bool) toolset_getarr( $args, 'hide_field_title', $this->hide_field_title );

		$this->purpose = toolset_getarr( $args, 'purpose', Toolset_Common_Bootstrap::MODE_FRONTEND );
	}

	/**
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function render( $echo = false ) {

		$field_config = $this->get_toolset_forms_config();

		if( $this->hide_field_title ) {
			$field_config['title'] = '';
			$field_config['hide_field_title'] = true;
		}

		$value_in_intermediate_format = $this->field->get_value();

		// At the moment, we're not able to properly render the post reference field in this context.
		// This needs to be fixed before the final m2m release. For now, we at least display a different message
		// than the scary "couldn't render field" error.
		if( $this->field->get_field_type()->get_slug() === 'post' ) {
			$output = sprintf(
				'<div class="js-wpt-field wpt-field" data-wpt-type="post">
					<div class="js-wpt-field-items">
						<div class="js-wpt-field-item wpt-field-item">
							<div id="message" class="notice notice-warning"><p>%s</p></div>            
						</div>
                    </div>
    			</div>',
				sprintf(
					__( 'The post reference field %s could not have been displayed here. In order to set the post reference, visit the Edit Post page.', 'wpcf' ),
					'<strong>' . $this->field->get_definition()->get_display_name() . '</strong>'
				)
			);
		} else {
			$output = wptoolset_form_field( $this->get_form_id(), $field_config, $value_in_intermediate_format );
		}

		if( $echo ) {
			echo $output;
		}

		return $output;
	}


	protected function get_form_id() { return $this->form_id; }


	protected function get_toolset_forms_config() {
		return wptoolset_form_filter_types_field( $this->field->get_definition()->get_definition_array(), $this->field->get_object_id() );
	}

}