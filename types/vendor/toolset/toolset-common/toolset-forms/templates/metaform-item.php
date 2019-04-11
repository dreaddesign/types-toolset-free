<?php
/**
 *
 *
 */
$has_output_bootstrap = (isset( $cfg['attribute']['output'] ) && $cfg['attribute']['output'] == 'bootstrap');

if ( is_admin() ) {
	?>
    <div class="js-wpt-field-item wpt-field-item">
		<?php echo $out; ?>
		<?php if ( @$cfg['repetitive'] ): ?>
            <div class="wpt-repctl">
                <div class="js-wpt-repdrag wpt-repdrag">&nbsp;</div>
                <a class="js-wpt-repdelete button button-small" data-wpt-type="<?php echo $cfg['type']; ?>" data-wpt-id="<?php echo $cfg['id']; ?>"><?php apply_filters( 'toolset_button_delete_repetition_text', printf( __( 'Delete %s', 'wpv-views' ), strtolower( $cfg['title'] ) ), $cfg ); ?></a>
            </div>
		<?php endif; ?>
    </div>
	<?php
} else {
	$toolset_repdrag_image = '';
	$button_extra_classnames = '';
	if ( $has_output_bootstrap ) {
		if ( $cfg['repetitive'] ) {
			echo '<div class="wpt-repctl wpt-repctl-flex">';
			echo '<div class="wpt-repetitive-controls">';
			echo '<span role="button" class="js-wpt-repdrag wpt-repdrag dashicons dashicons-move"></span>';
			$str = sprintf( __( '%s repetition', 'wpv-views' ), $cfg['title'] );
			echo '<span role="button" class="js-wpt-repdelete wpt-repdelete dashicons-before dashicons-trash" title="';
			echo apply_filters( 'toolset_button_delete_repetition_text', esc_attr( __( 'Delete', 'wpv-views' ) ) . " " . esc_attr( $str ), $cfg );
			echo '"></span>';
			echo '</div>';
			echo '<div class="wpt-repetitive-field">';
		}
		echo $out;
		if ( $cfg['repetitive'] ) {
			echo '</div>';
			echo '</div>';
		}
	} else {
		if ( $cfg['repetitive'] ) {
			$toolset_repdrag_image = apply_filters( 'wptoolset_filter_wptoolset_repdrag_image', $toolset_repdrag_image );
			echo '<div class="wpt-repctl">';
			echo '<span class="js-wpt-repdrag wpt-repdrag"><img class="wpv-repdrag-image" src="' . $toolset_repdrag_image . '" /></span>';
		}
		echo $out;
		if ( $cfg['repetitive'] ) {
			if ( ! $has_output_bootstrap && array_key_exists( 'use_bootstrap', $cfg ) && $cfg['use_bootstrap'] ) {
				$button_extra_classnames = ' btn btn-default btn-sm';
			}
			$str = sprintf( __( '%s repetition', 'wpv-views' ), $cfg['title'] );
			echo '<input type="button" href="#" class="js-wpt-repdelete wpt-repdelete' . $button_extra_classnames . '" value="';
			echo apply_filters( 'toolset_button_delete_repetition_text', esc_attr( __( 'Delete', 'wpv-views' ) ) . " " . esc_attr( $str ), $cfg );
			echo '" />';
			echo '</div>';
		}
	}
}
