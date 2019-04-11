<?php
/**
 * This example file is a part of the Types plugin online documentation found at: https://wp-types.com/documentation/customizing-sites-using-php/
 * It is based on the original Twenty Sixteen theme's template part file for displaying single items.
 * It features additional code to render custom fields created with the Types plugin and display contents of child posts.
 *
 * The template part for displaying Author (Writer) post content
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
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


<!-- TYPES TIP: Custom call to Types function to render a custom field "Author Image" -->		
	<?php echo types_render_field( "author-image", array( "size" => "thumbnail" )); ?>

	
	<div class="entry-content">

<!-- Standard Twenty Sixteen content output -->	
		<?php
			/* translators: %s: Name of current post */
			the_content( sprintf(
				__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
				get_the_title()
			) );
			?>


<!-- TYPES TIP: Custom section to display a list of child posts ("Books"), belonging to the currently displayed parent post ("Author") -->		
			<h4>Books from this author</h4>
			
			<?php
			// Using the Types function to load the contents of child posts and then starting a custom loop to output them
			$child_posts = types_child_posts("book"); // Load the contents of related posts in the array
			foreach ($child_posts as $child_post) { // Loop through each of the child post in the array
			?>
			
			<div class="book-listing">
				<h5><?php echo $child_post->post_title; // Display the post title of a current child post ?></h5>
				<?php echo types_render_field( "book-cover", array( "id"=> "$child_post->ID", "size" => "thumbnail" )); // Display the image coming from a custom field of the current child post ?>
			</div>
			
			
<!-- Continuation of the standard Twenty Sixteen content output -->	
			<?php }
			wp_link_pages( array(
				'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentysixteen' ) . '</span>',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
				'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>%',
				'separator'   => '<span class="screen-reader-text">, </span>',
			) );
		?>
		
	</div><!-- .entry-content -->
	
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

