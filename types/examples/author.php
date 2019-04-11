<?php
/**
 * This example file is a part of the Types plugin online documentation found at: https://wp-types.com/documentation/customizing-sites-using-php/
 * It is based on the original Twenty Sixteen theme's archive.php file.
 * It features additional code to render custom user fields created with the Types plugin.
 *
 * Please note that the names of the custom fields are for example purposes only and will not work in your site as-is. You need to edit this example according to the documentation mentioned above.
 *
 * The template for displaying custom author archive pages
 *
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
<!-- TYPES TIP: using get_the_author_meta function to output author name as the archive's title -->
			<?php
					echo '<h1 class="page-title">Author: ' . get_the_author_meta( 'first_name' ) . ' ' . get_the_author_meta( 'last_name' ) . '</h1>';
				?>

<!-- TYPES TIP: using get_avatar function to output author's avatar image -->						
				<div class="user-avatar"><?php echo get_avatar( get_the_author_meta( 'user_email' ), '150' ); ?></div>
				
<!-- TYPES TIP: using Types function to custom user fields -->		
				<p><strong>Specialty topics:</strong> <?php echo types_render_usermeta( "specialty-topics" ); ?><br />
				<strong>Staff role:</strong> <?php echo types_render_usermeta( "staff-role" ); ?></p>

<!-- TYPES TIP: using the the_author_meta function to output the description of the user -->						
				<p><b>About:</b> <?php the_author_meta( 'description' ); ?></p>
				
				</header><!-- .page-header -->
				
			<hr />

			
<!-- TYPES TIP: Creating a custom query for our author/user to display posts belonging to the "Post" and "Book" post types -->			
			<?php
			// Add Custom Post Type to author archive
			$args = array(
				'post_type' => array('post', 'book') ,
				'author' => get_queried_object_id(), // this will be the author ID on the author page
				'showposts' => 10
			);
			$custom_posts = new WP_Query( $args );
			
			// Start the Loop.
			while ( $custom_posts->have_posts() ) : $custom_posts->the_post();
				
				/*
				 * Include the Post-Format-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
				 */
				
				get_template_part( 'template-parts/content', get_post_type() );
				
			// End the loop.
			endwhile;

			// Previous/next page navigation.
			the_posts_pagination( array(
				'prev_text'          => __( 'Previous page', 'twentysixteen' ),
				'next_text'          => __( 'Next page', 'twentysixteen' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>',
			) );

		// If no content, include the "No posts found" template.
		else :
			

		endif;
		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
