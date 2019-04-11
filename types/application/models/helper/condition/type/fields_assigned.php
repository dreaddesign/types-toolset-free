<?php

class Types_Helper_Condition_Type_Fields_Assigned extends Types_Helper_Condition {

	public function valid() {
		$post_type = Types_Helper_Condition::get_post_type();

		// false if we have no post type
		if( ! isset( $post_type->name ) )
			return false;

		// query a post
		$query = new WP_Query( 'post_type=' . $post_type->name . '&posts_per_page=1' );

		if( $query->have_posts() ) {
			$post = $query->posts[0];
			
		// for the case no post created yet (post fields group edit page / post type edit page)
		} else {
			$post = new stdClass();
			$post->ID = -1;
			$post->post_type = $post_type->name;
		}

		if( !function_exists( 'wpcf_admin_post_get_post_groups_fields') )
			include_once( WPCF_EMBEDDED_ABSPATH . '/includes/fields-post.php' );

		$fields = wpcf_admin_post_get_post_groups_fields( $post );

		if(
			isset( $fields )
			&& is_array( $fields )
		    && !empty( $fields )
		) return true;

		return false;
	}
}