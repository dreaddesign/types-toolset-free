<?php

include_once 'class.textfield.php';

class WPToolset_Field_Taxonomyhierarchical extends WPToolset_Field_Textfield {

	protected $children;
	protected $names;
	protected $values = array();

	protected $current_term_ids = array();
	protected $objValues;
	protected $output;

	public function init() {
		global $post;

		$this->objValues = array();
		if ( isset( $post ) ) {

			/**
			 * toolset_filter_taxonomyhierarchical_terms
			 *
			 * Terms with all fields as they are returned from wp_get_post_terms()
			 *
			 * @since 1.8.8
			 *
			 * @param array terms
			 * @param string name field
			 */
			$wp_get_post_terms = wp_get_post_terms( $post->ID, $this->getName(), array( "fields" => "all" ) );
			$terms = apply_filters( 'toolset_filter_taxonomyhierarchical_terms', $wp_get_post_terms, $this->getName() );
			foreach ( $terms as $n => $term ) {
				$this->values[] = $term->slug;
				$this->current_term_ids[] = $term->term_id;
				$this->objValues[ $term->slug ] = $term;
			}
		}

		$all_terms = get_terms( $this->getName(), array( 'hide_empty' => 0, 'fields' => 'all' ) );

		/**
		 * toolset_filter_taxonomyhierarchical_values
		 *
		 * array of current values of taxonomy hierarichical field
		 *
		 * @since 1.8.8
		 *
		 * @param array values
		 * @param string name field
		 */
		$this->current_term_ids = apply_filters( 'toolset_filter_taxonomyhierarchical_values', $this->current_term_ids, $all_terms, $this->getName() );

		/**
		 * toolset_filter_taxonomyhierarchical_all_terms
		 *
		 * Array object of build terms with html and styling of taxonomy hierarichical field
		 *
		 * @since 1.8.8
		 *
		 * @param array buildTerms
		 * @param string name field
		 */
		$all = apply_filters( 'toolset_filter_taxonomyhierarchical_all_terms', $this->buildTerms( $all_terms ), $this->getName() );

		$children = array();
		$names = array();
		foreach ( $all as $term ) {
			$names[ $term['term_id'] ] = $term['name'];
			if ( ! isset( $children[ $term['parent'] ] ) || ! is_array( $children[ $term['parent'] ] ) ) {
				$children[ $term['parent'] ] = array();
			}
			$children[ $term['parent'] ][] = $term['term_id'];
		}

		/**
		 * toolset_filter_taxonomyhierarchical_children
		 *
		 * Array object of children elements of taxonomy hierarichical field
		 *
		 * @since 1.8.8
		 *
		 * @param array children term
		 * @param string name field
		 */
		$this->children = apply_filters( 'toolset_filter_taxonomyhierarchical_children', $children, $this->getName() );

		/**
		 * toolset_filter_taxonomyhierarchical_names
		 *
		 * Array object of elements name of taxonomy hierarichical field
		 *
		 * @since 1.8.8
		 *
		 * @param array names
		 * @param string name field
		 */
		$this->names = apply_filters( 'toolset_filter_taxonomyhierarchical_names', $names, $this->getName() );
	}

	public function enqueueScripts() {

	}

	public function enqueueStyles() {

	}

	public function metaform() {
		$use_bootstrap = array_key_exists( 'use_bootstrap', $this->_data ) && $this->_data['use_bootstrap'];
		$attributes = $this->getAttr();
		$this->output = ( isset( $attributes['output'] ) ) ? $attributes['output'] : "";

		$shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";

		$taxonomy_name = $this->getName();
		$res = '';
		$metaform = array();
		$build_what = '';

		if ( array_key_exists( 'display', $this->_data ) && 'select' == $this->_data['display'] ) {
			$metaform = $this->buildSelect();
			$build_what = 'select';
		} else {
			if ( $this->output == 'bootstrap' ) {
				$res = $this->buildBootstrapCheckboxes( 0, $this->children, $this->names, $metaform );
			} else {
				$res = $this->buildCheckboxes( 0, $this->children, $this->names, $metaform );
			}
			$this->set_metaform( $res );
			$build_what = 'checkboxes';
		}

		/**
		 * toolset_button_add_new_text
		 *
		 * Toolset button add new text
		 *
		 * @since 1.8.8
		 *
		 * @param string attribute add new text
		 */
		$add_new_text_button_value = apply_filters( 'toolset_button_add_new_text', esc_attr( $attributes['add_new_text'] ) );
		/**
		 * toolset_button_cancel_text
		 *
		 * Toolset Cancel button
		 *
		 * @since 1.8.8
		 *
		 * @param string Cancel text
		 */
		$cancel_button_value = apply_filters( 'toolset_button_cancel_text', esc_attr( __( 'Cancel', 'wpv-views' ) ) );
		/**
		 * toolset_button_add_text
		 *
		 * Toolset Cancel button
		 *
		 * @since 1.8.8
		 *
		 * @param string $add_text
		 */
		$add_text_button_value = apply_filters( 'toolset_button_add_text', esc_attr( $attributes['add_text'] ) );

		if ( $this->output == 'bootstrap' ) {
			/**
			 * Input Text/Selectbox Parent container
			 */
			$open_container = '<div style="display:none" class="form-group wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new-container js-wpt-hierarchical-taxonomy-add-new-' . $taxonomy_name . '" data-taxonomy="' . $taxonomy_name . '">';
			$close_container = '</div>';

			/**
			 * The textfield input
			 */
			$metaform[] = array(
				'#type' => 'textfield',
				'#title' => '',
				'#description' => '',
				'#name' => "new_tax_text_" . $taxonomy_name,
				'#value' => '',
				'#attributes' => array(
					'data-taxonomy' => $taxonomy_name,
					'data-taxtype' => 'hierarchical',
					'class' => "form-control wpt-new-taxonomy-title js-wpt-new-taxonomy-title {$shortcode_class}",
				),
				'#validate' => $this->getValidationData(),
				'#before' => $open_container,
			);

			/**
			 * The select for parent
			 */
			$metaform[] = array(
				'#type' => 'select',
				'#title' => '',
				'#options' => array(
					array(
						'#title' => $attributes['parent_text'],
						'#value' => -1,
					),
				),
				'#default_value' => 0,
				'#description' => '',
				'#name' => "new_tax_select_" . $taxonomy_name,
				'#attributes' => array(
					'data-parent-text' => $attributes['parent_text'],
					'data-taxonomy' => $taxonomy_name,
					'class' => "form-control js-taxonomy-parent wpt-taxonomy-parent {$shortcode_class}",
				),
				'#validate' => $this->getValidationData(),
			);

			$bootstrap_class = "btn btn-default";

			/**
			 * The add button
			 */
			$metaform[] = array(
				'#type' => 'button',
				'#title' => '',
				'#description' => '',
				'#name' => "new_tax_button_{$taxonomy_name}",
				'#value' => $add_text_button_value,
				'#attributes' => array(
					'data-taxonomy' => $taxonomy_name,
					'data-build_what' => $build_what,
					'class' => "wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new {$bootstrap_class}",
					'data-output' => $this->output,
				),
				'#validate' => $this->getValidationData(),
				'#after' => $close_container,
			);

			$class = 'wpt-hierarchical-taxonomy-add-new-show-hide js-wpt-hierarchical-taxonomy-add-new-show-hide dashicons-before dashicons-plus-alt';

			$bootstrap_button = "<a
			style='display:none;'
            data-taxonomy='{$taxonomy_name}'
            data-after-selector='js-wpt-hierarchical-taxonomy-add-new-{$taxonomy_name}' 
            data-open='" . $add_new_text_button_value . "' 
            data-close='" . $cancel_button_value . "' 
            data-output='" . $this->output . "'     
            class = '{$class}'  
            role = 'button' 
            name = 'btn_{$taxonomy_name}' 
            >{$add_new_text_button_value}</a>";

			$metaform[] = array(
				'#type' => 'markup',
				'#markup' => $bootstrap_button,
			);

		} else {
			$metaform[] = array(
				'#type' => 'button',
				'#title' => '',
				'#description' => '',
				'#name' => "btn_{$taxonomy_name}",
				'#value' => $add_new_text_button_value,
				'#attributes' => array(
					'style' => 'display:none;',
					'data-taxonomy' => $taxonomy_name,
					'data-build_what' => $build_what,
					'data-after-selector' => 'js-wpt-hierarchical-taxonomy-add-new-' . $taxonomy_name,
					'data-open' => $add_new_text_button_value,
					'data-close' => $cancel_button_value, // TODO adjust the button value depending on open/close action
					'class' => $use_bootstrap ? 'btn btn-default wpt-hierarchical-taxonomy-add-new-show-hide js-wpt-hierarchical-taxonomy-add-new-show-hide' : 'wpt-hierarchical-taxonomy-add-new-show-hide js-wpt-hierarchical-taxonomy-add-new-show-hide',
				),
				'#validate' => $this->getValidationData(),
			);

			// Input for new taxonomy

			if ( $use_bootstrap ) {
				$container = '<div style="display:none" class="form-group wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new-container js-wpt-hierarchical-taxonomy-add-new-' . $taxonomy_name . '" data-taxonomy="' . $taxonomy_name . '">';
			} else {
				$container = '<div style="display:none" class="wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new-container js-wpt-hierarchical-taxonomy-add-new-' . $taxonomy_name . '" data-taxonomy="' . $taxonomy_name . '">';
			}

			/**
			 * The textfield input
			 */
			$metaform[] = array(
				'#type' => 'textfield',
				'#title' => '',
				'#description' => '',
				'#name' => "new_tax_text_{$taxonomy_name}",
				'#value' => '',
				'#attributes' => array(
					'data-taxonomy' => $taxonomy_name,
					'data-taxtype' => 'hierarchical',
					'class' => $use_bootstrap ? 'inline wpt-new-taxonomy-title js-wpt-new-taxonomy-title' : 'wpt-new-taxonomy-title js-wpt-new-taxonomy-title',
				),
				'#validate' => $this->getValidationData(),
				'#before' => $container,
			);

			/**
			 * The select for parent
			 */
			$metaform[] = array(
				'#type' => 'select',
				'#title' => '',
				'#options' => array(
					array(
						'#title' => $attributes['parent_text'],
						'#value' => -1,
					),
				),
				'#default_value' => 0,
				'#description' => '',
				'#name' => "new_tax_select_{$taxonomy_name}",
				'#attributes' => array(
					'data-parent-text' => $attributes['parent_text'],
					'data-taxonomy' => $taxonomy_name,
					'class' => 'js-taxonomy-parent wpt-taxonomy-parent',
				),
				'#validate' => $this->getValidationData(),
			);

			/**
			 * The add button
			 */
			$metaform[] = array(
				'#type' => 'button',
				'#title' => '',
				'#description' => '',
				'#name' => "new_tax_button_" . $taxonomy_name,
				'#value' => $add_text_button_value,
				'#attributes' => array(
					'data-taxonomy' => $taxonomy_name,
					'data-build_what' => $build_what,
					'class' => $use_bootstrap ? 'btn btn-default wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new' : 'wpt-hierarchical-taxonomy-add-new js-wpt-hierarchical-taxonomy-add-new',
				),
				'#validate' => $this->getValidationData(),
				'#after' => '</div>',
			);
		}

		/**
		 * toolset_filter_taxonomyhierarchical_metaform
		 *
		 * Toolset Cancel button
		 *
		 * @since 1.8.8
		 *
		 * @param array metaform
		 * @param string name_field
		 */
		return apply_filters( 'toolset_filter_taxonomyhierarchical_metaform', $metaform, $this->getName() );
	}

	private function buildTerms( $obj_terms ) {
		$tax_terms = array();
		foreach ( $obj_terms as $term ) {
			$tax_terms[] = array(
				'name' => $term->name,
				'count' => $term->count,
				'parent' => $term->parent,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'term_id' => $term->term_id,
			);
		}

		return $tax_terms;
	}

	private function buildSelect() {
		$attributes = $this->getAttr();

		$multiple = ! isset( $attributes['single_select'] ) || ! $attributes['single_select'];

		$curr_options = $this->getOptions();
		$values = $this->current_term_ids;
		$options = array();
		if ( $curr_options ) {
			foreach ( $curr_options as $name => $data ) {
				$option = array(
					'#value' => $name,
					'#title' => $data['value'],
					'#attributes' => array( 'data-parent' => $data['parent'] ),
				);
				if ( $multiple && in_array( $name, $values ) ) {
					$option['#attributes']['selected'] = '';
				}

				$options[] = $option;
			}
		}

		/**
		 * default_value
		 */
		$default_value = null;
		if ( count( $this->current_term_ids ) ) {
			$default_value = $this->current_term_ids[0];
		}
		/**
		 * form settings
		 */
		$form = array();
		$select = array(
			'#type' => 'select',
			'#title' => $this->getTitle(),
			'#description' => $this->getDescription(),
			'#name' => $this->getName() . '[]',
			'#options' => $options,
			'#default_value' => isset( $data['default_value'] ) && ! empty( $data['default_value'] ) ? $data['default_value'] : $default_value,
			'#validate' => $this->getValidationData(),
			'#class' => 'form-inline',
			'#repetitive' => $this->isRepetitive(),
		);

		if ( $multiple ) {
			$select['#attributes'] = array( 'multiple' => 'multiple' );
		}

		if ( count( $options ) == 0 ) {
			if ( isset( $select['#attributes'] ) ) {
				$select['#attributes']['style'] = 'display:none';
			} else {
				$select['#attributes'] = array( 'style' => 'display:none' );
			}
		}
		$form[] = $select;

		return $form;
	}

	private function getOptions( $index = 0, $level = 0, $parent = -1 ) {
		if ( ! isset( $this->children[ $index ] ) || empty( $this->children[ $index ] ) ) {
			return;
		}
		$options = array();

		foreach ( $this->children[ $index ] as $one ) {
			$options[ $one ] = array(
				'value' => sprintf( '%s%s', str_repeat( '&nbsp;', 2 * $level ), $this->names[ $one ] ),
				'parent' => $parent,
			);
			if ( isset( $this->children[ $one ] ) && count( $this->children[ $one ] ) ) {
				foreach ( $this->getOptions( $one, $level + 1, $one ) as $id => $data ) {
					$options[ $id ] = $data;
				}
			}
		}

		return $options;
	}

	/**
	 * function that created the checkboxes tree
	 * $parent is the previous term_id and -1 is the first one that does not have any parent
	 *
	 * @param $index
	 * @param $children
	 * @param $names
	 * @param $metaform
	 * @param int $level
	 * @param int $parent
	 *
	 * @return array
	 */
	private function buildCheckboxes( $index, &$children, &$names, &$metaform, $level = 0, $parent = -1 ) {
		if ( isset( $children[ $index ] ) ) {
			$level_count = count( $children[ $index ] );
			foreach ( $children[ $index ] as $term_key => $term_id ) {
				$name = $names[ $term_id ];

				$is_checked_by_default = false;
				if ( isset( $this->current_term_ids ) && is_array( $this->current_term_ids ) && ! empty( $this->current_term_ids ) ) {
					$is_checked_by_default = in_array( $term_id, $this->current_term_ids );
				} elseif ( is_array( $this->getValue() ) ) {
					$is_checked_by_default = in_array( $term_id, $this->getValue() );
				}
				$classes = array();
				$classes[] = 'tax-' . sanitize_title( $names[ $term_id ] );
				$classes[] = 'tax-' . $this->_data['name'] . '-' . $term_id;
				/**
				 * filter: cred_checkboxes_class
				 *
				 * @param array $classes current array of classes
				 *
				 * @parem array $option current option
				 *
				 * @param string field type
				 *
				 * @return array
				 */
				$classes = apply_filters( 'cred_item_li_class', $classes, array(
					'id' => $term_id,
					'name' => $name,
				), 'taxonomyhierarchical' );

				$item = array(
					'#type' => 'checkbox',
					'#title' => $names[ $term_id ],
					'#description' => '',
					'#name' => $this->getName() . "[]",
					'#value' => $term_id,
					'#default_value' => $is_checked_by_default,
					'#validate' => $this->getValidationData(),
					'#before' => sprintf( '<li class="%s">', implode( ' ', $classes ) ),
					'#after' => '</li>',
					'#attributes' => array(
						'data-parent' => $parent,
						'data-value' => $names[ $term_id ],
					),
					'#pattern' => '<BEFORE><PREFIX><ELEMENT><LABEL><ERROR><SUFFIX><DESCRIPTION><AFTER>',
				);

				if ( $term_key == 0 ) {
					if ( $level > 0 ) {
						$item['#before'] = '<li class="tax-children-of-' . $parent . '"><ul class="wpt-form-set-children wpt-form-set-children-level-' . $level . '" data-level="' . $level . '">' . $item['#before'];
					} else {
						$item['#before'] = '<ul class="wpt-form-set wpt-form-set-checkboxes wpt-form-set-checkboxes-' . esc_attr( $this->getName() ) . '" data-level="0">' . $item['#before'];
					}
				}
				if ( $term_key == ( $level_count - 1 ) ) {
					$item['#after'] = '</li>';
				}

				$metaform[] = $item;

				if ( isset( $children[ $term_id ] ) ) {
					$metaform = $this->buildCheckboxes( $term_id, $children, $names, $metaform, $level + 1, $term_id );
				}
			}
		}

		if ( count( $metaform ) ) {
			if ( $level == 0 ) {
				$metaform[ count( $metaform ) - 1 ]['#after'] .= '</ul>';
			} else {
				$metaform[ count( $metaform ) - 1 ]['#after'] .= '</ul></li>';
			}
		}

		return $metaform;
	}

	/**
	 * function that created the checkboxes tree using bootstrap structure
	 * $parent is the previous term_id and -1 is the first one that does not have any parent
	 *
	 * @param $index
	 * @param $children
	 * @param $names
	 * @param $metaform
	 * @param int $level
	 * @param int $parent
	 *
	 * @return array
	 */
	private function buildBootstrapCheckboxes( $index, &$children, &$names, &$metaform, $level = 0, $parent = -1 ) {
		if ( isset( $children[ $index ] ) ) {
			$level_count = count( $children[ $index ] );
			foreach ( $children[ $index ] as $term_key => $term_id ) {
				$name = $names[ $term_id ];

				$is_checked_by_default = false;
				if ( isset( $this->current_term_ids ) && is_array( $this->current_term_ids ) && ! empty( $this->current_term_ids ) ) {
					$is_checked_by_default = in_array( $term_id, $this->current_term_ids );
				} elseif ( is_array( $this->getValue() ) ) {
					$is_checked_by_default = in_array( $term_id, $this->getValue() );
				}
				$classes = array();
				$classes[] = 'checkbox';
				$classes[] = 'tax-' . sanitize_title( $names[ $term_id ] );
				$classes[] = 'tax-' . $this->_data['name'] . '-' . $term_id;
				/**
				 * filter: cred_checkboxes_class
				 *
				 * @param array $classes current array of classes
				 *
				 * @parem array $option current option
				 *
				 * @param string field type
				 *
				 * @return array
				 */
				$classes = apply_filters( 'cred_item_li_class', $classes, array(
					'id' => $term_id,
					'name' => $name,
				), 'taxonomyhierarchical' );

				$item = array(
					'#type' => 'checkbox',
					'#title' => $names[ $term_id ],
					'#description' => '',
					'#name' => $this->getName() . "[]",
					'#value' => $term_id,
					'#default_value' => $is_checked_by_default,
					'#validate' => $this->getValidationData(),
					'#before' => sprintf( '<li class="%s"><label class="wpt-form-label wpt-form-checkbox-label">', implode( ' ', $classes ) ),
					'#after' => $names[ $term_id ] . '</label></li>',
					'#attributes' => array(
						'data-parent' => $parent,
						'data-value' => $names[ $term_id ],
					),
					'#pattern' => '<BEFORE><PREFIX><ELEMENT><ERROR><SUFFIX><DESCRIPTION><AFTER>',
				);

				if ( $term_key == 0 ) {
					if ( $level > 0 ) {
						$item['#before'] = '<li class="tax-children-of-' . $parent . '"><ul class="wpt-form-set-children wpt-form-set-children-level-' . $level . '" data-level="' . $level . '">' . $item['#before'];
					} else {
						$item['#before'] = '<ul class="wpt-form-set wpt-form-set-checkboxes wpt-form-set-checkboxes-' . esc_attr( $this->getName() ) . '" data-level="0">' . $item['#before'];
					}
				}
				if ( $term_key == ( $level_count - 1 ) ) {
					$item['#after'] = $names[ $term_id ] . '</label></li>';
				}

				$metaform[] = $item;

				if ( isset( $children[ $term_id ] ) ) {
					$metaform = $this->buildBootstrapCheckboxes( $term_id, $children, $names, $metaform, $level + 1, $term_id );
				}
			}
		}

		if ( count( $metaform ) ) {
			if ( $level == 0 ) {
				$metaform[ count( $metaform ) - 1 ]['#after'] .= '</ul>';
			} else {
				$metaform[ count( $metaform ) - 1 ]['#after'] .= '</ul></li>';
			}
		}

		return $metaform;
	}

}