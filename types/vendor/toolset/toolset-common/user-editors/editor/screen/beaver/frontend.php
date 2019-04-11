<?php
class Toolset_User_Editors_Editor_Screen_Beaver_Frontend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	private $active_medium_id;
	private $beaver_filter_enabled;
	private $beaver_post_id_stack;
	private $beaver_post_id_assets_rendered;

	public function initialize() {
		
		// Pre-process Views shortcodes in the frontend editor and its AJAX update, as well as in the frontend rendering
		// Make sure the $authordata global is correctly set
		add_filter( 'fl_builder_before_render_shortcodes',		array( $this, 'before_render_shortcodes' ) );
		
		// Do nothing else in an admin, frontend editing and frontend editing AJAX refresh
		if (
			( is_admin() && ! wp_doing_ajax() )
			|| isset( $_GET['fl-builder'] ) 
			|| isset( $_POST['fl_builder_data'] ) 
		) {
			return;
		}

		/*
		// Those actions are not needed anymore
		add_action( 'wpv_before_shortcode_post_body', array( $this, 'action_set_post_id_for_views_body_shortcode' ) );
		add_action( 'wpv_after_shortcode_post_body',  array( $this, 'action_set_medium_id_after_views_body_shortcode' ) );
		add_action( 'wp', array( $this, 'action_globalize_medium_id' ) );

		add_filter( 'wpv_filter_content_template_output', array( $this, 'filter_archive_content' ), 10, 4 );
		*/
		add_filter( 'fl_builder_post_types',					array( $this, 'filter_support_medium' ) );
		
		add_filter( 'body_class',								array( $this, 'body_class' ) );
		
		add_filter( 'wpv_filter_content_template_output',		array( $this, 'filter_content_template_output' ), 10, 4 );
		add_filter( 'the_content',								array( $this, 'restore_beaver_filter' ), 9999 );
		
		$this->beaver_filter_enabled = true;
		$this->beaver_post_id_stack = array();
		$this->beaver_post_id_assets_rendered = array();

	}

	public function is_active() {
		return true;
	}
	
	// @todo we need to set the $authordata global, but we need to use it on do_shortcode
	// which happens after this filter callback, and as we need to restore after rendering
	// we can not do it here
	public function before_render_shortcodes( $content ) {
		/*
		global $authordata;
		$authordata_old = $authordata;
		$current_post_id = FLBuilderModel::get_post_id();
		if ( $current_post_id ) {
			$current_post_author = get_post_field( 'post_author', $current_post_id );
			$authordata = new WP_User( $current_post_author );
		}
		*/
		$content = WPV_Frontend_Render_Filters::pre_process_shortcodes( $content );
		/*
		$authordata = $authordata_old;
		*/
		return $content;
	}
	
	public function filter_support_medium( $allowed_types ) {
		if( ! is_array( $allowed_types ) ) {
			return array( $this->medium->get_slug() );
		}
		$medium_slug = $this->medium->get_slug();
		if ( ! in_array( $medium_slug, $allowed_types ) ) {
			$allowed_types[] = $medium_slug;
		}
		return $allowed_types;
	}
	
	public function body_class( $classes ) {
		if ( ! is_archive() ) {
			$current_post = get_post( FLBuilderModel::get_post_id() );
			if ( $current_post ) {
				$post_has_ct = get_post_meta( $current_post->ID, '_views_template', true );
				if ( $post_has_ct ) {
					$ct_has_beaver = get_post_meta( $post_has_ct, '_fl_builder_enabled', true );
					if ( $ct_has_beaver ) {
						$classes[] = 'fl-builder';
					}
				}
			}
		}
		return $classes;
	}
	
	public function filter_content_template_output( $content, $template_selected, $id, $kind ) {
		
		if (
			$template_selected 
			&& $template_selected > 0
		) {
			
			// There is a CT applied, either on single/archive pages or on a wpv-post-body shortcode
			// Render the BB content of the CT, if any, and prevent Beaver from overwriting it
			
			$editor_choice = get_post_meta( $template_selected, $this->medium->get_option_name_editor_choice(), true );
			
			if (
				$editor_choice
				&& $editor_choice == $this->editor->get_id()
			) {
				
				FLBuilderModel::update_post_data( 'post_id', $template_selected );
				
				$this->beaver_post_id_stack[] = $template_selected;

				// In order to render the content template output, Beaver Builder checks, among others, the output of the "in_the_loop" function.
				// When we try to render the content template output but have the "$wp_query->in_the_loop" set to false, the content template
				// output won't get the Beaver Builder touch.
				// Here we are faking being in the loop and reverting the setting back to it's previous state right after the content template output
				// is produced.
				global $wp_query;
				$revert_in_the_loop = false;
				if ( ! $wp_query->in_the_loop ) {
					$revert_in_the_loop = true;
					// Fake being in the loop.
					$wp_query->in_the_loop = true;
				}

				// In Beaver Builder 2.0, when the FLBuilder::render_content method is used, Beaver Builder is getting the
				// post ID by forcing globals, which means that they force the use of WP globals instead of checking their
				// internal post ID.
				// The WP globals contains the post currently rendered and not the Content Template, so we need to temporarily
				// set the Content Template in the $wp_the_query (which they use) and then put the old post in its place
				// after the content is rendered.
				global $wp_the_query;
				$wp_the_query_post = $wp_the_query->post;
				if ( (int) $template_selected !== $wp_the_query_post->ID ) {
					$ct_post = get_post( $template_selected );
					$wp_the_query->post = $ct_post;
				}
				
				$content = FLBuilder::render_content( $content );

				// If the post inside the $wp_the_query global has been substituted by the Content Template, we are putting
				// it back.
				if ( $wp_the_query->post->ID !== $wp_the_query_post->ID ) {
					$wp_the_query->post = $wp_the_query_post;
				}

				if ( $revert_in_the_loop ) {
					$wp_query->in_the_loop = false;
				}

				if ( ! in_array( $template_selected, $this->beaver_post_id_assets_rendered ) ) {
					// When having a Beaver Builder styled Content Template inside a Beaver Builder styled page we need to
					// change the current post id for the Beaver Builder model in order to load the style of the Content
					// Template instead of the page style.
					FLBuilderModel::set_post_id( $template_selected );

					FLBuilder::enqueue_layout_styles_scripts();

					// After the Beaver Builder styled Content Template styles are loaded, we are reverting the model's id
					// back to the id of the page.
					FLBuilderModel::reset_post_id( $template_selected );
					
					$this->beaver_post_id_assets_rendered[] = $template_selected;
				}
				
				array_pop( $this->beaver_post_id_stack );
				if ( count( $this->beaver_post_id_stack ) > 0 ) {
					$aux_array = array_slice( $this->beaver_post_id_stack, -1 );
					$bb_post_id = array_pop( $aux_array );
					FLBuilderModel::update_post_data( 'post_id', $bb_post_id );
				} else {
					FLBuilderModel::update_post_data( 'post_id', get_the_ID() );
				}
				
			}
			
			remove_filter( 'the_content', 'FLBuilder::render_content' );
			$this->beaver_filter_enabled = false;
			
		} else {
			global $post;
			if ( isset( $post->view_template_override ) ) {
				$this_id = get_the_ID();
				// This is coming from a wpv-post-body shortcode with view_template="None" so we do need to apply BB here 
				FLBuilderModel::update_post_data( 'post_id', $this_id );
				
				$this->beaver_post_id_stack[] = $this_id;
				
				$content = FLBuilder::render_content( $content );
				
				if ( isset( $template_selected ) && ! in_array( $template_selected, $this->beaver_post_id_assets_rendered ) ) {
					//FLBuilder::enqueue_layout_styles_scripts();
					$this->beaver_post_id_assets_rendered[] = $this_id;
				}
				
				array_pop( $this->beaver_post_id_stack );
				if ( count( $this->beaver_post_id_stack ) > 0 ) {
					$aux_array = array_slice( $this->beaver_post_id_stack, -1 );
					$bb_post_id = array_pop( $aux_array );
					FLBuilderModel::update_post_data( 'post_id', $bb_post_id );
				}
				
			}
		}
		
		return $content;
	}
	
	public function restore_beaver_filter( $content ) {
		if ( ! $this->beaver_filter_enabled ) {
			add_filter( 'the_content', 'FLBuilder::render_content' );
		}
		return $content;
	}

	public function filter_archive_content( $content, $template_selected, $id, $kind ) {

		if( $this->get_active_medium_id() && $this->get_active_medium_id() == $template_selected ) {
			FLBuilderModel::update_post_data( 'post_id', $this->get_active_medium_id() );
			$content = FLBuilder::render_content( $content );
		}


		return $content;
	}

	/**
	 * Beaver is looking for $_POST['post_id'] first, to select content
	 * we use that to get beaver content of our medium id
	 */
	public function action_globalize_medium_id() {
		if( $this->get_active_medium_id() )
			FLBuilderModel::update_post_data( 'post_id', $this->get_active_medium_id() );
	}

	private function get_active_medium_id() {
		if( $this->active_medium_id === null )
			$this->active_medium_id = $this->fetch_active_medium_id();

		return $this->active_medium_id;
	}

	private function fetch_active_medium_id() {
		$medium_id = $this->medium->get_id();

		$editor_choice = get_post_meta( $medium_id, $this->medium->get_option_name_editor_choice(), true );

		if(
			$editor_choice
		    && $editor_choice == $this->editor->get_id()
		    && isset( $medium_id ) && $medium_id
		)
			return $medium_id;


		return false;
	}

	public function action_set_post_id_for_views_body_shortcode() {
		add_filter( 'the_content', 'FLBuilder::render_content' );
		FLBuilderModel::update_post_data( 'post_id', get_the_ID() );

		add_filter( 'wpv_filter_content_template_output', 'FLBuilder::render_content' );
	}

	public function action_set_medium_id_after_views_body_shortcode() {
		remove_filter( 'the_content', 'FLBuilder::render_content' );

		if( $this->get_active_medium_id() )
			FLBuilderModel::update_post_data( 'post_id', $this->get_active_medium_id() );
	}


}