<?php

if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
	return;
}

$medium = new Toolset_User_Editors_Medium_Content_Template();
$medium->add_screen( 'backend', new Toolset_User_Editors_Medium_Screen_Content_Template_Backend() );
$medium->add_screen( 'frontend', new Toolset_User_Editors_Medium_Screen_Content_Template_Frontend() );
$medium->add_screen( 'frontend-editor', new Toolset_User_Editors_Medium_Screen_Content_Template_Frontend_Editor() );

$editor_setup  = new Toolset_User_Editors_Manager( $medium );

$available_editors = array(
	'Toolset_User_Editors_Editor_Basic' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Basic_Backend',
	),
	'Toolset_User_Editors_Editor_Visual_Composer' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Visual_Composer_Backend',
		'frontend' => 'Toolset_User_Editors_Editor_Screen_Visual_Composer_Frontend',
	),
	'Toolset_User_Editors_Editor_Beaver' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Beaver_Backend',
		'frontend' => 'Toolset_User_Editors_Editor_Screen_Beaver_Frontend',
		'frontend-editor' => 'Toolset_User_Editors_Editor_Screen_Beaver_Frontend_Editor',
	),
	'Toolset_User_Editors_Editor_Native' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Native_Backend',
	),
	'Toolset_User_Editors_Editor_Avada' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Avada_Backend',
	),
	'Toolset_User_Editors_Editor_Divi' => array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Divi_Backend',
		'frontend' => 'Toolset_User_Editors_Editor_Screen_Divi_Frontend',
	),
);

if ( version_compare( WPV_VERSION, '2.6-b1', '>' ) ) {
	$available_editors['Toolset_User_Editors_Editor_Gutenberg'] = array(
		'backend' => 'Toolset_User_Editors_Editor_Screen_Gutenberg_Backend',
	);
}

foreach ( $available_editors as $editor_main_class => $editor_screen_classes ) {
	$editor = new $editor_main_class( $medium );
	if ( method_exists( $editor,'initialize' ) ) {
		$editor->initialize();
	}

	if ( $editor_setup->add_editor( $editor ) ) {
		foreach ( $editor_screen_classes as $key => $editor_screen_class ) {
			$new_editor_screen_class = new $editor_screen_class();
			$new_editor_screen_class->initialize();
			$editor->add_screen( $key, $new_editor_screen_class );

		}
	}
}

/**
* The editor setup is run early on init because it depends on the user capabilities
*/
add_action( 'init', array( $editor_setup, 'run' ), -1000  );
