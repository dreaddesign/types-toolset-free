<?php
/**
 * This example file is a part of the Types plugin online documentation found at: https://wp-types.com/documentation/customizing-sites-using-php/
 * It is based on the original Twenty Sixteen theme's template part file for displaying single items.
 * It features additional code to render custom fields created with the Types plugin, including an image gallery created with images from the repeating image field.
 *
 * Please note that the names of the custom fields are for example purposes only and will not work in your site as-is. You need to edit this example according to the documentation mentioned above.
 *
 * The template part for displaying single House posts
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

	
<!-- TYPES TIP: Custom call to Types function to render a custom image field "House Photos". We used the "index" attribute to display only the first, representative photo of a house. -->	
	<?php echo types_render_field( "house-photos", array( "size"=>"thumbnail", "index" => "0" ) ); ?>

	
	<div class="entry-content">

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
		
		
		
<!-- TYPES TIP: Custom call to Types function to render a custom field "Size" belonging to a "Houses" post type -->			
		<p><strong>Size:</strong> <?php echo types_render_field( "size" ); ?></p>
		
<!-- TYPES TIP: Custom call to Types function to render a custom field "Area" belonging to a "Houses" post type -->			
		<p><strong>Area:</strong> <?php echo types_render_field( "area" ); ?></p>
		
<!-- TYPES TIP: Custom code to load Types function and render a REPEATING custom field "House Photos" but with each output thumbnail image also linking to the full-size image file (for lightbox effect, for example). -->			
		<p><strong>Property Photos</strong></p>
		
		<?php 
		// Do nothing if we don't have Types.
		if( apply_filters( 'types_is_active', false ) ) {
    
			$output = '';

			// ID of the current post
			$post_id = get_the_ID();

			// Slug of a Types repeating image field, without the "wpcf-" prefix.
			$field_slug = 'house-photos'; // TODO set the field slug you want to display

			// Parameters that define the field
			$field_definition = wpcf_fields_get_field_by_slug( $field_slug );
			if( ! empty( $field_definition ) ) {
        
				// Get the raw field data.
				$images = get_post_meta( $post_id, "wpcf-{$field_slug}" );

				foreach( $images as $image_index => $image ) {

					// Parameters for the Types field rendering mechanism.
					$image_parameters = array(
						'proportional' => 'true',
						'url' => 'true',
						'field_value' => $image
					);

					// Get an image of specific (maximum) proportions.
					// NOTE: Update image size to your needs
					$thumbnail_parameters = array_merge( $image_parameters, array( 'width' => '200', 'height' => '200' ) );
					$thumbnail_url = types_render_field_single( $field_definition, $thumbnail_parameters, null, '', $image_index );

					// Get the image in full size.
					$fullsize_parameters = array_merge( $image_parameters, array( 'size' => 'full' ) );
					$fullsize_url = types_render_field_single( $field_definition, $fullsize_parameters, null, '', $image_index );

					// Append the markup (a thumbnail linking to the full image) to existing content.
					// NOTE: Customize the output to your needs
					$output .= sprintf(
						'<div class="img"><a href=" '. $fullsize_url .' "><img src=" '. $thumbnail_url .' "></a></div>'
					);
				}
			}

			echo $output;
		}
	?>
				
	</div><!-- .entry-content -->
	
<!-- Continue the standard Twenty Sixteen content footer output -->		
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