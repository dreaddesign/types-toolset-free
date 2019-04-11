<?php

/**
 * Factory for the item attribute GUI selector, based on the relationship cardinality.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Gui_Factory {
	
	
	/**
	 * @var Toolset_Relationship_Definition
	 */
	private $relationship_definition;
	
	
	/**
	 * @var null|WP_Post_Type
	 */
	private $current_post_object;
	
	
	/**
	 * @var string
	 */
	private $option_name = '';
	
	
	/**
	 * @var string
	 */
	private $cardinality;
	
	
	/**
	 * @var Toolset_Post_Type_Repository
	 */
	private $post_type_repository;
	
	
	/**
	 * @var Toolset_Shortcode_Attr_Item_Gui_Base
	 */
	private $provider;
	
	
	function __construct( Toolset_Relationship_Definition $relationship_definition, $current_post_object, $option_name ) {
		$this->relationship_definition = $relationship_definition;
		$this->current_post_object = $current_post_object;
		$this->option_name = $option_name;
		
		$this->cardinality = $relationship_definition->get_cardinality()->get_type();
		
		$this->post_type_repository = Toolset_Post_Type_Repository::get_instance();
		
		$this->create();
	}
	
	
	/**
	 * Fill options per relationship cardinality.
	 *
	 * @since m2m
	 */
	private function create() {
		switch( $this->cardinality ) {
			case 'many-to-many':
				$this->provider = new Toolset_Shortcode_Attr_Item_Gui_M2m(
					$this->relationship_definition,
					$this->current_post_object,
					$this->option_name,
					$this->post_type_repository
				);
				break;
			case 'one-to-many':
				$this->provider = new Toolset_Shortcode_Attr_Item_Gui_O2m(
					$this->relationship_definition,
					$this->current_post_object,
					$this->option_name,
					$this->post_type_repository
				);
				break;
			case 'one-to-one':
				$this->provider = new Toolset_Shortcode_Attr_Item_Gui_O2o(
					$this->relationship_definition,
					$this->current_post_object,
					$this->option_name,
					$this->post_type_repository
				);
				break;
		}
	}
	
	
	/**
	 * Get the options registered by the right provider based on the current relationship cardinality.
	 *
	 * @return string[]
	 *
	 * @since m2m
	 */
	public function get_options() {
		return $this->provider->get_options();
	}
	
}