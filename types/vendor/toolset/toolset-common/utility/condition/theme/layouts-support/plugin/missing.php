<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Plugin_Missing
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Plugin_Missing
	extends Toolset_Condition_Theme_Layouts_Support_Plugin_Available {

	public function is_met() {
		return ! parent::is_met();
	}

}