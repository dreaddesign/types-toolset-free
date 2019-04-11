<?php

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
require_once 'class.field_factory.php';

class WPToolset_Field_Checkboxes extends FieldFactory {

	public function metaform() {
		global $post;
		$value = $this->getValue();
		$data = $this->getData();
		$name = $this->getName();
		$attributes = $this->getAttr();
		$output = ( isset( $attributes['output'] ) ) ? $attributes['output'] : "";

		$form = array();
		$_options = array();
		if ( isset( $data['options'] ) ) {
			foreach ( $data['options'] as $option_key => $option ) {

				$checked = isset( $option['checked'] ) ? $option['checked'] : ! empty( $value[ $option_key ] );

				if ( isset( $post ) && 'auto-draft' == $post->post_status && array_key_exists( 'checked', $option ) && $option['checked'] ) {
					$checked = true;
				}

				$_options[ $option_key ] = array(
					'#value' => $option['value'],
					'#title' => $option['title'],
					'#type' => 'checkbox',
					'#default_value' => $checked,
					'#checked' => $checked,
					'#name' => $option['name'] . "[]",
				);

				if ( isset( $option['data-value'] ) ) {
					$_options[ $option_key ]['#attributes'] = array( 'data-value' => $option['data-value'] );
				}

				if ( ! Toolset_Utils::is_real_admin() ) {
					$classes = array(
						'wpt-form-item',
						'wpt-form-item-checkbox',
						'checkbox-' . sanitize_title( $option['title'] ),
					);

					if ( $output == 'bootstrap' ) {
						$classes[] = 'checkbox';
					}

					/**
					 * filter: cred_checkboxes_class
					 *
					 * @param array $clases current array of classes
					 *
					 * @parem array $option current option
					 *
					 * @param string field type
					 *
					 * @return array
					 */
					$classes = apply_filters( 'cred_item_li_class', $classes, $option, 'checkboxes' );
					if ( $output == 'bootstrap' ) {
						$_options[ $option_key ]['#before'] = sprintf(
							'<li class="%s"><label class="wpt-form-label wpt-form-checkbox-label">', implode( ' ', $classes )
						);
						$_options[ $option_key ]['#after'] = stripslashes( $option['title'] ) . '</label></li>';
						$_options[ $option_key ]['#pattern'] = '<BEFORE><PREFIX><ELEMENT><ERROR><SUFFIX><DESCRIPTION><AFTER>';
					} else {
						$_options[ $option_key ]['#before'] = sprintf(
							'<li class="%s">', implode( ' ', $classes )
						);
						$_options[ $option_key ]['#after'] = '</li>';
						$_options[ $option_key ]['#pattern'] = '<BEFORE><PREFIX><ELEMENT><LABEL><ERROR><SUFFIX><DESCRIPTION><AFTER>';
					}
				}
			}
		}
		$metaform = array(
			'#type' => 'checkboxes',
			'#options' => $_options,
			'#description' => $this->getDescription(),
			'wpml_action' => $this->getWPMLAction(),
		);
		if ( Toolset_Utils::is_real_admin() ) {
			$metaform['#title'] = $this->getTitle();
			$metaform['#after'] = '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />';
		} else {
			$metaform['#before'] = '<ul class="wpt-form-set wpt-form-set-checkboxes wpt-form-set-checkboxes-' . $name . '">';
			$metaform['#after'] = '</ul>';
		}

		return array( $metaform );
	}

}