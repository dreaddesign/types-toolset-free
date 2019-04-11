<?php

/**
 * Handles the extension of the core Custom HTML Gutenberg block to include the button for the
 * Fields and Views shortcodes.
 *
 * @since 2.6.0
 */
class Toolset_Blocks_Custom_HTML {

	public function init_hooks() {

		add_action( 'init', array( $this, 'toolset_register_block_editor_assets' ) );

//		add_action( 'init', array( $this, 'toolset_register_block_type' ) );

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
			'toolset-custom-html-block-js',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/js/custom.html.block.editor.js',
			array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ),
			TOOLSET_COMMON_VERSION
		);

		wp_localize_script(
			'toolset-custom-html-block-js',
			'toolset_custom_html_block_strings',
			array()
		);

		$toolset_assets_manager->register_style(
			'toolset-custom-html-block-editor-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/custom.html.block.editor.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset-custom-html-block-editor-frontend-css',
			TOOLSET_COMMON_URL . '/toolset-blocks/assets/css/custom.html.block.style.css',
			array( 'wp-blocks', 'wp-edit-blocks' ),
			TOOLSET_COMMON_VERSION
		);
	}

	/**
	 * Enqueue assets, needed on the editor side, for the Toolset Gutenberg blocks
	 *
	 * @since 2.6.0
	 */
	public function toolset_blocks_editor_scripts() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-custom-html-block-js' ) );
		do_action( 'toolset_enqueue_styles', array( 'toolset-custom-html-block-editor-css' ) );
	}

	/**
	 * Enqueue assets, needed on the frontend side, for the Toolset Gutenberg blocks
	 *
	 * @since 2.6.0
	 */
	public function toolset_blocks_scripts() {
		return;
	}
}