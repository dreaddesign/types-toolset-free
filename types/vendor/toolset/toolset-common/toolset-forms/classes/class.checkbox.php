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
class WPToolset_Field_Checkbox extends FieldFactory {

    public function metaform() {
        global $post;
        $value = $this->getValue();
        $data = $this->getData();
        $checked = null;
        $is_cred_generic_field = isset( $data['options']['cred_generic'] ) && $data['options']['cred_generic'] == 1;

        /**
         * autocheck for new posts
         */
        if ( isset( $post ) && 'auto-draft' == $post->post_status && array_key_exists( 'checked', $data ) && $data['checked'] ) {
            $checked = true;
        }
        /**
         * is checked?
         */
        if ( isset( $data['options'] ) && array_key_exists( 'checked', $data['options'] ) ) {
            $checked = $data['options']['checked'];
        }
        /**
         * if is a default value, there value is 1 or default_value
         */
        if (
                array_key_exists( 'default_value', $data ) && ( 'y' === $value || $value === $data['default_value']) && !$is_cred_generic_field
        ) {
            $checked = true;
        }

        // Comment out broken code. This tries to set the previous state after validation fails
        //if (!$checked&&$this->getValue()==1) {
        //    $checked=true;
        //}
        $default_value = array_key_exists( 'default_value', $data ) ? $data['default_value'] : "";
        if ( $is_cred_generic_field && !$checked ) {
            $default_value = "";
        }

        $attributes = $this->getAttr();
        $output = (isset( $attributes['output'] )) ? $attributes['output'] : "";        

        $form = array(
            '#type' => 'checkbox',
            '#value' => $value,
            '#default_value' => $default_value,
            '#name' => $this->getName(),
            '#description' => $this->getDescription(),
            '#title' => $this->getTitle(),
            '#validate' => $this->getValidationData(),
            '#after' => '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />',
            '#checked' => $checked,
            '#repetitive' => $this->isRepetitive(),
            /*
             * class attribute was missed
             */
            '#attributes' => $attributes,
            'wpml_action' => $this->getWPMLAction(),
        );

        if ( $output == 'bootstrap' ) {
            $form = array_merge($form, array(
                '#before' => sprintf( '<ul class="wpt-form-set wpt-form-set-checkboxes wpt-form-set-checkboxes-wpcf-checkboxes-field"><li class="wpt-form-item wpt-form-item-checkbox checkbox"><label class="wpt-form-label wpt-form-checkbox-label" for="%s">', $this->getName() ),
                '#after' => $this->getTitle() . '</label></li></ul><input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />',
                '#pattern' => '<BEFORE><PREFIX><ELEMENT><ERROR><SUFFIX><DESCRIPTION><AFTER>'
            ));
        }

        return array($form);
    }

}
