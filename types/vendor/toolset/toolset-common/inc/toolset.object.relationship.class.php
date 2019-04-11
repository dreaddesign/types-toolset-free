<?php

/**
* Toolset_Object_Relationship
*
* Manages the id="XXX" attribute on Types and Views shortcodes.
*
* @since 1.9.0
*/

if ( ! class_exists( 'Toolset_Object_Relationship', false ) ) {
	class Toolset_Object_Relationship
	{
		private static $instance;
		
		public function __construct()
        {
			$this->relations				= array();
			$this->post_relationship_depth	= 0;
			$this->post_relationship_track	= array();
			$this->post_relationship		= array();
			
            add_action( 'admin_init',								array( $this, 'admin_init' ) );
			
			add_action( 'toolset_action_record_post_relationship_belongs',	array( $this, 'force_record_post_relationship_belongs' ) );
			add_action( 'toolset_action_restore_post_relationship_belongs',	array( $this, 'force_restore_post_relationship_belongs' ) );
			
			add_filter( 'the_content', 								array( $this, 'record_post_relationship_belongs' ), 0, 1 );
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 	array( $this, 'record_post_relationship_belongs' ), 0, 1 );
			
			add_filter( 'the_content', 								array( $this, 'restore_post_relationship_belongs' ), PHP_INT_MAX, 1 );
			add_filter( 'wpv_filter_wpv_the_content_suppressed', 	array( $this, 'restore_post_relationship_belongs' ), PHP_INT_MAX, 1 );
        }
		
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new Toolset_Object_Relationship();
			}
			return self::$instance;
		}

        public function admin_init() {
            
        }
		
		/**
		 * Manually record the Types relationships for a post.
		 *
		 * Given a WP_Post instance, this records the relations for its post type in this object relations property.
		 * AFter that, it calculates the specific parents for this given post in each of its stored relations.
		 * Finally, those specific relations are added to the post_relationship_track propery using a depth counter.
		 *
		 * @note This might be a little inefficient since we query once per relation.
		 * @note This is automatically called when looping over a post and calling the the_content filter.
		 * @note This might be much more efficient if we stored each post relationships, insted of a post_relationship_track property depending on a depth index.
		 *         Especially in the case that we need to get this same post relationships later on the same request.
		 *         This will need extensive review once the m2m feature lands in.
		 *         Right now, we use this depth index because of nested structures, but a permanent $post->ID based data schema might be much better.
		 *
		 * @since 2.3.0
		 */
		
		function force_record_post_relationship_belongs( $post_object = null ) {
			
			if ( ! $post_object instanceof WP_Post ) {
				return;
			}
			
			// In empty WPAs, we fake a loop with a faked post with no post type
			if ( empty( $post_object->post_type ) ) {
				return;
			}
			
			$this->post_relationship_depth = ( $this->post_relationship_depth + 1 );

			if ( 
				! empty( $post_object->ID ) 
				&& function_exists( 'wpcf_pr_get_belongs' ) 
			) {

				if ( ! isset( $this->relations[ $post_object->post_type ] ) ) {
					$this->relations[ $post_object->post_type ] = wpcf_pr_get_belongs( $post_object->post_type );
				}
				if ( is_array( $this->relations[ $post_object->post_type ] ) ) {
					foreach ( $this->relations[ $post_object->post_type ] as $post_type => $data ) {
						$related_id = wpcf_pr_post_get_belongs( $post_object->ID, $post_type );
						if ( $related_id ) {
							$this->post_relationship['$' . $post_type . '_id'] = $related_id;
						} else {
							$this->post_relationship['$' . $post_type . '_id'] = 0;
						}
					}
				}
			}
			
			$this->post_relationship_track[ $this->post_relationship_depth ] = $this->post_relationship;
			
		}
		
		/**
		 * Manually restore the Types relationships.
		 *
		 * AFter using the relationships of a given post, before moving to the next one, this cleans the data.
		 * As the data is stored in a post_relationship_track property with a depth index, we clean the last one.
		 *
		 * @note This is automatically called when looping over a post and calling the the_content filter, at a very late priority.
		 * @note As described above, a permanent $post->ID based data schema might be better and would not require this kind of cleaning.
		 *         This will be reviewed once the m2m feture lands in.
		 *
		 * @since 2.3.0
		 */
		
		function force_restore_post_relationship_belongs() {
			
			$this->post_relationship_depth = ( $this->post_relationship_depth - 1 );
			if ( 
				$this->post_relationship_depth > 0 
				&& isset( $this->post_relationship_track[ $this->post_relationship_depth ] )
			) {
				$this->post_relationship = $this->post_relationship_track[ $this->post_relationship_depth ];
			} else {
				$this->post_relationship = array();
			}
			
		}
		
		/**
		 * Callback to record the current post relationships early in the the_content filter.
		 *
		 * @uses Toolset_Object_Relationship::force_record_post_relationship_belongs
		 *
		 * @since unknown
		 */
		
		function record_post_relationship_belongs( $content ) {
			
			global $post;
			$this->force_record_post_relationship_belongs( $post );

			return $content;
			
		}
		
		/**
		 * Callback to restore the stored post relationships late in the the_content filter.
		 *
		 * @uses Toolset_Object_Relationship::force_restore_post_relationship_belongs
		 *
		 * @since unknown
		 */
		
		function restore_post_relationship_belongs( $content ) {
			
			$this->force_restore_post_relationship_belongs();
			
			return $content;
			
		}
	}
}

/**
 * class WPV_wpcf_switch_post_from_attr_id
 *
 * This class handles the "id" attribute in a wpv-post-xxxxx shortcode
 * and sets the global $id, $post, and $authordata
 *
 * It also handles types. eg [types field='my-field' id='233']
 *
 * id can be a integer to refer directly to a post
 * id can be $parent to refer to the parent
 * id can be $current_page or refer to the current page
 *
 * id can also refer to a related post type
 * eg. for a stay the related post types could be guest and room
 * [types field='my-field' id='$guest']
 * [types field='my-field' id='$room']
 */
if ( ! class_exists( 'WPV_wpcf_switch_post_from_attr_id', false ) ) {
	class WPV_wpcf_switch_post_from_attr_id
	{

		function __construct( $atts ) {
			$this->found = false;
			$this->reassign_original_post = false;
			$this->toolset_object_relationship = Toolset_Object_Relationship::get_instance();
			$this->post_relationship = $this->toolset_object_relationship->post_relationship;

			if ( isset( $atts['id'] ) ) {

				global $post, $authordata, $id;

				$post_id = 0;

				if ( strpos( $atts['id'], '$' ) === 0 ) {
					// Handle the parent if the id is $parent
					if ( 
						$atts['id'] == '$parent' 
						&& isset( $post->post_parent ) 
					) {
						$post_id = $post->post_parent;
					} else if ( $atts['id'] == '$current_page' ) {
						if ( is_single() || is_page() ) {
							global $wp_query;
							if ( isset( $wp_query->posts[0] ) ) {
								$current_post = $wp_query->posts[0];
								$post_id = $current_post->ID;
							}
						}
						
						/**
						 * Get the current top post.
						 *
						 * Some Toolset plugins might need to set the top current post under some scenarios,
						 * like Toolset Views when doing AJAX pagination or AJAX custom search.
						 * In those cases, they can use this filter to get the top current post they are setting
						 * and override the ID to apply as the $current_page value.
						 *
						 * @not Toolset plugins should set this just in time, not globally, when needed, meaning AJAX calls or whatever.
						 *
						 * @param $top_post 	null
						 *
						 * @return $top_post 	null/WP_Post object 	The top current post, if set by any Toolset plugin.
						 *
						 * @since 2.3.0
						 */
						$top_current_post = apply_filters( 'toolset_filter_get_top_current_post', null );
						if ( $top_current_post ) {
							$post_id = $top_current_post->ID;
						}
					} else {
						// See if Views has the variable
						global $WP_Views;
						if ( isset( $WP_Views ) ) {
							$post_id = $WP_Views->get_variable( $atts['id'] . '_id' );
						}
						if ( $post_id == 0 ) {
							// Try the local storage.
							if ( isset( $this->post_relationship[ $atts['id'] . '_id' ] ) ) {
								$post_id = $this->post_relationship[ $atts['id'] . '_id' ];
							}
						}
					}
				} else {
					$post_id = intval( $atts['id'] );
				}

				if ( $post_id > 0 ) {
					
					// if post does not exists
					if( get_post_status( $post_id ) === false ) {

						// set to true to reapply backup post in __destruct()
						$this->reassign_original_post = true;

						// save original post
						$this->post = ( isset( $post ) && ( $post instanceof WP_Post ) ) ? clone $post : null;

						$msg_post_does_not_exists = __(
							sprintf(
								'A post with the ID %s does not exist.',
								'<b>'.$atts['id'].'</b>'
							)
							, 'wpv-views'
						);

						// prevents PHP Warning for property assigned to empty $post value
						// stdClass arg prevents get_obejct_vars in the constructor and the foreach looping through those vars
						// to throw type error https://core.trac.wordpress.org/browser/tags/4.7.3/src/wp-includes/class-wp-post.php#L240
						$post = new WP_Post( new stdClass() );
						$post->post_title = $post->post_content = $post->post_excerpt = $msg_post_does_not_exists;

						return;
					}

					$this->found = true;

					// save original post 
					$this->post = ( isset( $post ) && ( $post instanceof WP_Post ) ) ? clone $post : null;
					if ( $authordata ) {
						$this->authordata = clone $authordata;
					} else {
						$this->authordata = null;
					}
					$this->id = $id;

					// set the global post values
					$id = $post_id;
					$post = get_post( $id );

					$authordata = new WP_User( $post->post_author );

				}
			}

		}

		function __destruct() {
			if ( $this->found ) {
				global $post, $authordata, $id;
				// restore the global post values.
				$post = ( isset( $this->post ) && ( $this->post instanceof WP_Post ) ) ? clone $this->post : null;
				if ( $this->authordata ) {
					$authordata = clone $this->authordata;
				} else {
					$authordata = null;
				}
				$id = $this->id;
			}
			
			if( isset( $this->reassign_original_post ) && $this->reassign_original_post ) {
				global $post;

				$post = ( isset( $this->post ) && ( $this->post instanceof WP_Post ) ) ? clone $this->post : null;
			}

		}

	}
}