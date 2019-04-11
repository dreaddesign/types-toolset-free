<?php

class WPCF_WPViews {

	static $types_shortcodes_assets_added = false;


	/**
	 * Init called from WPCF_Loader.
	 */
	public static function init() {
		add_action( 'load-post.php', array(
			'WPCF_WPViews',
			'wpcf_wpv_admin_post_add_postmeta_usermeta_to_editor_js'
		) );
		add_action( 'load-post-new.php', array(
			'WPCF_WPViews',
			'wpcf_wpv_admin_post_add_postmeta_usermeta_to_editor_js'
		) );
		add_action( 'views_edit_screen', array( 'WPCF_WPViews', 'editScreenInit' ) );
		add_action( 'layouts_edit_screen', array( 'WPCF_WPViews', 'editScreenInit' ) );
		add_action( 'current_screen', array( 'WPCF_WPViews', 'include_types_fields_on_views_dialog_on_demand' ) );
		add_action( 'views_ct_inline_editor', array( 'WPCF_WPViews', 'addEditorDropdownFilter' ) );
		add_action( 'wpv_action_wpv_add_types_postmeta_usermeta_to_editor_menus', array(
			'WPCF_WPViews',
			'addEditorDropdownFilter'
		) );

		WPCF_WPViews::register_types_shortcodes_dialog_groups();
		add_action( 'wpv_action_wpv_enforce_shortcodes_assets', array(
			'WPCF_WPViews',
			'enforce_types_shortcodes_assets'
		) );
	}


	/**
	 * Register the Types meta fields groups into the Fields and Views dialog for Views.
	 *
	 * Depending on the current admin page, we register different meta types (post, term, user),
	 * and also only for some targets (posts, taxonomy, users) et by the Fields and Views button itself.
	 *
	 * @note We keep the old registering mechanism too as it is needed by the Views Loop Wizard,
	 * and for backwards compatibility.
	 *
	 * @since 2.2.7
	 */
	public static function register_types_shortcodes_dialog_groups() {

		global $pagenow;

		if (
			$pagenow == 'admin.php'
			&& isset( $_GET['page'] )
			&& in_array( $_GET['page'], array(
				'views-editor',
				'ct-editor',
				'view-archives-editor',
				'dd_layouts_edit'
			) )
		) {
			// We are on a Views object edit page, so add all Types postmeta groups and usermeta groups
			// We can also be on a Layouts object edit page, so we add all postmeta and usermeta groups too
			WPCF_WPViews::register_types_postmeta_shortcodes_dialog_groups();
			WPCF_WPViews::register_types_termmeta_shortcodes_dialog_groups();
			WPCF_WPViews::register_types_usermeta_shortcodes_dialog_groups();
		} else if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			// We are on a post edit page, add the relevant(?) postmeta and usermeta groups
			WPCF_WPViews::register_types_postmeta_shortcodes_dialog_groups();
			WPCF_WPViews::register_types_usermeta_shortcodes_dialog_groups();
		} else if ( in_array( $pagenow, array( 'edit-tags.php', 'term.php' ) ) ) {
			WPCF_WPViews::register_types_termmeta_shortcodes_dialog_groups();
		} else if ( in_array( $pagenow, array( 'profile.php', 'user-new.php', 'user-edit.php' ) ) ) {
			WPCF_WPViews::register_types_usermeta_shortcodes_dialog_groups();
		} else {
			WPCF_WPViews::register_types_postmeta_shortcodes_dialog_groups();
			WPCF_WPViews::register_types_usermeta_shortcodes_dialog_groups();
		}

	}


	/**
	 * Enforce the Types shortcodes assets when registered in a Fields and Views dialog.
	 *
	 * @note Only enforce this one per requst, by using the $types_shortcodes_assets_added boolean flag.
	 *
	 * @since 2.2.7
	 */
	public static function enforce_types_shortcodes_assets() {
		if ( WPCF_WPViews::$types_shortcodes_assets_added ) {
			return;
		}
		if ( ! is_admin() ) {
			$types_fields_shortcodes_settings = array(
				'wpnonce' => wp_create_nonce( '_typesnonce' ),
				'validation' => array()
			);
			echo '
			<style>
				#colorbox.js-wpcf-colorbox-with-iframe { z-index: 110000 !important; }
			</style>';
			wp_localize_script( 'types', 'types', $types_fields_shortcodes_settings );
		}

		wp_enqueue_script( 'types' );
		wp_enqueue_script( 'types-wp-views' );
		wp_enqueue_script( 'toolset-colorbox' );
		wp_enqueue_style( 'toolset-colorbox' );
		WPCF_WPViews::$types_shortcodes_assets_added = true;
	}


	/**
	 * Register postmeta fields groups in the relevant Fields and Views dialogs.
	 * Should work in backend and frontend.
	 *
	 * @since 2.2.7
	 */
	public static function register_types_postmeta_shortcodes_dialog_groups() {
		$current_post = WPCF_WPViews::get_current_post();
		$groups = wpcf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'group_active' );
		$all_post_types = implode( ' ', get_post_types( array( 'public' => true ) ) );
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$fields = wpcf_admin_fields_get_fields_by_group( $group['id'], 'slug', true, false, true );
				if ( ! empty( $fields ) ) {

					$group_id = 'types-postmeta-' . $group['id'];
					$group_data = array(
						'name' => $group['name'],
						'target' => array( 'posts' ),
						'fields' => array()
					);

					foreach ( $fields as $field ) {
						$group_data['fields'][ $field['id'] ] = array(
							'name' => stripslashes( $field['name'] ),
							'shortcode' => trim( wpcf_fields_get_shortcode( $field ), '[]' ),
							'callback' => 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'postmeta\', ' . $current_post->ID . ')'
						);
					}

					do_action( 'wpv_action_wpv_register_dialog_group', $group_id, $group_data );
				}
			}
		}
	}


	/**
	 * Register termmeta fields groups in the relevant Fields and Views dialogs.
	 * Should work in backend and frontend.
	 *
	 * @todo set the target to posts when on a WPA edit page
	 *
	 * @since 2.2.7
	 */
	public static function register_types_termmeta_shortcodes_dialog_groups() {
		//Get types groups and fields
		global $pagenow;
		$groups = wpcf_admin_fields_get_groups( TYPES_TERM_META_FIELD_GROUP_CPT_NAME, 'group_active' );
		$add = array();
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group_id => $group ) {
				$fields = wpcf_admin_fields_get_fields_by_group( $group['id'], 'slug', true, false, true, TYPES_TERM_META_FIELD_GROUP_CPT_NAME, 'wpcf-termmeta' );
				if ( ! empty( $fields ) ) {

					$group_id_taxonomy = 'types-termmeta-taxonomy-' . $group['id'];
					$group_data_taxonomy = array(
						'name' => $group['name'],
						'target' => array( 'taxonomy' ),
						'fields' => array()
					);
					
					$group_id_archive = 'types-termmeta-archive-' . $group['id'];
					$group_data_archive = array(
						'name' => sprintf( __( '%s (Termmeta fields for term archives)', 'wpcf' ), $group['name'] ),
						'target' => array( 'posts' ),
						'fields' => array()
					);

					foreach ( $fields as $field_id => $field ) {
						$group_data_taxonomy['fields'][ $field['id'] ] = array(
							'name' => stripslashes( $field['name'] ),
							'shortcode' => 'types termmeta="' . $field['id'] . '"][/types',
							'callback' => 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'views-termmeta\', -1)'
						);
						$group_data_archive['fields'][ $field['id'] ] = array(
							'name' => stripslashes( $field['name'] ),
							'shortcode' => 'types termmeta="' . $field['id'] . '"][/types',
							'callback' => 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'views-termmeta\', -1)'
						);
					}

					do_action( 'wpv_action_wpv_register_dialog_group', $group_id_taxonomy, $group_data_taxonomy );

					if (
						$pagenow == 'admin.php'
						&& isset( $_GET['page'] )
						&& in_array( $_GET['page'], array(
							'view-archives-editor',
							'dd_layouts_edit'
						) )
					) {
						do_action( 'wpv_action_wpv_register_dialog_group', $group_id_archive, $group_data_archive );
					}
					
				}
			}
		}
	}


	/**
	 * Register usermeta fields groups in the relevant Fields and Views dialogs.
	 * Should work in backend and frontend.
	 *
	 * @note We register each group twice for post-based edit pags and for user-based edit pages,
	 * including an "(Usermeta fields)" group name suffix in the first case.
	 *
	 * @since 2.2.7
	 */
	public static function register_types_usermeta_shortcodes_dialog_groups() {
		global $wpcf;
		$current_post = WPCF_WPViews::get_current_post();
		$groups = wpcf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'group_active' );
		$user_id = wpcf_usermeta_get_user();
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group_id => $group ) {
				$group_name = sprintf( __( '%s (Usermeta fields)', 'wpcf' ), $group['name'] );
				$fields = wpcf_admin_fields_get_fields_by_group( $group['id'], 'slug', true, false, true, TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta' );
				if ( ! empty( $fields ) ) {

					$group_id_generic = 'types-usermeta-generic-' . $group['id'];
					$group_data_generic = array(
						'name' => sprintf( __( '%s (Usermeta fields)', 'wpcf' ), $group['name'] ),
						'target' => array( 'posts' ),
						'fields' => array()
					);

					$group_id_specific = 'types-usermeta-specific-' . $group['id'];
					$group_data_specific = array(
						'name' => $group['name'],
						'target' => array( 'users' ),
						'fields' => array()
					);

					foreach ( $fields as $field_id => $field ) {

						$group_data_generic['fields'][ $field['id'] ] = array(
							'name' => stripslashes( $field['name'] ),
							'shortcode' => trim( wpcf_usermeta_get_shortcode( $field ), '[]' ),
							'callback' => 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'usermeta\', ' . $current_post->ID . ')'
						);

						$group_data_specific['fields'][ $field['id'] ] = array(
							'name' => stripslashes( $field['name'] ),
							'shortcode' => trim( wpcf_usermeta_get_shortcode( $field ), '[]' ),
							'callback' => 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'views-usermeta\', ' . $current_post->ID . ')'
						);

					}

					do_action( 'wpv_action_wpv_register_dialog_group', $group_id_generic, $group_data_generic );
					do_action( 'wpv_action_wpv_register_dialog_group', $group_id_specific, $group_data_specific );

				}
			}
		}
	}


	/**
	 * Get the currently edited post eithwe in backend or frontend, if any, or a dummy object otherwise.
	 *
	 * In the backend, uses the wpcf_filter_wpcf_admin_get_current_edited_post which gets the current post
	 * based on GET, POST parameters and also the global $post_id.
	 * In the frontend, uses the global $post.
	 *
	 * @return WP_Post|object
	 *
	 * @since 2.2.7
	 */
	public static function get_current_post() {

		if ( is_admin() ) {
			$current_post = apply_filters( 'wpcf_filter_wpcf_admin_get_current_edited_post', null );
			if ( ! $current_post ) {
				$current_post = (object) array( 'ID' => - 1 );
			}
		} else {
			global $post;
			if ( $post instanceof WP_Post ) {
				$current_post = $post;
			} else {
				$current_post = (object) array( 'ID' => - 1 );
			}
		}

		return $current_post;

	}


	/**
	 * Include the Types custom fields and usermeta fields, along with the needed scripts to mange them,
	 * in the Fields and Views dialog, on demand.
	 *
	 * @param $current_screen
	 */
	public static function include_types_fields_on_views_dialog_on_demand( $current_screen ) {

		/**
		 * wpcf_filter_force_include_types_fields_on_views_dialog
		 *
		 * Force include the Types fields and usermeta fields as groups on the Fields and Views popup.
		 * This adds assets as well as menu items.
		 * Note that this happens on current_screen so this filter need to be added before that.
		 *
		 * @param (bool) Whether to include those items or not.
		 * @param $current_screen (object) The current WP_Screen object.
		 *
		 * @since 1.7
		 */
		$force_include_types_in_fields_and_views_dialog = apply_filters( 'wpcf_filter_force_include_types_fields_on_views_dialog', false, $current_screen );

		if ( $force_include_types_in_fields_and_views_dialog ) {
			self::editScreenInit();
		}
	}


	/**
	 * Actions for Views edit screens.
	 */
	public static function editScreenInit() {
		if ( ! wp_script_is( 'types', 'enqueued' ) ) {
			wp_enqueue_script( 'types' );
		}
		if ( ! wp_script_is( 'types-wp-views', 'enqueued' ) ) {
			wp_enqueue_script( 'types-wp-views' );
		}
		if ( ! wp_script_is( 'toolset-colorbox', 'enqueued' ) ) {
			wp_enqueue_script( 'toolset-colorbox' );
		}
		if ( ! wp_style_is( 'toolset-colorbox', 'enqueued' ) ) {
			wp_enqueue_style( 'toolset-colorbox' );
		}
		self::addEditorDropdownFilter();
	}


	/**
	 * Adds filtering editor dropdown items.
	 */
	public static function addEditorDropdownFilter() {
		add_filter( 'editor_addon_menus_wpv-views',
			array( 'WPCF_WPViews', 'editorDropdownFilter' ) );
		add_filter( 'editor_addon_menus_wpv-views',
			'wpcf_admin_post_add_usermeta_to_editor_js', 20 );
	}


	public static function wpcf_wpv_admin_post_add_postmeta_usermeta_to_editor_js() {
		add_action( 'wpv_action_wpv_add_types_postmeta_to_editor', array(
			'WPCF_WPViews',
			'wpcf_admin_post_add_postmeta_to_editor_on_demand'
		) );
		add_action( 'wpv_action_wpv_add_types_post_usermeta_to_editor', array(
			'WPCF_WPViews',
			'wpcf_admin_post_add_usermeta_to_editor_on_demand'
		) );
	}


	public static function wpcf_admin_post_add_postmeta_to_editor_on_demand( $editor ) {
		add_action( 'admin_footer', 'wpcf_admin_post_js_validation' );
		wpcf_enqueue_scripts();
		wp_enqueue_script( 'toolset-colorbox' );
		wp_enqueue_style( 'toolset-colorbox' );

		$current_post = apply_filters( 'wpcf_filter_wpcf_admin_get_current_edited_post', null );
		if ( empty( $current_post ) ) {
			$current_post = (object) array( 'ID' => - 1 );
		}

		$fields = wpcf_admin_post_add_to_editor( 'get' );
		$groups = wpcf_admin_post_get_post_groups_fields( $current_post );
		if (
			! empty( $fields )
			&& ! empty( $groups )
		) {
			foreach ( $groups as $group ) {
				if ( empty( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $group_field_id => $group_field ) {
					if ( isset( $fields[ $group_field_id ] ) ) {
						$field = $fields[ $group_field_id ];
						$callback = 'wpcfFieldsEditorCallback(\'' . $field['id'] . '\', \'postmeta\', ' . $current_post->ID . ')';
						$editor->add_insert_shortcode_menu(
							stripslashes( $field['name'] ),
							trim( wpcf_fields_get_shortcode( $field ), '[]' ),
							$group['name'],
							$callback
						);
					}
				}
			}
		}
	}


	public static function wpcf_admin_post_add_usermeta_to_editor_on_demand() {
		add_action( 'admin_footer', 'wpcf_admin_post_js_validation' );
		wpcf_enqueue_scripts();
		wp_enqueue_script( 'toolset-colorbox' );
		wp_enqueue_style( 'toolset-colorbox' );
		add_filter( 'editor_addon_menus_wpv-views', 'wpcf_admin_post_add_usermeta_to_editor_js' );
	}


	/**
	 * Adds items to view dropdown.
	 *
	 * @param $menu
	 *
	 * @return mixed
	 */
	public static function editorDropdownFilter( $menu ) {
		$post = apply_filters( 'wpcf_filter_wpcf_admin_get_current_edited_post', null );
		if ( ! $post ) {
			$post = (object) array( 'ID' => - 1 );
		}
		$groups = wpcf_admin_fields_get_groups( TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'group_active' );
		$all_post_types = implode( ' ',
			get_post_types( array( 'public' => true ) ) );
		$add = array();
		if ( ! empty( $groups ) ) {
			// $group_id is blank therefore not equal to $group['id']
			// use array for item key and CSS class
			$item_styles = array();

			foreach ( $groups as $group ) {
				$fields = wpcf_admin_fields_get_fields_by_group( $group['id'],
					'slug', true, false, true );
				if ( ! empty( $fields ) ) {
					// code from Types used here without breaking the flow
					// get post types list for every group or apply all
					$post_types = get_post_meta( $group['id'],
						'_wp_types_group_post_types', true );
					if ( $post_types == 'all' ) {
						$post_types = $all_post_types;
					}
					$post_types = trim( str_replace( ',', ' ', $post_types ) );
					$item_styles[ $group['name'] ] = $post_types;

					foreach ( $fields as $field ) {
						$callback = 'wpcfFieldsEditorCallback(\'' . $field['id']
						            . '\', \'postmeta\', ' . $post->ID . ')';
						$menu[ $group['name'] ][ stripslashes( $field['name'] ) ] = array(
							stripslashes( $field['name'] ),
							trim( wpcf_fields_get_shortcode( $field ),
								'[]' ),
							$group['name'],
							$callback
						);
						// TODO Remove - it's not post edit screen (meta box JS and CSS)
						WPCF_Fields::enqueueScript( $field['type'] );
						WPCF_Fields::enqueueStyle( $field['type'] );
					}
				}
			}
		}

		return $menu;
	}

}
