<?php

class Toolset_User_Editors_Medium_Screen_Content_Template_Frontend_Editor
	extends Toolset_User_Editors_Medium_Screen_Abstract {

	private $original_global_post;

	/**
	 * Toolset_User_Editors_Medium_Screen_Content_Template_Frontend_Editor constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_set_preview_post', array( $this, 'ajax_set_preview_post' ) );
	}

	public function is_active() {
		if( is_admin() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		// todo we need to move here an equivalent to the editor screen action method
		// It should do what equivalent_editor_screen_is_active does, as it will be removed
		// It should get the methods now on /editor/screen/beaver/frontend-editor.php that are user editor agnostic
		// And we move there the ones that are BB only related
		$this->action();
		return true;
	}
	
	private function action() {
		
	}

	public function equivalent_editor_screen_is_active() {
		add_action( 'init', array( $this, 'register_as_post_type' ) );
		add_action( 'wp', array( $this, 'load_medium_id_by_post' ), -1 );
		
		add_action( 'wp', array( $this, 'register_assets' ) );
	}

	public function load_medium_id_by_post() {
		global $post;

		if( ! is_object( $post ) || $post->post_type != 'view-template'  ) {
			return false;
		}

		$this->manager->get_medium()->set_id( $post->ID );

		// todo outsource complete preview selector to 'resources'
		add_action( 'wp_footer', array( $this, 'render_preview_post_selector') );
		
		// set global post helper labels outside the frontend editor
		add_action( 'wp', array( $this, 'set_global_post_helper_labels' ) );

		// set preview post as global post
		// todo move to beaver/screen/backend
		add_action( 'fl_builder_before_render_module', array( $this, 'set_preview_post' ) );

		// reset global post after content is loaded via ajax
		// todo move to beaver/screen/backend
		add_action( 'fl_builder_after_render_content', array( $this, 'reset_preview_post' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_assets() {
		
		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();
		
		$toolset_assets_manager->register_style(
			'toolset-user-editors-ct-frontend-editor-style', 
			TOOLSET_COMMON_URL . '/user-editors/medium/screen/content-template/frontend-editor.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_script(
			'toolset-user-editors-ct-frontend-editor-script',
			TOOLSET_COMMON_URL . '/user-editors/medium/screen/content-template/frontend-editor.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);
		
		$toolset_assets_manager->localize_script(
			'toolset-user-editors-ct-frontend-editor-script',
			'toolset_user_editors',
			array(
				'nonce' => wp_create_nonce( 'toolset_user_editors' ),
				'mediumId' => $this->manager->get_medium()->get_id(),
				'mediumUrl' => admin_url( 'admin.php?page=ct-editor&ct_id=' . $this->manager->get_medium()->get_id() ),
			)
		);
		
	}

	public function enqueue_assets() {
		
		do_action( 'toolset_enqueue_styles',	array( 'toolset-user-editors-ct-frontend-editor-style' ) );
		do_action( 'toolset_enqueue_scripts',	array( 'toolset-user-editors-ct-frontend-editor-script' ) );
		
	}
	

	public function register_as_post_type() {
		register_post_type( 'view-template', array(
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'rewrite'            => array( 'slug' => 'view-template' ),
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
		) );

		flush_rewrite_rules();
	}
	
	public function set_global_post_helper_labels() {
		if( isset( $_GET['fl_builder'] ) ) {
			global $post;
			$post->post_title	= __( '{{Post title}}', 'wpv-views' );
			$post->post_author	= get_current_user_id();
			$post->post_date	= date( "Y-m-d H:i:s" );
		}
	}

	public function set_preview_post() {
		if( isset( $_POST['fl_builder_data'] ) ) {
			global $post;

			if( $this->original_global_post === null ) {
				$this->original_global_post = $post;
			}

			$preview_post = $this->get_preview_post_id( $this->original_global_post->ID );

			if( $preview_post ) {
				$post = get_post( $preview_post );
			}
			// no preview post selected or selected does not exist anymore
			else {
				// disable shortcode rendering
				add_filter( 'fl_builder_render_shortcodes', '__return_false' );
			}
		}
	}

	public function reset_preview_post() {
		if( isset( $_POST['fl_builder_data'] ) ) {
			global $post;
			$post = $this->original_global_post;
		}
	}

	// @todo Offer to preview only posts that have this CT assigned, when frontend editing a CT assigned to single posts.
	public function render_preview_post_selector() {
		if( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			global $post;

			$toolset_frontend_editor = get_post_meta( $post->ID, '_toolset_user_editors_beaver_template', true );
			$preview_slug = array_key_exists( 'preview_slug', $toolset_frontend_editor )
				? $toolset_frontend_editor['preview_slug']
				: false;
				
			$preview_posts = array();

			if( $toolset_frontend_editor['preview_domain'] == 'post' ) {
				if ( post_type_exists( $preview_slug ) ) {
					$preview_posts =  wp_get_recent_posts( array( 'post_type' => $preview_slug ), ARRAY_A );
				}
			} else {
				if ( taxonomy_exists( $preview_slug ) ) {
					
					$terms = get_terms( $preview_slug );
					$term_ids = array();
					foreach( $terms as $term ) {
						$term_ids[] = $term->term_id;
					}

					$preview_posts =  wp_get_recent_posts( array( 'post_type' => 'any', 'tax_query' => array(
						array(
							'taxonomy' => $preview_slug,
							'field' => 'id',
							'terms' => $term_ids
						))), ARRAY_A );
					
				}
			}

			$preview_post = $this->get_preview_post_id( $post->ID );
			$preview_post_offered = false;
			
			$selected = $preview_post == 0
				? ' selected="selected"'
				: '';

			$options = '';
			foreach( $preview_posts as $single_post ) {
				$selected = $preview_post == $single_post['ID']
					? ' selected="selected"'
					: '';
				if ( ! empty( $selected ) ) {
					$preview_post_offered = true;
				}
				$options .= '<option value="' . $single_post['ID'] . '"' . $selected . '>'.$single_post['post_title'].'</option>';
			}

			$output = 	'<span class="fl-builder-bar-title toolset-editors-frontend-editor-extra js-toolset-editors-frontend-editor-extra">' .
							'<span class="toolset-editors-frontend-editor-extra-content js-toolset-editors-frontend-editor-extra-content">' .
								'<span class="toolset-editors-frontend-editor-extra-content-icon-container">' .
									'<i class="icon icon-toolset-logo"></i>' .
								'</span>' .
								'<span>' .
									__( 'Preview this Content Template with:', 'wpv-views' ) .
								'</span>' .
								'<select id="wpv-ct-preview-post">' .
									'<option value="0"' . $selected . '>'.__( 'No post', 'wpv-views' ).'</option>' .
									$options .
								'</select>' .
							'</span>' .
						'</span>';

			echo $output;

			if ( ! $preview_post_offered ) {
				$this->store_preview_post_id( $post->ID, 0 );
			}
		}
	}

	public function ajax_set_preview_post() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'toolset_user_editors' ) ) {
			die( -1 );
		}

		if( isset( $_REQUEST['ct_id'] ) && isset( $_REQUEST['preview_post_id'] ) ) {
			$this->store_preview_post_id( (int) $_REQUEST['ct_id'], (int) $_REQUEST['preview_post_id'] );
		}

		die( 1 );
	}

	private function store_preview_post_id( $ct_id, $preview_post_id ) {
		update_post_meta( $ct_id, '_toolset_user_editors_frontend_editor_preview_post', $preview_post_id );
	}

	private function get_preview_post_id( $ct_id ) {
		$stored_template = get_post_meta( $ct_id, '_toolset_user_editors_frontend_editor_preview_post', true  );

		// stored template available and is an allowed template
		if( $stored_template && get_post( $stored_template ) ) {
			return $stored_template;
		}

		return false;
	}
}