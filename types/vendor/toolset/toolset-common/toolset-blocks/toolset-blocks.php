<?php
/**
 * Handles the creation and initialization of the all the Gutenberg integration stuff.
 *
 * @since 2.6.0
 */

class Toolset_Blocks {
	public function load_blocks() {
		if (
			! $this->is_gutenberg_active()
			|| ! $this->is_toolset_ready_for_gutenberg()
		) {
			return;
		}

		// Load Toolset View Gutenberg Block
		$view_block = new Toolset_Blocks_View();
		$view_block->init_hooks();

		// Load Toolset View Gutenberg Block
		$ct_block = new Toolset_Blocks_Content_Template();
		$ct_block->init_hooks();

		// Load Toolset Custom HTML core Gutenberg Block extension
		$custom_html_block = new Toolset_Blocks_Custom_HTML();
		$custom_html_block->init_hooks();
	}

	public function is_gutenberg_active() {
		// return defined( 'GUTENBERG_VERSION' ) || defined( 'GUTENBERG_DEVELOPMENT_MODE' );
		return function_exists( 'register_block_type' );
	}

	public function is_toolset_ready_for_gutenberg() {
		if ( version_compare( WPV_VERSION, '2.6-b1', '>' ) ) {
			return true;
		}
		return false;
	}
}
