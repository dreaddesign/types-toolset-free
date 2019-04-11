<?php

/**
 * Handles the creation of the Toolset View Gutenberg block to allow Views embedding inside the Gutenberg editor.
 *
 * @since 2.6.0
 */
class Toolset_Blocks_View {

	public function init_hooks() {

		// "toolset_register_block_editor_assets" and "toolset_register_block_type" are hooked on "init" with priority 111
		// because "wpv_generic_register_secondary_shortcodes_dialog_groups" that registers the transient for published Views
		// used there, is hooked on "init" with priority 110.
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
	 * @since 2.6.0
	 */
	public function toolset_register_block_editor_assets() {
		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		$toolset_assets_manager->register_script(
			'toolset-view-block-js',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/js/view.block.editor.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_ajax_controller = Toolset_Ajax::get_instance();
		wp_localize_script(
			'toolset-view-block-js',
			'toolset_view_block_strings',
			array(
				'published_views' => get_transient( 'wpv_transient_published_views' ),
				'wpnonce' => wp_create_nonce( Toolset_Ajax::CALLBACK_GET_VIEW_BLOCK_PREVIEW ),
				'actionName' => $toolset_ajax_controller->get_action_js_name( Toolset_Ajax::CALLBACK_GET_VIEW_BLOCK_PREVIEW ),
			)
		);

		$toolset_assets_manager->register_script(
			'toolset-view-block-frontend-js',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/js/view.block.frontend.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset-view-block-editor-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/view.block.editor.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset-view-block-editor-frontend-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/view.block.style.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);
	}

	/**
	 * Register block type. We can use this method to register the editor & frontend scripts as well as the render callback.
	 *
	 * @note For now the scripts registration is disabled as it creates console errors on the classic editor.
	 *
	 * @since 2.6.0
	 */
	public function toolset_register_block_type() {
		register_block_type( 'toolset/view', array(
//		        'editor_script' => 'toolset-view-block-js', // Editor script
//		        'editor_style'  => 'toolset-view-block-editor-css', // Editor style
//		        'script' => 'toolset-view-block-frontend-js', // Frontend script
//		        'style' => 'toolset-view-block-editor-frontend-css', // Frontend style
				'render_callback' => array( $this, 'wpv_gutenberg_view_block_render' ),
		) );
	}

	/**
	 * Enqueue assets, needed on the editor side, for the Toolset Gutenberg blocks
	 *
	 * @since 2.6.0
	 */
	public function toolset_blocks_editor_scripts() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-view-block-js' ) );
		do_action( 'toolset_enqueue_styles', array( 'toolset-view-block-editor-css' ) );
	}

	/**
	 * Enqueue assets, needed on the frontend side, for the Toolset Gutenberg blocks
	 *
	 * @since 2.6.0
	 */
	public function toolset_blocks_scripts() {
		return;
	}

	/**
	 * Toolset View Gutenberg Block render callback. Dynamic blocks are rendered using PHP instead of JavaScript.
	 *
	 * @param  array $attributes The attributes of the block.
	 * @return The output of the block. In this case the block renders a View shortcode.
	 *
	 * @since 2.6.0
	 */
	public function wpv_gutenberg_view_block_render( $attributes ) {
		$defaults = array(
			'view' => '',
			'limit' => -1,
			'offset' => 0,
			'orderby' => '',
			'order' => '',
			'secondaryOrderby' => '',
			'secondaryOrder' => '',
			'hasExtraAttributes' => array(),
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		$view = '';
		if ( '' !== $attributes['view'] ) {
			$view = is_numeric( $attributes['view'] ) ? ' id="' . $attributes['view'] . '"' : ' name="' . $attributes['view'] . '"';
		}
		$shortcode_start = '[wpv-view';
		$shortcode_end = ']';
		$limit = (int) $attributes['limit'] > -1 ? ' limit="' . $attributes['limit'] . '"' : '';
		$offset = (int) $attributes['offset'] > 0 ? ' offset="' . $attributes['offset'] . '"' : '';
		$orderby = '' !== $attributes['orderby'] ? ' orderby="' . $attributes['orderby'] . '"' : '';
		$order = '' !== $attributes['order'] ? ' order="' . $attributes['order'] . '"' : '';
		$secondary_order_by = '' !== $attributes['secondaryOrderby'] ? ' orderby_second="' . $attributes['secondaryOrderby'] . '"' : '';
		$secondary_order = '' !== $attributes['secondaryOrder'] ? ' order_second="' . $attributes['secondaryOrder'] . '"' : '';

		$target = '';
		$view_display = '';

		if (
			isset( $attributes['hasCustomSearch'] )
			&& $attributes['hasCustomSearch']
			&& isset( $attributes['formDisplay'] )
			&& 'form' === $attributes['formDisplay']
		) {
			$shortcode_start = '[wpv-form-view';
			if (
				! isset( $attributes['formOnlyDisplay'] )
				|| 'samePage' === $attributes['formOnlyDisplay']
			) {
				$target = ' target_id="self"';
			} else if (
				isset( $attributes['formOnlyDisplay'] )
				&& 'otherPage' === $attributes['formOnlyDisplay']
				&& isset( $attributes['hasSubmit'] )
				&& $attributes['hasSubmit']
			) {
				$target = ' target_id="' . $attributes['otherPageID'] . '"';
			}
		}

		if (
			isset( $attributes['hasCustomSearch'] )
			&& $attributes['hasCustomSearch']
			&& isset( $attributes['formDisplay'] )
			&& 'results' === $attributes['formDisplay']
		) {
			$target = '';
			$view_display = ' view_display="layout"';
		}

		$query_filters = '';
		foreach ( $attributes['hasExtraAttributes'] as $extra_attribute ) {
			if ( ! empty( $attributes['queryFilters'][ $extra_attribute['filter_type'] ] ) ) {
				$query_filters .= ' ' . $extra_attribute['attribute'] . '="' . $attributes['queryFilters'][ $extra_attribute['filter_type'] ] . '"';
			}
		}

		return $shortcode_start . $view . $limit . $offset . $orderby . $order . $secondary_order_by . $secondary_order . $target . $view_display . $query_filters . $shortcode_end;
	}
}
