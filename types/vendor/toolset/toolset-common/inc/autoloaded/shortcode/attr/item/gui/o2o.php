<?php

/**
 * item attribute GUI selector provider for one-to-one relationships.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Gui_O2o extends Toolset_Shortcode_Attr_Item_Gui_Base {
	
	
	/**
	 * Set options for post selectors on O2O relationships.
	 *
	 * @since m2m
	 */
	protected function set_options() {
		if ( 
			null != $this->current_post_object 
			&& in_array( $this->current_post_object->name, $this->parent_types ) 
		) {
			$this->set_one_to_one_current_parent();
			return;
		}
		
		if ( 
			null != $this->current_post_object 
			&& in_array( $this->current_post_object->name, $this->child_types ) 
		) {
			$this->set_one_to_one_current_child();
			return;
		}
		
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$this->options[] = $option_parent->get_option();
		
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$this->options[] = $option_child->get_option();
		return;
	}
	
	
	/**
	 * Set the post selector options when editing a parent post type.
	 *
	 * In this case, you can display the child post data.
	 *
	 * @since m2m
	 */
	private function set_one_to_one_current_parent() {
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$this->options[] = $option_child->get_option();
	}
	
	
	/**
	 * Set the post selector options when editing a child post type.
	 *
	 * In this case, you can display the parent post data.
	 *
	 * @since m2m
	 */
	private function set_one_to_one_current_child() {
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$this->options[] = $option_parent->get_option();
	}
	
}