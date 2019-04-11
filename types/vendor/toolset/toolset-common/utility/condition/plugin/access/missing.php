<?php

/**
 * Toolset_Condition_Plugin_Access_Missing
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Access_Missing extends Toolset_Condition_Plugin_Access_Active {

	public function is_met() {
		return ! parent::is_met();
	}

}