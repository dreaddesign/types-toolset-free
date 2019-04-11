<?php
/**
 * This example file is a part of the Types plugin online documentation found at: https://wp-types.com/documentation/customizing-sites-using-php/
 * It is based on the original Twenty Sixteen theme's template part file for displaying single items.
 * It features additional code to render custom fields created with the Types plugin and some parent post contents.
 *
 * Please note that the names of the custom fields are for example purposes only and will not work in your site as-is. You need to edit this example according to the documentation mentioned above.
 *
 * The template part for displaying Book post content
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
	
	
<!-- TYPES TIP: Custom call to Types function to render a custom image field "Book Cover" -->		
	<?php echo types_render_field( "book-cover", array( "size" => "thumbnail" ) ); ?>

	<div class="entry-content">
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

		
<!-- TYPES TIP: Custom call to Types function to render a custom field "Number of pages" -->
		<p><strong>Number of pages:</strong> <?php echo types_render_field( "number-of-pages"); ?></p>
		

<!-- TYPES TIP: Custom code to load and display contents of the parent ("Writer") post -->
		<?php 
			// Get the ID of the parent post, which belongs to the "Writer" post type
			$writer_id = wpcf_pr_post_get_belongs( get_the_ID(), 'writer' );

			// Get all the parent (writer) post data
			$writer_post = get_post( $writer_id );
    
			// Get the title of the parent (writer) post
			$writer_name = $writer_post->post_title;

			// Get the contents of the parent (writer) post
			$writer_content = $writer_post->post_content;
		?>
         <!-- After loading the data of the parent post we display it using our custom HTML structure -->		
		<div class="writer">
			<h5>Author: <?php echo $writer_name; ?></h5>
			<div class="writer-description"><?php echo $writer_content; ?></div>
		
		<!-- We can use the "post_id" argument with the types_render_field function to get a custom field of any post -->		
			<div class="writer-photo"><?php echo types_render_field( 'author-image', array( 'post_id' => $writer_id, 'size' => 'thumbnail' ) ); 
    ?></div>
	
			<br class="clear">
		</div>
		 
	
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

