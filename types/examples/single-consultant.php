<?php
/**
 * This example file is a part of the Types plugin online documentation found at: https://wp-types.com/documentation/customizing-sites-using-php/
 * It is based on the original Twenty Sixteen theme's single.php file.
 * It does not use template parts, but loads the post contents directly.
 * It also features additional code to render custom fields created with the Types plugin.
 *
 * Please note that the names of the custom fields are for example purposes only and will not work in your site as-is. You need to edit this example according to the documentation mentioned above.
 *
 * The template for displaying single "Consultant" posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		// Start the loop.
		while ( have_posts() ) : the_post();
		?>
		
		<!-- Standard Twenty Sixteen article header output -->	
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
			<?php if ( is_sticky() && is_home() && ! is_paged() ) : ?>
				<span class="sticky-post"><?php _e( 'Featured', 'twentysixteen' ); ?></span>
			<?php endif; ?>

			<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
			</header><!-- .entry-header -->

			<?php twentysixteen_excerpt(); ?>

	
<!-- TYPES TIP: Custom call to Types function to render a custom field "Consultant Photo" -->	
			<?php echo types_render_field( "consultant-photo", array( "size" => "thumbnail" )); ?>

	
			<div class="entry-content">
	
	
<!-- TYPES TIP: Custom call to get_the_term_list function to display a list of taxonomy terms that the current "Consultant" post belongs to -->		
	<?php echo get_the_term_list( $post->ID, 'spoken-language', '<p><strong>Spoken languages: </strong>', ', ', '</p>'); ?>
	
<!-- TYPES TIP: Custom call to Types function to render a custom field "Consultant Roles" -->	
		<p><strong>Role: <?php echo types_render_field( "consultant-roles" );  // Call to Types function for rendering a custom field "Consultant Roles" ?></strong></p>	

		
				<!-- Standard Twenty Sixteen content output -->	
				<?php
				/* translators: %s: Name of current post */
					the_content( sprintf(
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
					get_the_title()
					) );

					wp_link_pages( array(
						'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentysixteen' ) . '</span>',
						'after'       => '</div>',
						'link_before' => '<span>',
						'link_after'  => '</span>',
						'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>%',
						'separator'   => '<span class="screen-reader-text">, </span>',
					) );
				?>
		
<!-- TYPES TIP: Custom call to Types function to render a custom field "Consultant Contact Phone" -->
				<p><strong>Contact Phone: <?php echo types_render_field( "consultant-phone-number"); ?></strong></p>
			</div><!-- .entry-content -->

			<!-- Standard Twenty Sixteen content footer output -->		
			<footer class="entry-footer">
				<?php twentysixteen_entry_meta(); ?>
					<?php
					edit_post_link(
						sprintf(
						/* translators: %s: Name of current post */
						__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
						get_the_title()
						),
					'<span class="edit-link">',
					'</span>'
					);
				?>
			</footer><!-- .entry-footer -->
		</article><!-- #post-## -->
			
			
			<?php
			// TYPES TIP: all of the lines below this point are the standard part of a single.php of the Twenty Sixteen theme.
			
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}

			if ( is_singular( 'attachment' ) ) {
				// Parent post navigation.
				the_post_navigation( array(
					'prev_text' => _x( '<span class="meta-nav">Published in</span><span class="post-title">%title</span>', 'Parent post link', 'twentysixteen' ),
				) );
			} elseif ( is_singular( 'post' ) ) {
				// Previous/next post navigation.
				the_post_navigation( array(
					'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentysixteen' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Next post:', 'twentysixteen' ) . '</span> ' .
						'<span class="post-title">%title</span>',
					'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentysixteen' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Previous post:', 'twentysixteen' ) . '</span> ' .
						'<span class="post-title">%title</span>',
				) );
			}

			// End of the loop.
		endwhile;
		?>

	</main><!-- .site-main -->

	<?php get_sidebar( 'content-bottom' ); ?>

</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
