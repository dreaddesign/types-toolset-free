<?php

/**
 * Toolset_Condition_Plugin_Layouts_Missing
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Layouts_Missing extends Toolset_Condition_Plugin_Layouts_Active {

	public function is_met() {
		// opposite of Layouts_Active::is_met()
		return ! parent::is_met();
	}

}