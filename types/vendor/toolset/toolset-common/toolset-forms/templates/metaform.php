<?php
/**
 *
 *
 */
$has_output_bootstrap = (isset( $cfg['attribute']['output'] ) && $cfg['attribute']['output'] == 'bootstrap');

if ( is_admin() ) {
    $child_div_classes = array('js-wpt-field-items');
	if (  ! $has_output_bootstrap && $cfg['use_bootstrap'] && in_array( $cfg['type'], array( 'date', 'select' ) ) ) {
		$child_div_classes[] = 'form-inline';
	}
	$field_additional_classes = apply_filters('toolset_field_additional_classes', '', $cfg);
    ?><div class="js-wpt-field wpt-field js-wpt-<?php echo $cfg['type']; ?> wpt-<?php echo $cfg['type']; ?><?php if ( @$cfg['repetitive'] ) echo ' js-wpt-repetitive wpt-repetitive'; ?><?php echo $field_additional_classes; ?>" data-wpt-type="<?php echo $cfg['type']; ?>" data-wpt-id="<?php echo $cfg['id']; ?>">
    <div class="<?php echo implode( ' ', $child_div_classes ); ?>">
		<?php
		foreach ( $html as $out ):
			include 'metaform-item.php';
		endforeach;
		?>
		<?php if ( @$cfg['repetitive'] ): ?>
            <a href="#" class="js-wpt-repadd wpt-repadd button button-small button-primary-toolset" data-wpt-type="<?php echo $cfg['type']; ?>" data-wpt-id="<?php echo $cfg['id']; ?>"><?php echo apply_filters( 'toolset_button_add_repetition_text', __( 'Add new', 'wpv-views' ), $cfg ); ?></a>
		<?php endif; ?>
    </div>
    </div>
	<?php
} else {
    // CHeck if we need a wrapper
    $types_without_wrapper = array('submit', 'hidden');
    $needs_wrapper = true;
    if ( isset( $cfg['type'] ) && in_array( $cfg['type'], $types_without_wrapper ) ) {
        $needs_wrapper = false;
    }
    /**
	 * Get the field extra classnames, coming from checks in validation and conditionals.
	 *
	 * Used to adjust the data-initial-conditional attribute by getting the field extra classnames 
	 * and checking whether it contains a "wpt-hidden" bit.
	 *
	 * @note This might need a deeper review, since we are getting here some classnames that we ditch entirely
	 *       Wy do we do it here anyway? Just to know whether this field has a condition on it?
	 *       There are easier ways to do so, and much mor straight forward, without strpos a classname that we do not even use here.
	 *       In fact, all those classnames we are getting are then added and removed in JS.
	 *
	 * @param string     Th classnames for this field
	 * @param array $ctg The field settings
	 *
	 * @since 2.4.0
	 * @since 1.9.0 CRED
	 */
    $conditional_classes = apply_filters('toolset_field_additional_classes', '', $cfg);
    if ( strpos( $conditional_classes, 'wpt-hidden' ) === false ) {
        $conditional_classes = '';
    } else {
        $conditional_classes = 'true';
    }
    // Adjust classnames for container and buttons
    $button_extra_classnames = '';
    $container_classes = '';
	if ( ! $has_output_bootstrap && array_key_exists( 'use_bootstrap', $cfg ) && $cfg['use_bootstrap'] ) {
		$button_extra_classnames .= ' btn btn-default btn-sm';
		$container_classes .= ' form-group';
	}
    if ( array_key_exists( 'repetitive', $cfg ) ) {
        $container_classes .= ' js-wpt-repetitive wpt-repetitive';
    }
    // Render
    if ( $needs_wrapper ) {
        $identifier = $cfg['type'] . '-' . $cfg['name'];
        echo '<div class="js-wpt-field-items' . $container_classes . '" data-initial-conditional="' . $conditional_classes . '" data-item_name="' . $identifier . '">';
    }
    foreach ( $html as $out ) {
        include 'metaform-item.php';
    }
    if ( $cfg['repetitive'] ) {
        if ( $has_output_bootstrap ) {
			echo '<a role="button" class="js-wpt-repadd wpt-repadd dashicons-before dashicons-plus-alt" data-wpt-type="' . $cfg['type'] . '" data-wpt-id="' . $cfg['id'] . '">' . apply_filters( 'toolset_button_add_repetition_text', esc_attr( __( 'Add new', 'wpv-views' ) ), $cfg ) . '</a>';
		} else {
			echo '<input type="button" class="js-wpt-repadd wpt-repadd' . $button_extra_classnames . '" data-wpt-type="' . $cfg['type'] . '" data-wpt-id="' . $cfg['id'] . '" value="';
			echo apply_filters( 'toolset_button_add_repetition_text', esc_attr( sprintf( __( 'Add new %s', 'wpv-views' ), $cfg['title'] ) ), $cfg );
			echo '" />';
		}
    }
    if ( $needs_wrapper ) {
        echo '</div>';
    }
}
