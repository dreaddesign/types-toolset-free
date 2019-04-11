<?php
/*
 * entry editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

?>

<div data-bind="template: {name:'tpl-types-modal-entry'}"></div>

<!--TYPES MODAL entry-->
<script id="tpl-types-modal-entry" type="text/html">
<?php
sprintf(
    '<input type="hidden" name="wpcf-entry-display-default" value="%s"/>',
    esc_attr($data['default'])
);
?>
<div class="fieldset">
    <ul class="form-inline">
<?php
foreach ($data['options'] as $key => $field_data ) {
    $id = esc_attr(sprintf('wpcf-entry-%s', $key));
    echo '<li>';
    printf(
        '<input id="%s" type="radio" name="display" value="%s" %s />',
        $id,
        esc_attr($key),
        $key == $data['default']? 'checked="checked"':''
    );
    printf(
        '<label for="%s">%s</label>',
        $id,
        $field_data['label']
    );
    echo '</li>';
}
?>
    </ul>
</div>
</script><!--END TYPES MODAL entry-->
