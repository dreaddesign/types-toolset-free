<?php
/*
 * Fields and groups list functions
 */

/**
 * Renders 'widefat' table.
 */
function wpcf_admin_usermeta_list()
{
    include_once dirname(__FILE__).'/classes/class.types.admin.usermeta.groups.list.table.php';
    //Create an instance of our package class...
    $listTable = new Types_Admin_Usermeta_Groups_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $listTable->prepare_items();
    ?>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="usermeta-filter" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php $listTable->search_box(__('Search User Field Groups', 'wpcf'), 'search_id'); ?>
            <!-- Now we can render the completed list table -->
            <?php $listTable->display() ?>
        </form>
    <?php
    do_action('wpcf_groups_list_table_after');
}

/**
 * Action after group list.
 *
 * This access allow to add something after group list
 *
 * @since 1.8.0
 *
 */
add_action('wpcf_admin_footer_wpcf-um', 'wpcf_admin_fields_list_metabox_to_custom_fields_control');

/**
 * Show link to Control Custom Field
 *
 * @since 1.8.0
 *
 */
function wpcf_admin_fields_list_metabox_to_custom_fields_control()
{
    $form['table-1-open'] = array(
        '#type' => 'markup',
        '#markup' => '<table class="wpcf-types-form-table widefat js-wpcf-slugize-container"><thead><tr><th>' . __( 'User Field Control', 'wpcf' ) . '</th></tr></thead><tbody>',
        '_builtin' => true,
    );
    $form['table-row-1-open'] = array(
        '#type' => 'markup',
        '#markup' => '<tr><td>',
        '_builtin' => true,
    );

    $form['table-row-1-content-1'] = array(
        '#type' => 'markup',
        '#markup' => '<p>'.__('You can control User Fields by removing them from the groups, changing type or just deleting.', 'wpcf'),
        '_builtin' => true,
    );

    $form['table-row-1-content-2'] = array(
        '#type' => 'markup',
        '#markup' => sprintf(
            ' <a class="button" href="%s">%s</a></p>',
	        Types_Page_Field_Control::get_page_url( Types_Field_Utils::DOMAIN_USERS ),
            __('User Field Control', 'wpcf')
        ),
        '_builtin' => true,
    );

    $form['table-row-1-close'] = array(
        '#type' => 'markup',
        '#markup' => '</td></tr>',
        '_builtin' => true,
    );
    $form['table-1-close'] = array(
        '#type' => 'markup',
        '#markup' => '</tbody></table>',
        '_builtin' => true,
    );
    $form = wpcf_form( __FUNCTION__, $form );
    echo $form->renderForm();

}

