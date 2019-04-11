<?php

/**
 * Handles the creation of the Toolset Content Template Gutenberg block to allow Content Template embedding inside the Gutenberg editor.
 *
 * @since 2.6.0
 */
class Toolset_Blocks_Content_Template {

	public function init_hooks() {

		// "toolset_register_block_editor_assets" and "toolset_register_block_type" are hooked on "init" with priority 111
		// because "wpv_generic_register_secondary_shortcodes_dialog_groups" that registers the transient for published Content
		// Templates used there, is hooked on "init" with priority 110.
		add_action( 'init', array( $this, 'toolset_register_block_editor_assets' ), 111 );

		add_action( 'init', array( $this, 'toolset_register_block_type' ), 111 );

		// Hook scripts function into block editor hook
		add_action( 'enqueue_block_editor_assets', array( $this, 'toolset_blocks_editor_scripts' ) );

		// Hook scripts function into block editor hook
		add_action( 'enqueue_block_assets', array( $this, 'toolset_blocks_scripts' ) );
	}

	/**
	 * Register the needed assets for the Toolset Gutenberg blocks
	 *
	 */
	public function toolset_register_block_editor_assets() {
		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		$toolset_assets_manager->register_script(
			'toolset-ct-block-js',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/js/ct.block.editor.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_ajax_controller = Toolset_Ajax::get_instance();
		wp_localize_script(
			'toolset-ct-block-js',
			'toolset_ct_block_strings',
			array(
				'published_cts' => $this->get_available_cts(),
				'wpnonce' => wp_create_nonce( Toolset_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW ),
				'actionName' => $toolset_ajax_controller->get_action_js_name( Toolset_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW ),
			)
		);

		$toolset_assets_manager->register_script(
			'toolset-ct-block-frontend-js',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/js/ct.block.frontend.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset-ct-block-editor-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/ct.block.editor.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset-ct-block-editor-frontend-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/ct.block.style.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);
	}

	/**
	 * Register block type. We can use this method to register the editor & frontend scripts as well as the render callback.
	 *
	 * @note For now the scripts registration is disabled as it creates console errors on the classic editor.
	 *
	 */
	public function toolset_register_block_type() {
		register_block_type( 'toolset/ct', array(
				'render_callback' => array( $this, 'wpv_gutenberg_ct_block_render' ),
		) );
	}

	/**
	 * Enqueue assets, needed on the editor side, for the Toolset Gutenberg blocks
	 *
	 */
	public function toolset_blocks_editor_scripts() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-ct-block-js' ) );
		do_action( 'toolset_enqueue_styles', array( 'toolset-ct-block-editor-css' ) );
	}

	/**
	 * Enqueue assets, needed on the frontend side, for the Toolset Gutenberg blocks
	 *
	 */
	public function toolset_blocks_scripts() {
		return;
	}

	/**
	 *
	 * @return array|mixed
	 */
	public function get_available_cts() {
		global $pagenow;
		$ct_objects = get_transient( 'wpv_transient_published_cts' );

		if ( ! $ct_objects ) {
			$ct_objects = array();
		}
		
		$values_to_exclude = array();

		// Exclude current Content Template

		$action = toolset_getget( 'action', null );
		$action = null === $action ? toolset_getpost( 'action', null ) : $action;

		$post_id = (int) toolset_getget( 'post', 0 );
		$post_id = ( 0 === $post_id ? (int) toolset_getpost( 'post_ID', 0 ) : $post_id );

		$post = get_post( $post_id );
		if (
			'post.php' === $pagenow
			&& ( 'edit' === $action || 'editpost' == $action )
			&& null !== $post
			&& 'view-template' === $post->post_type
		) {
			$values_to_exclude[] = $post_id;
		}

		// Exclude all Loop Templates
		$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
		if ( count( $exclude_loop_templates_ids ) > 0 ) {
			$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
			$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
			// is_numeric + intval does sanitization
			$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
			$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
			if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
				$values_to_exclude = array_merge( $values_to_exclude, $exclude_loop_templates_ids_sanitized );
			}
		}

		return array_filter(
			$ct_objects,
			function( $ct ) use ( $values_to_exclude ) {
				if ( ! in_array( $ct->ID, $values_to_exclude ) ) {
					return true;
				} else {
					return false;
				}
			}
		);
	}

	/**
	 * Toolset Content Template Gutenberg Block render callback. Dynamic blocks are rendered using PHP instead of JavaScript.
	 *
	 * @param  array $attributes The attributes of the block.
	 * @return The output of the block. In this case the block renders a Content Template shortcode.
	 *
	 */
	public function wpv_gutenberg_ct_block_render( $attributes ) {
		$defaults = array(
			'ct' => '',
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		if ( '' !== $attributes['ct'] ) {
			$ct = ' view_template="' . $attributes['ct'] . '"';
			$shortcode_start = '[wpv-post-body';
			$shortcode_end = ']';

			return $shortcode_start . $ct . $shortcode_end;
		}

		return null;
	}
}
