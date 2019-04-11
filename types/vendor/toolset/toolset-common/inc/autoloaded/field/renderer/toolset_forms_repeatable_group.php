<?php

/**
 * Class Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group
 *
 * This class extends Toolset_Field_Renderer_Toolset_Forms by adding the post_id to the html name attribute of the
 * field input. This way it's easy to store a field for a post wherever the field edit is shown.
 *
 * See:
 * <input name="wpcf[name-of-field]" /> (output of Toolset_Field_Renderer_Toolset_Forms)
 * <input name="wpcf[post-id-the-field-belongs-to][name-of-field] /> (output of Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group)
 *
 * @since 2.3
 */
class Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group extends Toolset_Field_Renderer_Toolset_Forms {

	/**
	 * @var array
	 */
	private $field_config;

	/**
	 * @var int
	 */
	private $group_id;

	/**
	 * Get the legacy field config and modifiy the field names to work with rfg items.
	 *
	 * @param null $group_id
	 *
	 * @return array
	 *
	 * @since 2.6.5
	 */
	public function get_field_config( $group_id = null ) {
		if( $this->field_config === null || ( $group_id !== null && $group_id !== $this->group_id ) ) {
			$this->group_id = $group_id;

			// done by legacy
			$field_config = $this->get_toolset_forms_config();

			if ( $this->hide_field_title ) {
				$field_config['title'] = '';
				$field_config['hide_field_title'] = true;
			}

			// no repetitive fields on rfg
			$field_config['repetitive'] = false;

			// convert field name to types-repeatable-group[item-id][field-slug]
			$field_config['name'] = 'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_config['slug'] . ']';
			if( isset( $field_config['options'] ) ) {
				foreach ( (array) $field_config['options'] as $option_key => $option_value ) {
					if ( isset( $option_value['name'] ) ) {
						$field_config['options'][$option_key]['name'] =
							'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_config['slug'] . '][' . $option_key . ']';
					}
				}
			}

			// check conditionals and rename field names
			if( isset( $field_config['conditional'] ) && isset( $field_config['conditional']['conditions'] ) ) {
				$group_fields = get_post_meta( $group_id, '_wp_types_group_fields', true );
				$group_fields = explode( ',', $group_fields );

				foreach( $field_config['conditional']['conditions']  as $con_key => $con_value ) {
					$con_field_slug = str_replace( 'wpcf-', '', $con_value['id'] );

					// only change the conditions field slug if the field is part of the rfg
					if( in_array( $con_field_slug, $group_fields ) ) {
						$field_config['conditional']['conditions'][ $con_key ][ 'id' ] =
							'types-repeatable-group[' . $this->field->get_object_id() . '][' . $con_field_slug . ']';
					}
				}

				// we also need to renaeme the keys of the values
				// before 'wpcf-field-slug' => 1  /  after: 'types-repeatable-group[item-id][field-slug]' => 1
				if( isset( $field_config['conditional']['values'] ) && is_array( $field_config['conditional']['values'] ) ) {
					foreach( $field_config['conditional']['values'] as $original_field_slug => $field_value ) {
						$field_slug = str_replace( 'wpcf-', '', $original_field_slug );

						// check if field is part of rfg
						if( in_array( $field_slug, $group_fields ) ) {
							$rfg_item_field_slug = 'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_slug . ']';
							$field_config['conditional']['values'][$rfg_item_field_slug] = $field_value;
							unset( $field_config['conditional']['values'][$original_field_slug] );
						}
					}
				}
			}

			$this->field_config = $field_config;
		}

		return $this->field_config;
	}

	/**
	 * Render group
	 *
	 * @param bool $echo
	 * @param null $group_id
	 *
	 * @return mixed|void
	 */
	public function render( $echo = false, $group_id = null ) {

		/**
		 * Use filter to set types-related-content to "true"
		 * This is necessary to make sure that all WYSIWYG fields will have unique ID
		 */
		add_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10, 2 );

		$field_config = $this->get_field_config( $group_id );

		$value_in_intermediate_format = $this->field->get_value();
		$output = wptoolset_form_field( $this->get_form_id(), $field_config, $value_in_intermediate_format );

		if ( $echo ) {
			echo $output;
		}
		remove_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10, 2 );


		return $output;
	}


	/**
	 * In case of wysiwyg fields set types-related-content to true
	 * to make sure that field ID is unique
	 * @param $attributes
	 * @param $field
	 *
	 * @return array
	 */
	public function add_filter_attributes( $attributes, $field ) {
		$field_type = $field->getType();
		if( 'wysiwyg' ==  $field_type ){
			$attributes['types-related-content'] = true;
		}
		return $attributes;
	}
}