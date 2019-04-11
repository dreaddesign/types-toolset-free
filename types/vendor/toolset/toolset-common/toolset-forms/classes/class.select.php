<?php

require_once 'class.field_factory.php';

/**
 * Class WPToolset_Field_Select
 */
class WPToolset_Field_Select extends FieldFactory {

	/**
	 * @return array
	 */
	public function metaform() {
		$value = $this->getValue();
		$data = $this->getData();
		$attributes = $this->getAttr();
		$form = $options = array();
		$is_multiselect = array_key_exists( 'multiple', $attributes ) && 'multiple' == $attributes['multiple'];

		if ( ! $is_multiselect ) {
			if ( ! isset( $data['default_value'] ) ) {

				$option_title = __( '--- not set ---', 'wpv-views' );

				// no_parent_text even if deprecated since 1.9.1
				// Mantaining backward compatilibility
				if ( isset( $attributes['no_parent_text'] ) ) {
					$option_title = $attributes['no_parent_text'];
				} else {
					//If cred_field shortcode attribute select_text is present use it
					if ( isset( $attributes['select_text'] ) ) {
						$option_title = $attributes['select_text'];
					}
				}

				$not_set_value = array(
					'#value' => '',
					'#title' => $option_title,
				);

				/**
				 * wpt_field_not_set_option
				 *
				 * Filter used to change empty option element $not_set_value array
				 *
				 * @since 1.9.1
				 *
				 * @param array     selectbox attributes
				 * @param array    all select field data elements
				 */
				$not_set_value = apply_filters( 'wpt_field_not_set_option', $not_set_value, $attributes, $data );

				$options[] = $not_set_value;
			}

			// default_value
			if ( ! empty( $value ) || $value == '0' ) {
				$data['default_value'] = $value;
			}
		}

		if ( isset( $data['options'] ) ) {
			if ( ! Toolset_Utils::is_real_admin() ) {
				$new_options = array();
				foreach ( $data['options'] as $key => $option ) {
					if ( isset( $option['types-value'] ) ) {
						$tmp = $option['value'];
						$option['value'] = $option['types-value'];
						$option['types-value'] = $tmp;
					} else {
						$option['types-value'] = $option['value'];
					}
					$new_options[ $key ] = $option;
					unset( $tmp );
				}
				$data['options'] = $new_options;
			}

			foreach ( $data['options'] as $key => $option ) {
				$one_option_data = array(
					'#value' => $option['value'],
					'#title' => stripslashes( $option['title'] ),
				);

				/**
				 * add default value if needed
				 * issue: frontend, multiforms CRED
				 */
				//                if (array_key_exists('types-value', $option)) {
				//                    $one_option_data['#types-value'] = $option['types-value'];
				//                }

				$options[] = $one_option_data;
			}
		}

		/**
		 * for user fields we reset title and description to avoid double
		 * display
		 */
		$title = $this->getTitle();
		if ( empty( $title ) ) {
			$title = $this->getTitle( true );
		}
		$options = apply_filters( 'wpt_field_options', $options, $title, 'select' );
		$default_value = isset( $data['default_value'] ) ? $data['default_value'] : null;

		//Fix https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/189219391/comments
		if ( $is_multiselect && ! empty( $default_value ) ) {
			$default_value = new RecursiveIteratorIterator( new RecursiveArrayIterator( $default_value ) );
			$default_value = iterator_to_array( $default_value, false );
		}
		//##############################################################################################

		/**
		 * metaform
		 */
		$form[] = array(
			'#type' => 'select',
			'#title' => $this->getTitle(),
			'#description' => $this->getDescription(),
			'#name' => $this->getName(),
			'#options' => $options,
			'#default_value' => $default_value,
			'#multiple' => $is_multiselect,
			'#validate' => $this->getValidationData(),
			'#class' => 'form-inline',
			'#repetitive' => $this->isRepetitive(),
			/*
			 * class attribute was missed
			 */
			'#attributes' => $attributes,
			'wpml_action' => $this->getWPMLAction(),
		);

		return $form;
	}

}
