<?php
return array(
	/* CRED missing */
	'cred-missing' => array(
		'type' => 'forms',

		'conditions'=> array(
			'Types_Helper_Condition_Cred_Missing'
		),

		'description' => array(
			array(
				'type'   => 'dialog',
				'class'  => 'button',
				'label'  => __( 'Create Form', 'wpcf' ),
				'dialog' => array(
					'id' => 'create-form',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'To create a form for front-end content submission and editing, you need to have CRED plugin installed.
                    CRED is part of the complete Toolset package for adding and displaying custom content.', 'wpcf' )
						),
						array(
							'type' => 'link',
							'external' => true,
							'label' => __( 'Learn how CRED forms work', 'wpcf' ),
							'target'  => Types_Helper_Url::get_url( 'how-cred-work', 'popup' )
						),
						/*
						array(
							'type' => 'link',
							'external' => true,
							'label' => __( 'Free Toolset Trial', 'wpcf' ),
							'target'  => Types_Helper_Url::get_url( 'free-trial', 'popup' )
						),
						*/
					)
				)
			)
		),
	),

	/* CRED, forms missing */
	'cred-forms-missing' => array(
		'type' => 'forms',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Cred_Forms_Missing'
		),

		'description' => array(
			array(
				'type'   => 'link',
				'class'  => 'button',
				'target' => '%POST-CREATE-FORM%',
				'label'  => __( 'Create form', 'wpcf' )
			)
		)
	),

	/* CRED, forms */
	'cred-forms' => array(
		'type' => 'forms',

		'conditions'=> array(
			'Types_Helper_Condition_Cred_Active',
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Cred_Forms_Exist'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => '%POST-FORMS-LIST%'
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'target' => '%POST-CREATE-FORM%',
				'label'  => __( 'Create form', 'wpcf' )
			)
		)
	),

	/* CRED & Layouts, forms missing */
	'cred-layouts-forms-missing' => array(
		'type' => 'forms',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Cred_Forms_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __(
					'You can create forms for front-end submission and editing of %POST-LABEL-PLURAL%.', 'wpcf'
				)
			),
			array(
				'type'   => 'link',
				'external' => true,
				'target' => Types_Helper_Url::get_url( 'adding-forms-to-layouts', 'table' ),
				'label'  => __( 'Learn how', 'wpcf' )
			),
		)
	),

	/* CRED & Layouts, forms exists */
	'cred-layouts-forms' => array(
		'type' => 'forms',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Cred_Forms_Exist'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => '%POST-FORMS-LIST%'
			),
			array(
				'type'   => 'link',
				'external' => true,
				'target' => Types_Helper_Url::get_url( 'adding-forms-to-layouts', 'table' ),
				'label'  => __( 'How to add forms to layouts', 'wpcf' )
			),
		)
	),
);