<?php

require_once 'class.field_factory.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Radios extends FieldFactory {

	public function metaform() {
		$value = $this->getValue();
		$data = $this->getData();
		$name = $this->getName();
		$form = array();
		$options = array();
		$attributes = $this->getAttr();
		$output = ( isset( $attributes['output'] ) ) ? $attributes['output'] : "";

		foreach ( $data['options'] as $option ) {
			$one_option_data = array(
				'#value' => $option['value'],
				'#title' => $option['title'],
				'#validate' => $this->getValidationData(),
			);

            if (!Toolset_Utils::is_real_admin() ) {
                $classes = array(
                    'wpt-form-item',
                    'wpt-form-item-radio',
                    'radio-' . sanitize_title($option['title']),
                );

                if ( $output == 'bootstrap' ) {
					$classes[] = 'radio';
				}

				/** * filter: cred_checkboxes_class
                 ** @param array $clases current array of classes
                 ** @parem array $option current option
                 ** @param string field type
                 *
                 * @return array
                 */
                $classes = apply_filters('cred_item_li_class', $classes, $option, 'radio');
                if ( $output == 'bootstrap' ) {$one_option_data['#before'] = sprintf(
                        '<li class="%s"><label class="wpt-form-label wpt-form-checkbox-label">', implode(' ', $classes)
                );
                $one_option_data['#after'] = $option['title'] . '</label></li>';
                //moved error from element to before prefix
                $one_option_data['#pattern'] = '<BEFORE><ERROR><PREFIX><ELEMENT><SUFFIX><DESCRIPTION><AFTER>';
            }else {
            $one_option_data['#before'] = sprintf(
             '<li class="%s">', implode( ' ', $classes )
             );
             $one_option_data['#after'] = '</li>';
//            moved error from element to before prefix
                $one_option_data['#pattern'] = '<BEFORE><ERROR><PREFIX><ELEMENT><LABEL><SUFFIX><DESCRIPTION><AFTER>';
				}
			}

            /**
             * add to options array
             */
            $options[] = $one_option_data;
        }
        /**
         * for user fields we reset title and description to avoid double 
         * display
         */
        $title = $this->getTitle();
        if (empty($title)) {
            $title = $this->getTitle(true);
        }
        $options = apply_filters('wpt_field_options', $options, $title, 'select');
        /**
         * default_value
         */
        if (!empty($value) || $value == '0') {
            $data['default_value'] = $value;
        }
        /**
         * metaform
         */
        $form_attr = array(
            '#type' => 'radios',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $name,
            '#options' => $options,
            '#default_value' => isset($data['default_value']) ? $data['default_value'] : false,
            '#repetitive' => $this->isRepetitive(),
            '#validate' => $this->getValidationData(),
			'wpml_action' => $this->getWPMLAction(),
			'#after' => '<input type="hidden" name="_wptoolset_radios[' . $this->getId() . ']" value="1" />',
		);

		if ( ! Toolset_Utils::is_real_admin() ) {
			$form_attr['#before'] = '<ul class="wpt-form-set wpt-form-set-radios wpt-form-set-radios-' . $name . '">';
			$form_attr['#after'] = '</ul>';
		}

		$form[] = $form_attr;

		return $form;
	}

}