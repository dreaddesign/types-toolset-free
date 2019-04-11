<?php

class Toolset_User_Editors_Medium_Screen_Content_Template_Backend
	extends Toolset_User_Editors_Medium_Screen_Abstract {

	public function is_active() {
		if( ! is_admin() || ! array_key_exists( 'ct_id', $_REQUEST ) ) {
			return false;
		}

		return (int) $_REQUEST['ct_id'];
	}

	public function equivalent_editor_screen_is_active() {
		add_filter( 'wpv_ct_editor_localize_script', array( $this, 'set_user_editor_choice' ) );
	}
	
	/**
	* @todo refactor this, it should not happen this way.
	* The user editor choice can be added to the native localization 
	* with a filter defaulting to basic on each of the integrations backend screens,
	* we might not need this at all.
	*/
	
	public function set_user_editor_choice( $l10n_data ) {
		$l10n_data['user_editor'] = $this->manager->get_active_editor()->get_id();
		return $l10n_data;
	}

}