<?php

/**
 * item attribute GUI selector provider for many-to-many relationships.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Gui_M2m extends Toolset_Shortcode_Attr_Item_Gui_Base {
	
	
	/**
	 * Set options for post selectors on M2M relationships.
	 *
	 * @since m2m
	 */
	protected function set_options() {
		if ( null === $this->intermediary_type ) {
			$this->set_many_to_many_disabled();
			return;
		}
		
		if ( null === $this->current_post_object ) {
			
			if ( 
				in_array( toolset_getget('get_page'), array( 'views-editor', 'view-archives-editor' ) ) 
				|| (
					is_admin() 
					&& in_array( toolset_getget('page'), array( 'views-editor', 'view-archives-editor' ) ) 
				)
			) {
				$this->set_many_to_many_view_loop();
				return;
			}
			
			if ( 
				in_array( toolset_getget('get_page'), array( 'ct-editor' ) ) 
				|| (
					is_admin() 
					&& in_array( toolset_getget('page'), array( 'ct-editor' ) ) 
				)
			) {
				$this->set_many_to_many_content_template();
				return;
			}
			
			$this->set_many_to_many_disabled();
			return;
			
		}
		
		if ( $this->current_post_object->name === $this->intermediary_type ) {
			$this->set_many_to_many_current_intermediary();
			return;
		}
		
		$this->set_many_to_many_disabled();
		return;
	}
	
	
	/**
	 * Set a disabled option.
	 *
	 * M2M relationships usualy do not allow to display data from related objects,
	 * so a disabled option is rendered in most cases.
	 *
	 * @since m2m
	 */
	private function set_many_to_many_disabled() {
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$option_parent->set_property( 'is_disabled', true );
		$option_parent->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
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
		$this->options[] = $option_parent->get_option();
		
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option_child->set_property( 'is_disabled', true );
		$option_child->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'To display the %1$s that are connected to each %2$s, you will need to create a View.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD, true ) . '</strong>'
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
		$this->options[] = $option_child->get_option();
		
		if ( null != $this->intermediary_type ) {
			
			$option_intermediary = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::INTERMEDIARY,
				$this
			);
			$option_intermediary->set_property( 'is_disabled', true );
			$option_intermediary->set_property( 
				'pointer_content', 
				'<h3>' . sprintf(
					__( '%1$s (many-to-many relationship)', 'wpv-views' ),
					$this->relationship_definition->get_display_name()
				) . '</h3><p>' . sprintf(
					__( '%1$s connects with %2$s through %3$s.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
				) . '</p><p>' . sprintf(
					__( 'To display %1$s you need to use a View that will list %2$s or %3$s and include a filter by the %4$s relationship.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					$this->relationship_definition->get_display_name()
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
			$this->options[] = $option_intermediary->get_option();
			
		}
	}
	
	
	/**
	 * Set the post selector options in Views and WPAs loops.
	 *
	 * In those cases, you can:
	 * - display parent and child data from the IPT.
	 * - display the IPT data from parent and child posts when the association is well defined.
	 *
	 * @since m2m
	 */
	private function set_many_to_many_view_loop() {
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$option_parent->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s if this View is set to display %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
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
		$this->options[] = $option_parent->get_option();
		
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option_child->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s if this View is set to display %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
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
		$this->options[] = $option_child->get_option();
		
		$option_intermediary = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::INTERMEDIARY,
			$this
		);
		$option_intermediary->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s if this View is set to display %2$s or %3$s and has a filter by the %4$s relationship.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
				$this->relationship_definition->get_display_name()
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
		$this->options[] = $option_intermediary->get_option();
	}
	
	
	/**
	 * Set the post selector options in Content Templates.
	 *
	 * In this case, you can:
	 * - display parent and child data from the IPT.
	 *
	 * @since m2m
	 */
	private function set_many_to_many_content_template() {
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$option_parent->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s if this Content Template is set to display %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
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
		$this->options[] = $option_parent->get_option();
		
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option_child->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s if this Content Template is set to display %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
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
		$this->options[] = $option_child->get_option();
		
		if ( null != $this->intermediary_type ) {
			
			$option_intermediary = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::INTERMEDIARY,
				$this
			);
			$option_intermediary->set_property( 'is_disabled', true );
			$option_intermediary->set_property( 
				'pointer_content', 
				'<h3>' . sprintf(
					__( '%1$s (many-to-many relationship)', 'wpv-views' ),
					$this->relationship_definition->get_display_name()
				) . '</h3><p>' . sprintf(
					__( '%1$s connects with %2$s through %3$s.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>'
				) . '</p><p>' . sprintf(
					__( 'To display %1$s you need to use a View that will list %2$s or %3$s and include a filter by the %4$s relationship.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					$this->relationship_definition->get_display_name()
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
						)
					)
					. '" target="_blank">',
					'<i class="fa fa-external-link"></i>',
					'</a>'
				) . '</p>' 
			);
			$this->options[] = $option_intermediary->get_option();
			
		}
	}
	
	
	/**
	 * Set the post selector options when editing an IPT.
	 *
	 * In this case, you can:
	 * - display parent and child data from the IPT.
	 *
	 * @since m2m
	 */
	private function set_many_to_many_current_intermediary() {
		$option_parent = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::PARENT,
			$this
		);
		$option_parent->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s for the current %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY, true ) . '</strong>'
			) . '</p>' 
		);
		$this->options[] = $option_parent->get_option();
		
		$option_child = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option_child->set_property( 
			'pointer_content', 
			'<h3>' . sprintf(
				__( '%1$s (many-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'You can display the related %1$s for the current %2$s.', 'wpv-views' ),
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD, true ) . '</strong>',
				'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::INTERMEDIARY, true ) . '</strong>'
			) . '</p>' 
		);
		$this->options[] = $option_child->get_option();
	}
	
}