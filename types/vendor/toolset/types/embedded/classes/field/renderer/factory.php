<?php

/**
 * Factory for field renderers.
 *
 * This is especially useful because in most cases different types of fields have to use different renderers in
 * different context. The logic for choosing the right renderer should be completely encapsulated in this class.
 *
 * @since 1.9.1
 */
class WPCF_Field_Renderer_Factory {

	private static $instance = null;

	private function __construct() { }

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Get a preview renderer for given field.
	 *
	 * @param WPCF_Field_Instance_Abstract $field
	 * @param array $args Arguments for the preview renderer.
	 * @return WPCF_Field_Renderer_Preview_Base Preview renderer for a specific field.
	 * @throws InvalidArgumentException
	 * @since 1.9.1
	 */
	public function create_preview_renderer( $field, $args = array() ) {

		if( ! $field instanceof WPCF_Field_Instance_Abstract ) {
			throw new InvalidArgumentException( 'Not a field instance.' );
		}

		if( ! is_array( $args ) ) {
			throw new InvalidArgumentException( 'Not an array.' );
		}

		$field_type = $field->get_field_type();
		switch( $field_type->get_slug() ) {

			case Types_Field_Type_Definition_Factory::GOOGLE_ADDRESS:
				return new WPCF_Field_Renderer_Preview_Address( $field, $args );

			case Types_Field_Type_Definition_Factory::AUDIO:
			case Types_Field_Type_Definition_Factory::FILE:
			case Types_Field_Type_Definition_Factory::VIDEO:
				return new WPCF_Field_Renderer_Preview_File( $field, $args );

			case Types_Field_Type_Definition_Factory::COLORPICKER:
				return new WPCF_Field_Renderer_Preview_Colorpicker( $field, $args );

			case Types_Field_Type_Definition_Factory::DATE:
				return new WPCF_Field_Renderer_Preview_Date( $field, $args );

			case Types_Field_Type_Definition_Factory::EMBED:
			case Types_Field_Type_Definition_Factory::URL:
				return new WPCF_Field_Renderer_Preview_URL( $field, $args );

			case Types_Field_Type_Definition_Factory::CHECKBOX:
				return new WPCF_Field_Renderer_Preview_Checkbox( $field, $args );

			case Types_Field_Type_Definition_Factory::CHECKBOXES:
				return new WPCF_Field_Renderer_Preview_Checkboxes( $field, $args );

			case Types_Field_Type_Definition_Factory::IMAGE:
				return new WPCF_Field_Renderer_Preview_Image( $field, $args );

			case Types_Field_Type_Definition_Factory::RADIO:
			case Types_Field_Type_Definition_Factory::SELECT:
				return new WPCF_Field_Renderer_Preview_Radio( $field, $args );

			case Types_Field_Type_Definition_Factory::SKYPE:
				return new WPCF_Field_Renderer_Preview_Skype( $field, $args );

			default:
				return new WPCF_Field_Renderer_Preview_Textfield( $field, $args );
				break;
		}
	}
}
