<?php

/**
 * Class Toolset_Admin_Notice_Required_Action
 * This message should be shown until the action is done.
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Required_Action
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notice_Required_Action extends Toolset_Admin_Notice_Abstract {
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
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/toolset.phtml';
	}

}