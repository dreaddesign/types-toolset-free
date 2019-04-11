<?php
/**
 *
 *
 */
require_once 'class.field_factory.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Colorpicker extends FieldFactory
{
    public function init()
    {
        if ( !Toolset_Utils::is_real_admin() ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script(
                'iris',
                admin_url( 'js/iris.min.js' ),
                array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
                false,
                1
            );
			// colorpicker.js uses iris, not wp-color-picker. Check whether this is needed.
            wp_enqueue_script(
                'wp-color-picker',
                admin_url( 'js/color-picker.min.js' ),
                array( 'iris' ),
                false,
                1
            );
        }
        wp_register_script(
            'wptoolset-field-colorpicker',
            WPTOOLSET_FORMS_RELPATH . '/js/colorpicker.js',
            array('iris'),
            WPTOOLSET_FORMS_VERSION,
            true
        );
        wp_enqueue_script( 'wptoolset-field-colorpicker' );
        $this->set_placeholder_as_attribute();
    }

    static public function registerScripts()
    {
    }

    public function enqueueScripts()
    {

    }

    public function addTypeValidation($validation) {
        $validation['hexadecimal'] = array(
            'args' => array(
                'hexadecimal'
            ),
            'message' => __('Please use a valid hexadecimal value.', 'wpv-views' ),
        );
        return $validation;
    }

    public function metaform()
    {
		$field_data = $this->getData();
        $validation = $this->getValidationData();
        //CRED Colorpicker Generic fields has now the hexadecimal validation rule
	    //so avoiding the force of validation for cred_generic field
	    //TODO: once will be present this feature to Types we can remove this condition completely
	    if ( ! isset( $field_data['attribute']['cred_generic'] ) ) {
	    	//We are forcing the hexadecimal validation to this colorpicker field
		    //because in Types is still not present in the field settings popup the input of the validation field
		    $validation = $this->addTypeValidation( $validation );
		    $this->setValidationData( $validation );
	    }

        $attributes = $this->getAttr();
        $shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";
        $attributes['class'] = "js-wpt-colorpicker {$shortcode_class}"; // What is this js-wpt-cond-trigger classname for?

        $form = array();
        $form['name'] = array(
            '#type'			=> 'textfield',
            '#title'		=> $this->getTitle(),
            '#description'	=> $this->getDescription(),
            '#value'		=> $this->getValue(),
            '#name'			=> $this->getName(),
            '#attributes'	=> $attributes,
            '#validate'		=> $validation,
            '#after'		=> '',
            '#repetitive'	=> $this->isRepetitive(),
			'wpml_action'	=> $this->getWPMLAction(),
        );
        return $form;
    }

    public static function filterValidationValue($value)
    {
        if ( isset( $value['datepicker'] ) ) {
            return $value['datepicker'];
        }
        return $value;
    }
}
