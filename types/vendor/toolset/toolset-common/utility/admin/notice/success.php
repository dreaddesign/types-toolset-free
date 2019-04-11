<?php

/**
 * Class Toolset_Admin_Notice_Success
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Success
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notice_Success extends Toolset_Admin_Notice_Abstract {
	/**
	 * Success message is always temporary
	 * @var bool
	 */
	protected $is_temporary = true;

	/**
	 * default template file
	 */
	protected function set_default_template_file() {
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/success.phtml';
	}

}