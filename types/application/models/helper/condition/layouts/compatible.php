<?php

/**
 * Types_Helper_Condition_Layouts_Compatible
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Compatible extends Types_Helper_Condition_Template {

	public function __construct() {
		$cpt = Types_Helper_Condition::get_post_type();

		$this->templates = array(
			'single-' . $cpt->name . '.php',
			'archive-' . $cpt->name . '.php',
			'single.php',
			'archive.php',
			'index.php'
		);
	}

	public function valid() {
		// theme + theme integration running
		if ( defined( 'LAYOUTS_INTEGRATION_THEME_NAME' ) ) {
			return true;
		}

		$filesystem = new Toolset_Filesystem_File();
		foreach ( $this->templates as $name => $file ) {
			// file exists
			if ( $filesystem->open( get_template_directory() . '/' . $file )
			     || $filesystem->open( get_stylesheet_directory() . '/' . $file )
			) {
				// supports layouts
				if ( $filesystem->search( 'the_ddlayout' ) ) {
					return true;
				}

				// if for example single.php exists and it does not support Layouts we don't need to look at index.php
				return false;
			}
		}

		// no file exists
		return false;
	}

}