<?php
return array(
	/* Post Type with has_archive = false */
	'no-archive-support' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Archive_No_Support'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'The archive is disabled for this post type.', 'wpcf' )
			),
			array(
				'type' => 'paragraph',
				'content' => __( 'To enable, go to <a href="%POST-TYPE-EDIT-HAS-ARCHIVE%">Options</a> and mark "has_archive".', 'wpcf' )
			),
		),
	),

	/* Layouts, integrated, Archive missing */
	'layouts-integrated-archive-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Layouts_Compatible',
			'Types_Helper_Condition_Layouts_Archive_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no layout for the %POST-LABEL-PLURAL% archive.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create archive', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-ARCHIVE%',
			)
		),
	),

	/* Layouts, Archive */
	'layouts-archive' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Layouts_Archive_Exists'
		),

		'description' => array(
			array(
				'type'   => 'link',
				'label'  => '%POST-LAYOUT-ARCHIVE%',
				'target' => '%POST-EDIT-LAYOUT-ARCHIVE%',
			)
		),
	),

	/* Views, archive */
	'views-archive' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Archive_Exists'
		),

		'description' => array(
			array(
				'type'   => 'link',
				'label'  => '%POST-VIEWS-ARCHIVE%',
				'target' => '%POST-EDIT-VIEWS-ARCHIVE%',
			)
		),
	),

	/* For posts and pages we always show template file if it exists */
	'archive-exists-for-posts-pages' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Type_Post_Or_Page',
			'Types_Helper_Condition_Archive_Exists',
			'Types_Helper_Condition_Archive_Has_Fields'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-ARCHIVE-FILE%', 'wpcf' )
			),
		),
	),

	/* Layouts, has template with missing fields. */
	'layouts-archive-fields-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Layouts_Archive_Missing',
			'Types_Helper_Condition_Archive_No_Fields'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'The %POST-LABEL-PLURAL% archive of your theme %POST-ARCHIVE-FILE% is missing custom fields.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create archive', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-ARCHIVE%',
			)
		),
	),

	/* Layouts, single.php exists, but layouts missing */
	'layouts-php-archive-exists-layouts-archive-missing' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Layouts_Archive_Missing',
			'Types_Helper_Condition_Archive_Exists'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-ARCHIVE-FILE%', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create archive', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-ARCHIVE%',
			)
		),
	),

	/* Layouts, Archive missing */
	'layouts-archive-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Layouts_Archive_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no layout for the %POST-LABEL-PLURAL% archive.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create archive', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-ARCHIVE%',
			)
		),
	),

	/* No Views, No Layouts, Archive missing */
	'archive-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Archive_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Your theme is missing the standard WordPress archive for %POST-LABEL-PLURAL%.', 'wpcf' )
			),
			array(
				'type'   => 'dialog',
				'class'  => 'button',
				'label'  => __( 'Create archive', 'wpcf' ),
				'dialog' => array(
					'id' => 'resolve-no-archive',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'Toolset plugins let you design archive pages without writing PHP. Your archives will include all
                    the fields that you need and your design.', 'wpcf' )
						),
						array(
							'type'   => 'link',
							'class'  => 'button-primary types-button',
							'external' => true,
							'label'  => __( 'Learn about creating archives with Toolset', 'wpcf' ),
							'target' => Types_Helper_Url::get_url( 'creating-archives-with-toolset', 'popup' ),
						),
					)
				)
			)
		),
	),

	/* No Views, No Layouts, Archive without Fields */
	'archive-fields-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Archive_No_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'The %POST-LABEL-PLURAL% archive of your theme is missing custom fields.', 'wpcf' )
			),
			array(
				'type'   => 'dialog',
				'class'  => 'button',
				'label'  => __( 'Resolve', 'wpcf' ),
				'dialog' => array(
					'id' => 'resolve-no-custom-fields',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'Toolset plugins let you design archives with custom fields, without writing PHP.', 'wpcf' )
						),
						array(
							'type'   => 'link',
							'class'  => 'button-primary types-button',
							'external' => true,
							'label'  => __( 'Learn about creating archives with Toolset', 'wpcf' ),
							'target' => Types_Helper_Url::get_url( 'creating-archives-with-toolset', 'popup' ),
						),
					)
				)
			)
		),
	),

	/* No Views, No Layouts, Archive Fields */
	'archive-fields' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Archive_Has_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-ARCHIVE-FILE%', 'wpcf' )
			),
		),
	),

	/* Views, has template with missing fields. */
	'views-archive-fields-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Archive_Missing',
			'Types_Helper_Condition_Archive_No_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'The %POST-LABEL-PLURAL% archive of your theme %POST-ARCHIVE-FILE% is missing custom fields.', 'wpcf' )
			),
			array(
				'type' => 'link',
				'class' => 'button js-toolset-dashboard-create-archive',
				'target' => '%POST-CREATE-VIEWS-ARCHIVE%',
				'post_type' => '%POST-CREATE-VIEWS-ARCHIVE-TYPE%',
				'redirect_url' => '%POST-CREATE-VIEWS-ARCHIVE-REDIRECT-URL%',
				'forwhomtitle' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-TITLE%',
				'forwhomloop' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-LOOP%',
				'label'  => __( 'Create archive', 'wpcf' ),
			),
		),
	),

	/* Views, archive.php exists, but views missing */
	'views-php-archive-exists-views-archive-missing' => array(
		'type' => 'archive',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Archive_Missing',
			'Types_Helper_Condition_Archive_Exists'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-ARCHIVE-FILE%', 'wpcf' )
			),
			array(
				'type' => 'link',
				'class' => 'button js-toolset-dashboard-create-archive',
				'target' => '%POST-CREATE-VIEWS-ARCHIVE%',
				'post_type' => '%POST-CREATE-VIEWS-ARCHIVE-TYPE%',
				'redirect_url' => '%POST-CREATE-VIEWS-ARCHIVE-REDIRECT-URL%',
				'forwhomtitle' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-TITLE%',
				'forwhomloop' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-LOOP%',
				'label'  => __( 'Create archive', 'wpcf' ),
			),
		),

	),

	/* Views, template missing */
	'views-archive-missing' => array(
		'type' => 'archive',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Archive_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no WordPress Archive for %POST-LABEL-PLURAL%.', 'wpcf' )
			),
			array(
				'type' => 'link',
				'class' => 'button js-toolset-dashboard-create-archive',
				'target' => '%POST-CREATE-VIEWS-ARCHIVE%',
				'post_type' => '%POST-CREATE-VIEWS-ARCHIVE-TYPE%',
				'redirect_url' => '%POST-CREATE-VIEWS-ARCHIVE-REDIRECT-URL%',
				'forwhomtitle' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-TITLE%',
				'forwhomloop' => '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-LOOP%',
				'label'  => __( 'Create archive', 'wpcf' ),
			),
		),

	),
);