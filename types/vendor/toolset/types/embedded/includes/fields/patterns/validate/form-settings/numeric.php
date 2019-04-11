<?php

return array(
	'control' => array(
		'#pattern' => '<tr><td><TITLE></td><td><ERROR><BEFORE><ELEMENT><LABEL><AFTER></td></tr>',
		'#title' => __( 'Validation', 'wpcf' ),
		'#label' => __( 'Number', 'wpcf' ),
		'#attributes' => array(
			'class' => 'js-wpcf-validation-checkbox',
		),
	),
	'message' => array(
		'#label' => __( 'Validation error message', 'wpcf' ),
		'#attributes' => array(
			'class' => 'widefat'
		)
	)
);