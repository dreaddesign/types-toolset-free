<?php

/**
 * Viewmodel for the item attribute GUI selector options.
 *
 * @since m2m
 *
 * @todo Remove the dependency of the Toolset_Shortcode_Attr_Item_Gui_Base $provider: it only passes a name and a label
 * @todo This should include a method to return and/or print the output using a template, and
 *       Toolset_Shortcode_Attr_Item_Gui_Base::$options should be a Toolset_Shortcode_Attr_Item_Gui_Option[]
 */
class Toolset_Shortcode_Attr_Item_Gui_Option {
	
	
	/**
	 * @var string[]
	 */
	private $public_properties = array(
		'is_checked', 'is_disabled', 'pointer_content'
	);
	
	
	/**
	 * @var string
	 */
	private $id;
	
	
	/**
	 * @var string
	 */
	private $value;
	
	
	/**
	 * @var bool
	 */
	private $is_checked = false;
	
	
	/**
	 * @var bool
	 */
	private $is_disabled = false;
	
	
	/**
	 * @var string
	 */
	private $pointer_content = '';
	
	
	/**
	 * @var Toolset_Relationship_Definition
	 */
	private $relationship_definition;
	
	
	/**
	 * @var string
	 */
	private $role;
	
	
	/**
	 * @var string
	 */
	private $origin;
	
	
	/**
	 * @var Toolset_Shortcode_Attr_Item_Gui_Base
	 */
	private $provider;
	
	
	function __construct(
		Toolset_Relationship_Definition $relationship_definition,
		$role,
		Toolset_Shortcode_Attr_Item_Gui_Base $provider
	) {
		$this->relationship_definition = $relationship_definition;
		$this->role = $role;
		$this->provider = $provider;
		
		$this->origin = $this->relationship_definition->get_origin()->get_origin_keyword();
		
		$this->set_data();
	}
	
	
	/**
	 * Set the basic option data: id and value.
	 *
	 * @since m2m
	 */
	private function set_data() {
		$this->id = $this->relationship_definition->get_slug() . '-' . $this->role;
		$this->value = '@' . $this->relationship_definition->get_slug() . '.' . $this->role;
	}
	
	
	/**
	 * Public setter for expected properties.
	 *
	 * @param string $property
	 * @param mixed $value
	 *
	 * @since m2m
	 */
	public function set_property( $property, $value ) {
		if ( ! in_array( $property, $this->public_properties ) ) {
			return;
		}
		$this->{$property} = $value;
	}
	
	
	/**
	 * Get the option name attribute.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_option_name() {
		return $this->provider->get_option_name();
	}
	
	
	/**
	 * Get the option label.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_label() {
		return ( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD === $this->origin ) 
			? sprintf(
				__( '%1$s (from %2$s)', 'wpv-views' ),
				$this->provider->get_post_type_label_by_role( $this->role ),
				$this->relationship_definition->get_display_name()
			)
			: $this->provider->get_post_type_label_by_role( $this->role );
	}
	
	
	/**
	 * Get the option output.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_option() {
		$option = '';
		
		$option .= sprintf( 
			'<label for="toolset-shortcode-gui-item-selector-post-relationship-id-%1$s"%2$s>', 
			esc_attr( $this->id ),
			( $this->is_disabled ? ' class="toolset-option-label-disabled"' : '' )
		);
		$option .= sprintf(
			'<input type="radio" name="%s" id="toolset-shortcode-gui-item-selector-post-relationship-id-%s" value="%s" %s %s />',
			esc_attr( $this->get_option_name() ),
			esc_attr( $this->id ),
			esc_attr( $this->value ),
			checked( $this->is_checked, true, false ),
			disabled( $this->is_disabled, true, false )
		);
		$option .= esc_html( $this->get_label() );
		if ( ! empty( $this->pointer_content ) ) {
			$option .= ' <i class="fa fa-question-circle wp-toolset-pointer-trigger js-wp-toolset-shortcode-pointer-trigger" style="vertical-align:baseline"></i>';
		}
		$option .= '</label>';
		if ( ! empty( $this->pointer_content ) ) {
			$option .= '<div class="js-wp-toolset-shortcode-pointer-content" style="display:none">';
			$option .= $this->pointer_content;
			$option .= '</div>';
		}
		
		return $option;
	}
	
}