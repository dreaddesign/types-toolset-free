<?php

/**
 * Toolset_Condition_Plugin_Layouts_Active
 */
class Toolset_Condition_User_Role_Admin implements Toolset_Condition_Interface {

	public function is_met() {
		if( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return false;
	}

}