<?php

abstract class Toolset_User_Editors_Editor_Screen_Abstract
	implements Toolset_User_Editors_Editor_Screen_Interface {

	/**
	 * @var Toolset_User_Editors_Medium_Interface
	 */
	protected $medium;

	/**
	 * @var Toolset_User_Editors_Editor_Interface
	 */
	protected $editor;

	/**
	 * Check whether the current page is a Views edit page or a WPAs edit page.
	 * We need this check to register the needed assets for the inline CT section of those pages.
	 *
	 * @return bool Return true if the current page is the Views or WPAs edit page, false othewrise.
	 */
	public function is_views_or_wpa_edit_page() {
		$screen = get_current_screen();

		/*
		 * Class "WPV_Page_Slug" was introduced in Views 2.5.0, which caused issues when the installed version of Views
		 * was older than 2.5.0.
		 */
		$views_edit_page_screen_id = class_exists( 'WPV_Page_Slug' ) ? WPV_Page_Slug::VIEWS_EDIT_PAGE : 'toolset_page_views-editor';
		$wpa_edit_page_screen_id = class_exists( 'WPV_Page_Slug' ) ? WPV_Page_Slug::WORDPRESS_ARCHIVES_EDIT_PAGE : 'toolset_page_view-archives-editor';

		return in_array(
			$screen->id,
			array(
				$views_edit_page_screen_id,
				$wpa_edit_page_screen_id,
			)
		);
	}


	public function add_medium( Toolset_User_Editors_Medium_Interface $medium ) {
		$this->medium = $medium;
	}

	public function add_editor( Toolset_User_Editors_Editor_Interface $editor ) {
		$this->editor = $editor;
	}

	public function is_active() {
		return false;
	}
}