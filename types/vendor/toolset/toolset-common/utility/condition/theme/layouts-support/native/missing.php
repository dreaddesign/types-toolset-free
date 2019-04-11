<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Native_Missing
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Native_Missing
	extends Toolset_Condition_Theme_Layouts_Support_Native_Available {

	public function is_met() {
		return ! parent::is_met();
	}

}