<?php

/**
 * Toolset_Condition_Plugin_Types_Missing
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Cred_Missing extends Toolset_Condition_Plugin_Cred_Active {

	public function is_met() {
		return ! parent::is_met();
	}

}