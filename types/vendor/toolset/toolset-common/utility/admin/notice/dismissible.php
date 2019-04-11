<?php

/**
 * Class Toolset_Admin_Notice_Dismissible
 * This message should be shown until the action is done.
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Dismissible
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notice_Dismissible extends Toolset_Admin_Notice_Abstract {
	/**
	 * Not temporary
	 * @var bool
	 */
	protected $is_temporary = false;

	/**
	 * Not dismissible
	 * @var bool
	 */
	protected $is_dismissible_permanent = true;

	/**
	 * default template file
	 */
	protected function set_default_template_file() {
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/toolset.phtml';
	}

}