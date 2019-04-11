<?php


interface Toolset_User_Editors_Editor_Screen_Interface {
	public function is_active();
	public function add_medium( Toolset_User_Editors_Medium_Interface $medium );
	public function add_editor( Toolset_User_Editors_Editor_Interface $editor );
}
