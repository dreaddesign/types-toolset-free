<?php

/*
 * unused class
 */
class Types_Information_Message_Post_Type extends Types_Information_Message {

	private $post_type;

	// dependency injection for post type
	public function __construct( Types_Post_Type $post_type ) {
		$this->post_type = $post_type;
	}

}