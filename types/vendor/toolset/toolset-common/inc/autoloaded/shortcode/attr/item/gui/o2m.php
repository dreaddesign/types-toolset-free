<?php

/**
 * item attribute GUI selector provider for one-to-many relationships.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Gui_O2m extends Toolset_Shortcode_Attr_Item_Gui_Base {
	
	
	/**
	 * Set options for post selectors on O2M relationships.
	 *
	 * @since m2m
	 */
	protected function set_options() {
		$origin = $this->relationship_definition->get_origin()->get_origin_keyword();
		
		if ( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD === $origin ) {
			$this->set_parent_reference_option();
		} else {
			$this->set_parent_option();
			$this->set_child_option();
		}
	}
	
	protected function set_parent_reference_option() {
		if ( 
			null === $this->current_post_object 
			|| ! in_array( $this->current_post_object->name, $this->parent_types ) 
		) {
			$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::PARENT,
				$this
			);
			$this->options[] = $option->get_option();
		
		}
	}
	
	protected function set_parent_option() {
		if ( 
			null === $this->current_post_object 
			|| ! in_array( $this->current_post_object->name, $this->parent_types ) 
		) {
			$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::PARENT,
				$this
			);
			
			$this->options[] = $option->get_option();
		
		}
	}
	
	private function set_child_option() {
		$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option->set_property( 'is_disabled', true );
		$option->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (one-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'To display the %1$s that are connected to each %2$s, you will need to create a View.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT, true ) . '</strong>'
			) . '</p><p>' . sprintf(
				__( '%1$sDocumentation %2$s%3$s', 'wpv-views' ),
				'<a href="'
					. $this->get_documentation_link(
						'https://toolset.com/documentation/post-relationships/how-to-display-related-posts-with-toolset/',
						array(
							'utm_source'	=> 'postselector',
							'utm_campaign'	=> 'm2m',
							'utm_medium'	=> 'post-selector-documentation-link',
							'utm_term'		=> 'Documentation'
						),
						'displaying-many-related-items'
					)
					. '" target="_blank">',
				'<i class="fa fa-external-link"></i>',
				'</a>'
			) . '</p>' 
		);
		
		$this->options[] = $option->get_option();
	}
	
}