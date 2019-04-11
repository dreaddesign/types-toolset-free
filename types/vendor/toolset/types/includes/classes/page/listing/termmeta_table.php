<?php

/**
 * Listing table for the Term Field Groups page.
 */
class WPCF_Page_Listing_Termmeta_Table extends WPCF_Page_Listing_Table {

	const BULK_ACTION_FIELD_NAME = 'wpcf_cf_ids';

	function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'term field group', 'wpcf' ),     //singular name of the listed records
				'plural' => __( 'term field groups', 'wpcf' ),    //plural name of the listed records
				'ajax' => true
			)
		);
	}


	function prepare_items() {

		$per_page = $this->get_items_per_page( WPCF_Page_Listing_Termmeta::SCREEN_OPTION_PER_PAGE_NAME, WPCF_Page_Listing_Termmeta::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$search_string = isset( $_POST['s'] ) ? mb_strtolower( trim( $_POST['s'] ) ) : null;
		
		$query_args    = array(
			'orderby' => sanitize_text_field( wpcf_getget( 'orderby', 'post_title' ) ),
			'order' => sanitize_text_field( wpcf_getget( 'order', 'asc' ) ),
			'types_search' => $search_string
		);

		$groups = Types_Field_Group_Term_Factory::get_instance()->query_groups( $query_args );

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $groups );

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$groups = array_slice( $groups, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $groups;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}


	/**
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'title' => __( 'Name', 'wpcf' ),
			'description' => __( 'Description', 'wpcf' ),
			'status' => __( 'Active', 'wpcf' ),
			'taxonomies' => __( 'Taxonomies', 'wpcf' ),
		);

		/* todo
		if ( !WPCF_Roles::user_can_create('custom-field') ) {
			unset($columns['cb']);
		}*/

		return $columns;
	}


	/**
	 * @return array An associative array containing all the columns that should be sortable:
	 *     'slugs'=>array('data_values',bool)
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'title' => array( 'post_title', true ),
			'description' => array( 'post_content', false ),
			'status' => array( 'post_status', false )
		);

		return $sortable_columns;
	}


	/**
	 * @param $item Types_Field_Group_Term
	 *
	 * @return string
	 */
	function column_description( $item ) {
		return stripslashes( $item->get_description() );
	}


	/**
	 * @param $item Types_Field_Group_Term
	 *
	 * @return string
	 */
	function column_taxonomies( $item ) {
		$taxonomies = $item->get_associated_taxonomies();
		if ( empty( $taxonomies ) ) {
			$output = __( 'Any', 'wpcf' );
		} else {
			$taxonomy_labels = array();
			foreach( $taxonomies as $taxonomy_slug ) {
				$taxonomy_labels[] = Types_Utils::taxonomy_slug_to_label( $taxonomy_slug );
			}
			$output = implode( ', ', $taxonomy_labels );
		}

		return $output;
	}


	/**
	 * @param $item Types_Field_Group_Term
	 *
	 * @return string
	 */
	function column_status( $item ) {
		return ( $item->is_active() ? __( 'Yes', 'wpcf' ) : __( 'No', 'wpcf' ) );
	}


	/**
	 * @param Types_Field_Group_Term $item
	 * 
*@return string
	 */
	function column_title( $item ) {
		// todo use icl_post_link here
		$edit_url = esc_url(
			add_query_arg(
				array( 'page' => WPCF_Page_Edit_Termmeta::PAGE_NAME, 'group_id' => $item->get_id() ),
				admin_url( 'admin.php' )
			)
		);

		// todo this is working, but really ugly
		// Trick WPCF_Roles into believing it has something to do with an old-style field group definition.
		if ( WPCF_Roles::user_can_edit( 'term-field', array( 'id' => $item->get_id(), WPCF_AUTHOR => $item->get_author() ) ) ) {

			/** @noinspection HtmlUnknownTarget */
			$edit_link = sprintf(
				'<a href="%s">%s</a>',
				$edit_url,
				__('Edit', 'wpcf')
			);

			$status_link = ( $item->is_active()
				? wpcf_admin_fields_get_ajax_deactivation_link( $item->get_id(), 'deactivate_term_group' )
				: wpcf_admin_fields_get_ajax_activation_link( $item->get_id(), 'activate_term_group' )
			);

			$delete_link = sprintf(
				'<a href="%s" class="submitdelete wpcf-ajax-link" id="wpcf-list-delete-%d"">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'action' => 'wpcf_ajax',
							'wpcf_action' => 'delete_term_group',
							'group_id' => $item->get_id(),
							'wpcf_ajax_update' => 'wpcf_list_ajax_response_'.$item->get_id(),
							'_wpnonce' => wp_create_nonce('delete_term_group'),
							'wpcf_warning' => urlencode(__('Are you sure?', 'wpcf')),
						),
						admin_url('admin-ajax.php')
					)
				),
				$item->get_id(),
				__('Delete', 'wpcf')
			);

			$actions = array(
				'edit' => $edit_link,
				'status' => $status_link,
				'delete' => $delete_link
			);
		} else {
			$actions = array();
		}

		/** @noinspection HtmlUnknownTarget */
		return sprintf(
			'<strong><a href="%s" class="row-title">%s</strong>%s',
			$edit_url,
			$item->get_name(),
			$this->row_actions($actions)
		);
	}

	/**
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param Types_Field_Group_Term $item A singular item
	 * 
*@return string
	 */
	function column_cb( $item ) {
		if ( WPCF_Roles::user_can_edit( 'term-field', array( 'id' => $item->get_id() ) ) ) {
			return sprintf(
				'<input type="checkbox" name="%s[]" value="%s" />',
				self::BULK_ACTION_FIELD_NAME,
				$item->get_id()
			);
		} else {
			return '';
		}
	}



	function get_bulk_actions() {
		$actions = array();

		if ( WPCF_Roles::user_can_create( 'term-field' ) ) {
			$actions = array(
				'activate' => __( 'Activate', 'wpcf' ),
				'deactivate' => __( 'Deactivate', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			);
		}

		return $actions;
	}



	function process_bulk_action() {
		global $wpdb;

		$action = $this->current_action();
		if( empty( $action ) ) {
			return;
		}

		$nonce = wpcf_getpost( '_wpnonce', null );
		if ( ! wp_verify_nonce( $nonce, WPCF_Page_Listing_Termmeta::BULK_ACTION_NONCE ) ) {
			die( 'Security check' );
		}

		$selected_field_group_ids = wpcf_getpost( self::BULK_ACTION_FIELD_NAME, array() );
		if( empty( $selected_field_group_ids ) ) {
			return;
		}

		foreach ( $selected_field_group_ids as $field_group_id ) {

			$field_group_id = (int) $field_group_id;

			if ( ! WPCF_Roles::user_can_edit( 'term-field', array( 'id' => $field_group_id ) ) ) {
				continue;
			}

			switch ( $action ) {
				case 'delete':
					$wpdb->delete(
						$wpdb->posts,
						array( 'ID' => $field_group_id, 'post_type' => Types_Field_Group_Term::POST_TYPE ),
						array( '%d', '%s' )
					);
					break;
				case 'deactivate':
					$wpdb->update(
						$wpdb->posts,
						array( 'post_status' => 'draft' ),
						array( 'ID' => $field_group_id, 'post_type' => Types_Field_Group_Term::POST_TYPE ),
						array( '%s' ),
						array( '%d', '%s' )
					);
					break;
				case 'activate':
					$wpdb->update(
						$wpdb->posts,
						array( 'post_status' => 'publish' ),
						array( 'ID' => $field_group_id ),
						array( '%s' ),
						array( '%d' )
					);
					break;
			}
		}
		wp_cache_delete( md5( 'group::_get_group' . Types_Field_Group_Term::POST_TYPE ), 'types_cache_groups' );

	}


	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @param Types_Field_Group_Term $item The current item
	 */
	public function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? 'alternate' : '' );

		$status_class = ( $item->is_active() ? 'status-active' : 'status-inactive' );

		printf( '<tr class="%s %s">', $row_class, $status_class );

		$this->single_row_columns( $item );

		echo '</tr>';
	}


	public function no_items() {
		if ( isset( $_POST['s'] ) ) {
			_e( 'No term field groups found.', 'wpcf' );

			return;
		}

		printf(
			'<p>%s</p>',
			__( 'To use term fields, please create a group to hold them.', 'wpcf' )
		);

		// todo use icl_post_link here

		printf(
			'<a class="button-primary" href="%s">%s</a>',
			esc_url( add_query_arg( array( 'page' => WPCF_Page_Edit_Termmeta::PAGE_NAME ), admin_url( 'admin.php' ) ) ),
			__( 'Add New Group', 'wpcf' )
		);
	}
}
