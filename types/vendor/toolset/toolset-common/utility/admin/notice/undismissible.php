<?php

/**
 *
 * Class Toolset_Admin_Notice_Undismissible
 * This message should be shown always.
 *
 */
class Toolset_Admin_Notice_Undismissible extends Toolset_Admin_Notice_Abstract {

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
		$this->template_file = $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/templates/admin/notice/toolset.phtml';
	}

}