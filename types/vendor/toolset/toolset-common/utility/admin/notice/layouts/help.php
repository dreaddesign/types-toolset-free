<?php

/**
 * Class Toolset_Admin_Notice_Layouts_Help
 * Special about this notice: it's shown after the title of the layout and not after the page title like usual notices
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Layouts_Help
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notice_Layouts_Help extends Toolset_Admin_Notice_Abstract {
	/**
	 * Not temporary
	 * @var bool
	 */
	protected $is_temporary = false;

	/**
	 * Not dismissible
	 * @var bool
	 */
	protected $is_dismissible_permanent = false;

	/**
	 * default template file
	 */
	protected function set_default_template_file() {
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/toolset-custom-position.phtml';
	}

	/**
	 * Overwrites the render which is fired on 'admin_notices'
	 * We use it to forward the rendering to 'ddl-print-editor-additional-help-link'
	 * to show the notice after the title of the layout
	 */
	public function render() {
		add_action( 'ddl-print-editor-additional-help-link', array( $this, 'true_render' ), 100, 2 );
	}

	/**
	 * Called on 'ddl-print-editor-additional-help-link'
	 * This renders the notice.
	 *
	 * @param $layouts_array
	 * @param $current_id
	 */
	public function true_render( $layouts_array, $current_id ) {
		if( empty( $this->content ) || Toolset_Admin_Notices_Manager::is_notice_dismissed( $this ) ) {
			// abort if we have no content or notices is dismissed
			return;
		}
		parent::render();
	}

	/**
	 * As Toolset_Admin_Notice_Layouts_Help is registered before we knowing the id,
	 * we need to allow to set the id afterwards
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		if( is_string( $id ) ) {
			$this->id = sanitize_title( $id );
		}
	}

}