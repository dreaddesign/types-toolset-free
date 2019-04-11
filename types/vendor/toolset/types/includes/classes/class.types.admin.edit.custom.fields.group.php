<?php

require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.edit.fields.php';

/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 * @access (for functions: only use if private)
 *
 * @see Function/method/class relied on
 * @link URL
 * @global type $varname Description.
 * @global type $varname Description.
 *
 * @param type $var Description.
 * @param type $var Optional. Description.
 *
 * @return type Description.
 */
class Types_Admin_Edit_Custom_Fields_Group extends Types_Admin_Edit_Fields {

	const PAGE_NAME = 'wpcf-edit';

    public function __construct()
    {
        parent::__construct();
        $this->get_id = 'group_id';
        add_action('wp_ajax_wpcf_ajax_filter', array($this, 'ajax_filter_dialog'));
    }

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	public function init_admin() {
		$this->post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME;
		$this->init_hooks();
		$this->boxes = array(
			'submitdiv'   => array(
				'callback' => array($this, 'box_submitdiv'),
				'title'    => __( 'Save', 'wpcf' ),
				'default'  => 'side',
				'priority' => 'high',
			),
			/* 'types_where' => array(
				'callback' => array($this, 'sidebar_group_conditions'),
				'title'    => __( 'Where to include this Field Group', 'wpcf' ),
				'default'  => 'side',
			), */
		);

		/** Admin styles **/
		$this->current_user_can_edit = WPCF_Roles::user_can_create( 'custom-field' );

		if( defined( 'TYPES_USE_STYLING_EDITOR' )
		    && TYPES_USE_STYLING_EDITOR
		    && $this->current_user_can_edit) {
			$this->boxes['types_styling_editor'] = array(
				'callback' => array( $this, 'types_styling_editor' ),
				'title' => __( 'Fields Styling Editor' ),
				'default'  => 'normal',
			);
		}
		$this->boxes = apply_filters( 'wpcf_meta_box_order_defaults', $this->boxes, $this->post_type );
		$this->boxes = apply_filters( 'wpcf_meta_box_custom_field', $this->boxes, $this->post_type );

		wp_enqueue_script( __CLASS__, WPCF_RES_RELPATH . '/js/' . 'taxonomy-form.js', array(
			'jquery',
			'jquery-ui-dialog',
			'jquery-ui-tabs'
		), WPCF_VERSION );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		wpcf_admin_add_js_settings( 'wpcfFormAlertOnlyPreview', sprintf( "'%s'", __( 'Sorry, but this is only preview!', 'wpcf' ) ) );
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	public function form() {
		$this->save();

		$this->current_user_can_edit = WPCF_Roles::user_can_create( 'custom-field' );

		// If it's update, get data
		// Note (by christian 3 June 2016): "Update" means: we're on group edit page and not on creating a new one.
		if( isset( $_REQUEST[ $this->get_id ] ) ) {
			$this->update = wpcf_admin_fields_get_group( intval( $_REQUEST[ $this->get_id ] ) );
			if( empty( $this->update ) ) {
				$this->update = false;
				wpcf_admin_message( sprintf( __( "Group with ID %d do not exist", 'wpcf' ), intval( $_REQUEST[ $this->get_id ] ) ) );
			} else {
				$this->current_user_can_edit = WPCF_Roles::user_can_edit( 'custom-field', $this->update );
				$this->update['fields']      = wpcf_admin_fields_get_fields_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ), 'slug', false, true );
				$this->update['post_types']  = wpcf_admin_get_post_types_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				$this->update['taxonomies']  = wpcf_admin_get_taxonomies_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				$this->update['templates']   = wpcf_admin_get_templates_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				if( defined( 'TYPES_USE_STYLING_EDITOR' ) && TYPES_USE_STYLING_EDITOR ) {
					$this->update['admin_styles'] = wpcf_admin_get_groups_admin_styles_by_group( sanitize_text_field( $_REQUEST[ $this->get_id ] ) );
				}
			}
		}

		/**
		 * sanitize id
		 */
		if( ! isset( $this->update['id'] ) ) {
			$this->update['id'] = 0;
		}

		/**
		 * setup meta type
		 */
		$this->update['meta_type'] = 'custom_fields_group';

		/**
		 * copy update to ct
		 */
		$this->ct = $this->update;

		$form = $this->prepare_screen();

		$form['_wpnonce_wpcf'] = array(
			'#type'   => 'markup',
			'#markup' => wp_nonce_field( 'wpcf_form_fields', '_wpnonce_wpcf', true, false ),
		);

		/**
		 * nonce depend on group id
		 */
		$form[ '_wpnonce_' . $this->post_type ] = array(
			'#type'   => 'markup',
			'#markup' => wp_nonce_field( $this->get_nonce_action( $this->update['id'] ), 'wpcf_save_group_nonce', true, false ),
		);

		$form['form-open']     = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<div id="post-body-content" class="%s">', $this->current_user_can_edit
				? ''
				: 'wpcf-types-read-only' ),
		);
		$form[ $this->get_id ] = array(
			'#type'  => 'hidden',
			'#name'  => 'wpcf[group][id]',
			'#value' => $this->update['id'],
		);

		$form['table-1-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<table id="wpcf-types-form-name-table" class="wpcf-types-form-table widefat js-wpcf-slugize-container"><thead><tr><th colspan="2">' . __( 'Name and description', 'wpcf' ) . '</th></tr></thead><tbody>',
		);
		$table_row            = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
		$form['title']        = array(
			'#title'      => sprintf( '%s <b>(%s)</b>', __( 'Name', 'wpcf' ), __( 'required', 'wpcf' ) ),
			'#type'       => 'textfield',
			'#name'       => 'wpcf[group][name]',
			'#id'         => 'wpcf-group-name',
			'#value'      => $this->update['id']
				? $this->update['name']
				: '',
			'#inline'     => true,
			'#attributes' => array(
				'class'       => 'large-text',
				'placeholder' => __( 'Enter Field Group name', 'wpcf' ),
			),
			'#validate'   => array(
				'required' => array(
					'value' => true,
				),
			),
			'#pattern'    => $table_row,
		);
		$form['description']  = array(
			'#title'      => __( 'Description', 'wpcf' ),
			'#type'       => 'textarea',
			'#id'         => 'wpcf-group-description',
			'#name'       => 'wpcf[group][description]',
			'#value'      => $this->update['id']
				? $this->update['description']
				: '',
			'#attributes' => array(
				'placeholder' => __( 'Enter Field Group description', 'wpcf' ),
				'class'       => 'hidden js-wpcf-description',
			),
			'#pattern'    => $table_row,
			'#after'      => sprintf( '<a class="js-wpcf-toggle-description hidden" href="#">%s</a>', __( 'Add description', 'wpcf' ) ),
			'#inline'     => true,
		);

		$form['table-1-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);

		/**
		 * Where to include these field group
		 */

		$form['table-2-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<table class="wpcf-types-form-table wpcf-where-to-include widefat"><thead><tr><th colspan="2">' . __( 'Where to include this Field Group', 'wpcf' ) . '</th></tr></thead><tbody>',
		);

		$form['table-2-content'] = array(
			'#type'   => 'markup',
			'#markup' => '<tr><td>'.$this->sidebar_group_conditions().'</td></tr>',
		);

		$form['table-2-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);


		/**
		 * fields
		 */
		$form += $this->fields();

		$form['form-close'] = array(
			'#type'    => 'markup',
			'#markup'  => '</div>',
			'_builtin' => true,
		);

		/**
		 * setup common setting for forms
		 */
		$form = $this->common_form_setup( $form );

		/**
		 * return form if current_user_can edit
		 */
		if( $this->current_user_can_edit ) {
			return $form;
		}

		return wpcf_admin_common_only_show( $form );
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	public function sidebar_group_conditions() {
		global $wpcf;

		// if not saved yet, print message and abort
		if( $this->update['id'] === 0 ) {
			return $this->print_notice( __( 'Please save first, then you can select where to display this Field Group.', 'wpcf' ), 'no-wrap', false );
		}

		// supported post types
		$post_types          = get_post_types( '', 'objects' );
		$currently_supported = array();
		$form_types          = array();

		foreach( $post_types as $post_type_slug => $post_type ) {
			// skip if post type should
			if( ! $this->show_post_type_in_ui( $post_type, $post_type_slug ) )
				continue;

			// add hidden value field
			$form_types[ $post_type_slug ] = array(
				'#type'       => 'hidden',
				'#name'       => 'wpcf[group][supports][' . $post_type_slug . ']',
				'#id'         => 'wpcf-form-groups-support-post-type-' . $post_type_slug,
				'#attributes' => array(
					'data-wpcf-label' => $post_type->labels->name
				),
				'#value'      => '',
				'#inline'     => true,
			);
			/**
			 * updated?
			 */
			if( $this->update && ! empty( $this->update['post_types'] ) && in_array( $post_type_slug, $this->update['post_types'] ) ) {

				$form_types[ $post_type_slug ]['#value'] = $post_type_slug;
				$currently_supported[]                   = $post_type->labels->singular_name;
			}
		}
		sort( $currently_supported );

		$tax_currently_supported = array();
		$form_tax                = array();

		if(
			isset( $this->update['taxonomies'] )
			&& is_array( $this->update['taxonomies'] )
			&& ! empty( $this->update['taxonomies'] )
		) {
			foreach( $this->update['taxonomies'] as $taxonomy_slug => $taxonomy ) {
				foreach( $taxonomy as $key => $term ) {
					$tax_currently_supported[ $term['term_taxonomy_id'] ] = $term['name'];
					$form_tax[ $term['term_taxonomy_id'] ] = array(
						'#type'       => 'hidden',
						'#name'       => 'wpcf[group][taxonomies][' . $taxonomy_slug . '][' . $term['term_taxonomy_id'] . ']',
						'#id'         => 'wpcf-form-groups-support-tax-' . $term['term_taxonomy_id'],
						'#attributes' => array(
							'data-wpcf-label' => $term['name']
						),
						'#value'      => $term['term_taxonomy_id'],
						'#inline'     => true,
					);
				}
			}
		}

		/*
		 * Taxonomies

		$taxonomies              = apply_filters( 'wpcf_group_form_filter_taxonomies', get_taxonomies( '', 'objects' ) );
		$tax_currently_supported = array();
		$form_tax                = array();

		// hidden fields
		foreach( $taxonomies as $category_slug => $category ) {

			// system taxes to skip
			$skip_categories = array(
				'nav_menu',
				'link_category',
				'post_format'
			);

			if( in_array( $category_slug, $skip_categories ) )
				continue;


			// get all terms of tax
			$terms = apply_filters( 'wpcf_group_form_filter_terms', get_terms( $category_slug, array('hide_empty' => false) ) );

			// skip if tax has no terms
			if( empty( $terms ) )
				continue;

			foreach( $terms as $term ) {
				$checked = 0;
				if( $this->update && ! empty( $this->update['taxonomies'] ) && array_key_exists( $category_slug, $this->update['taxonomies'] ) ) {
					if( array_key_exists( $term->term_taxonomy_id, $this->update['taxonomies'][ $category_slug ] ) ) {
						$checked                                            = 1;
						$tax_currently_supported[ $term->term_taxonomy_id ] = $term->name;
					}
				}

				error_log( 'update-taxonomies ' . print_r( $this->update['taxonomies'], true ) );
				$form_tax[ $term->term_taxonomy_id ] = array(
					'#type'       => 'hidden',
					'#name'       => 'wpcf[group][taxonomies][' . $category_slug . '][' . $term->term_taxonomy_id . ']',
					'#id'         => 'wpcf-form-groups-support-tax-' . $term->term_taxonomy_id,
					'#attributes' => array(
						'data-wpcf-label' => $term->name
					),
					'#value'      => preg_match( '#"' . preg_quote( $term->slug, '#' ) . '"#i', json_encode( isset( $this->update['taxonomies'] )
						? $this->update['taxonomies']
						: '' ) )
						? $term->term_taxonomy_id
						: '',
					'#inline'     => true,
				);
			}

		}*/


		/**
		 * Filter templates
		 */
		$templates       = get_page_templates();
		$templates_views = get_posts( array(
			'post_type'   => 'view-template',
			'numberposts' => - 1,
			'post_status'      => 'publish',
		) );
		$form_templates  = array();

		/**
		 * Sanitize
		 */
		if( ! isset( $this->ct['templates'] ) ) {
			$this->ct['templates'] = array();
		}

		/**
		 * options
		 */
		$form_templates['default-template'] = array(
			'#type'       => 'hidden',
			'#value'      => in_array( 'default', $this->ct['templates'] )
				? 'default'
				: '',
			'#name'       => 'wpcf[group][templates][]',
			'#inline'     => true,
			'#attributes' => array(
				'data-wpcf-label' => __( 'Default Template', 'wpcf' ),
			),
			'#id'         => 'wpcf-form-groups-support-templates-default',
		);
		foreach( $templates as $template_name => $template_filename ) {
			$form_templates[ $template_filename ] = array(
				'#type'       => 'hidden',
				'#value'      => in_array( $template_filename, $this->ct['templates'] )
					? $template_filename
					: '',
				'#name'       => 'wpcf[group][templates][]',
				'#inline'     => true,
				'#attributes' => array(
					'data-wpcf-label' => $template_name,
				),
				'#id'         => sprintf( 'wpcf-form-groups-support-templates-%s', sanitize_title_with_dashes( $template_filename ) ),
			);
		}
		foreach( $templates_views as $template_view ) {
			$form_templates[ $template_view->post_name ]    = array(
				'#type'   => 'hidden',
				'#value'  => in_array( $template_view->ID, $this->ct['templates'] )
					? $template_view->ID
					: '',
				'#name'   => 'wpcf[group][templates][]',
				'#attributes' => array(
					'data-wpcf-label' => $template_view->post_title,
				),
				'#inline' => true,
				'#id'     => sprintf( 'wpcf-form-groups-support-templates-%d', $template_view->ID ),
			);
			$templates_view_list_text[ $template_view->ID ] = $template_view->post_title;
		}


		$text = '';
		if( ! empty( $this->update['templates'] ) ) {
			$text      = array();
			$templates = array_flip( $templates );
			foreach( $this->update['templates'] as $template ) {
				if( $template == 'default' ) {
					$template = __( 'Default Template', 'wpcf' );
				} else if( strpos( $template, '.php' ) !== false ) {
					$template = $templates[ $template ];
				} else {
					$template = sprintf( __( 'Content Template %s', 'wpcf' ), $templates_view_list_text[ $template ] );
				}
				$text[] = $template;
			}
			$text = implode( ', ', $text );
		} else {
			$text = __( 'Not Selected', 'wpcf' );
		}

		// start form
		$form = array();

		// container for better styling
		$form['where-to-include-inner-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="wpcf-where-to-include-inner"><div class="wpcf-conditions-container">'
		);

		// Description: no conditions set so far
		$form['supports-msg-conditions-none'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-none">%s</p>', __( 'By default <b>this group of fields</b> will appear when editing <b>all content.</b><br /><br />Select specific Post Types, Terms, Templates or set Data-dependent filters to limit the fields to specific locations and/or conditions in the WordPress admin.', 'wpcf' ) ),
		);

		// Description: conditions set
		$form['supports-msg-conditions'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-set">%s</p>', __( 'This Post Field Group is used with:', 'wpcf' ) ),
		);

		// Description: Post Types set
		$form['supports-msg-conditions-post-types'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-condition ' . 'js-wpcf-fields-group-conditions-post-types">' . '%s <span></span></p>', __( 'Post Type(s):', 'wpcf' ) ),
		);

		// Description: Terms set
		$form['supports-msg-conditions-terms'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-condition ' . 'js-wpcf-fields-group-conditions-terms">' . '%s <span></span></p>', __( 'Term(s):', 'wpcf' ) ),
		);

		// Description: Templates set
		$form['supports-msg-conditions-templates'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-condition ' . 'js-wpcf-fields-group-conditions-templates">' . '%s <span></span></p>', __( 'Template(s):', 'wpcf' ) ),
		);

		// Description: Data dependencies set
		$form['supports-msg-conditions-data-dependencies'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf( '<p class="wpcf-fields-group-conditions-description ' . 'js-wpcf-fields-group-conditions-condition ' . 'js-wpcf-fields-group-conditions-data-dependencies">' . '%s <span></span></p>', __( 'Additional condition(s):', 'wpcf' ) ),
		);

		/**
		 * Join filter forms
		 */
		// Types
		$form += $form_types;

		// Terms
		$form += $form_tax;

		// Templates
		$form += $form_templates;

		// Data Dependencies
		$form['hide-data-dependencies-open']  = array(
			'#type'   => 'markup',
			'#markup' => '<div style="display:none;">'
		);
		$additional_filters                   = apply_filters( 'wpcf_fields_form_additional_filters', array(), $this->update );
		$form                                 = $form + $additional_filters;
		$form['hide-data-dependencies-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);

		$form['conditions-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);

		// Edit Button
		$form['edit-button-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="wpcf-edit-button-container">'
		);
		$form += $this->filter_wrap( 'wpcf-filter-dialog-edit', array(
			'data-wpcf-buttons-apply'   => esc_attr__( 'Apply', 'wpcf' ),
			'data-wpcf-buttons-cancel'  => esc_attr__( 'Cancel', 'wpcf' ),
			'data-wpcf-dialog-title'    => esc_attr__( 'Where to use this Field Group', 'wpcf' ),
			'data-wpcf-field-prefix'    => esc_attr( 'wpcf-form-groups-support-' ),
			'data-wpcf-id'              => esc_attr( $this->update['id'] ),
			'data-wpcf-message-any'     => esc_attr__( 'Not Selected', 'wpcf' ),
			'data-wpcf-message-loading' => esc_attr__( 'Please Wait, Loading…', 'wpcf' ),
		), true );
		$form['where-to-include-inner-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div></div>' // also close for 'edit-button-container'
		);

		// Filter Association
		if( $this->current_user_can_edit ) {
			$count = 0;
			$count += ! empty( $this->update['post_types'] ) ? 1 : 0;
			$count += ! empty( $this->update['taxonomies'] ) ? 1 : 0;
			$count += ! empty( $this->update['templates'] ) ? 1 : 0;
			$display = $count > 1 ? '' : ' style="display:none;"';

			$form['filters_association'] = array(
				'#title'         => '<b>' . __( 'Use Field Group:', 'wpcf' ) . '</b>',
				'#type'          => 'radios',
				'#name'          => 'wpcf[group][filters_association]',
				'#id'            => 'wpcf-fields-form-filters-association',
				'#options-after' => '',
				'#options'       => array(
					__( 'when <b>ANY</b> condition is met', 'wpcf' )   => 'any',
					__( 'when <b>ALL</b> conditions are met', 'wpcf' ) => 'all',
				),
				'#default_value' => ! empty( $this->update['filters_association'] )
					? $this->update['filters_association']
					: 'any',
				'#inline'        => true,
				'#before'        => '<div id="wpcf-fields-form-filters-association-form"' . $display . '>',
				'#after'         => '<div id="wpcf-fields-form-filters-association-summary" ' . 'style="font-style:italic;clear:both;"></div></div>',
			);
			// settings
			/*
			$settings = array(
				'wpcf_filters_association_or' => __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% or Content Template is: %vt%', 'wpcf' ),

				'wpcf_filters_association_and' => __( 'This group will appear on %pt% edit pages where content belongs to Taxonomy: %tx% and Content Template is: %vt%', 'wpcf' ),
				'wpcf_filters_association_all_pages' => __( 'all', 'wpcf' ),
				'wpcf_filters_association_all_taxonomies' => __( 'any', 'wpcf' ),
				'wpcf_filters_association_all_templates' => __( 'any', 'wpcf' ),
			);
			$form['filters_association']['#after'] .= sprintf(
				'<script type="text/javascript">wpcf_settings = %s;</script>',
				json_encode($settings)
			);
			*/
		}

		/**
		 * setup common setting for forms
		 */
		$form = $this->common_form_setup( $form );

		/**
		 * render form
		 */
		$form = wpcf_form( __FUNCTION__, $form );
		return $form->renderForm();
	}

	public function types_styling_editor() {
		$form = $this->add_admin_style( array() );

		$form = wpcf_form( __FUNCTION__, $form );
		echo $form->renderForm();
	}

	/**
	 * deprecated
	 */
	private function add_admin_style( $form ) {

		$admin_styles_value = $preview_profile = $edit_profile = '';

		if( isset( $this->update['admin_styles'] ) ) {
			$admin_styles_value = $this->update['admin_styles'];
		}
		$temp = array();

		if( $this->update ) {
			require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
			// require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta.php';
			require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
			require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
			//Get sample post
			$post = query_posts( 'posts_per_page=1' );


			if( ! empty( $post ) && count( $post ) != '' ) {
				$post = $post[0];
			}
			$preview_profile = wpcf_admin_post_meta_box_preview( $post, $this->update, 1 );
			$group           = $this->update;
			$group['fields'] = wpcf_admin_post_process_fields( $post, wpcf_getarr( $group, 'fields', array() ), true, false );
			$edit_profile    = wpcf_admin_post_meta_box( $post, $group, 1, true );
			add_action( 'admin_enqueue_scripts', 'wpcf_admin_fields_form_fix_styles', PHP_INT_MAX );
		}

		$temp[] = array(
			'#type'          => 'radio',
			'#suffix'        => '<br />',
			'#value'         => 'edit_mode',
			'#title'         => 'Edit mode',
			'#name'          => 'wpcf[group][preview]',
			'#default_value' => '',
			'#before'        => '<div class="wpcf-admin-css-preview-style-edit">',
			'#inline'        => true,
			'#attributes'    => array('onclick' => 'changePreviewHtml(\'editmode\')', 'checked' => 'checked')
		);

		$temp[] = array(
			'#type'          => 'radio',
			'#title'         => 'Read Only',
			'#name'          => 'wpcf[group][preview]',
			'#default_value' => '',
			'#after'         => '</div>',
			'#inline'        => true,
			'#attributes'    => array('onclick' => 'changePreviewHtml(\'readonly\')')
		);

		$temp[] = array(
			'#type'   => 'textarea',
			'#name'   => 'wpcf[group][admin_html_preview]',
			'#inline' => true,
			'#id'     => 'wpcf-form-groups-admin-html-preview',
			'#before' => '<h3>Field group HTML</h3>'
		);

		$temp[] = array(
			'#type'          => 'textarea',
			'#name'          => 'wpcf[group][admin_styles]',
			'#inline'        => true,
			'#value'         => $admin_styles_value,
			'#default_value' => '',
			'#id'            => 'wpcf-form-groups-css-fields-editor',
			'#after'         => '
                <div class="wpcf-update-preview-btn"><input type="button" value="Update preview" onclick="wpcfPreviewHtml()" style="float:right;" class="button-secondary"></div>
                <h3>' . __( 'Field group preview', 'wpcf' ) . '</h3>
                <div id="wpcf-update-preview-div">Preview here</div>
                <script type="text/javascript">
var wpcfReadOnly = ' . json_encode( base64_encode( $preview_profile ) ) . ';
var wpcfEditMode = ' . json_encode( base64_encode( $edit_profile ) ) . ';
var wpcfDefaultCss = ' . json_encode( base64_encode( $admin_styles_value ) ) . ';
        </script>
        ',
			'#before'        => sprintf( '<h3>%s</h3>', __( 'Your CSS', 'wpcf' ) ),
		);

		$admin_styles                                                       = _wpcf_filter_wrap( 'admin_styles', __( 'Admin styles for fields:', 'wpcf' ), '', '', $temp, __( 'Open style editor', 'wpcf' ) );
		$form[ 'p_wrap_1_' . wpcf_unique_id( serialize( $admin_styles ) ) ] = array(
			'#type'   => 'markup',
			'#markup' => '<p class="wpcf-filter-wrap">',
		);
		$form                                                               = $form + $admin_styles;

		return $form;
	}


	/**
	 * Get description of tabs that will be displayed on the filter dialog.
	 *
	 * @return array[]
	 */
	protected function get_tabs_for_filter_dialog() {
		$tabs = array(
			'post-types'     => array(
				'title' => __( 'Post Types', 'wpcf' ),
			),
			'taxonomies'     => array(
				'title' => __( 'Taxonomies', 'wpcf' ),
			),
			'templates'      => array(
				'title' => __( 'Templates', 'wpcf' )
			),
			'data-dependant' => array(
				'title' => __( 'Data-dependant', 'wpcf' )
			)
		);

		return $tabs;
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @param $filter
	 * @param $form
	 */
	protected function form_add_filter_dialog( $filter, &$form ) {
		global $wpcf;
		switch( $filter ) {
			/**
			 * post types
			 */
			case 'post-types':
				$form['post-types-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-wpcf-description">' . __( 'Select specific Post Types that you want to use with this Field Group:', 'wpcf' ) . '</p>'
				);
				$form['post-types-ul-open']     = array(
					'#type'   => 'markup',
					'#markup' => '<ul>',
				);

				$currently_supported = wpcf_admin_get_post_types_by_group( sanitize_text_field( $_REQUEST['id'] ) );

				$post_types = get_post_types( array('show_ui' => true), 'objects' );
				ksort( $post_types );
				foreach( $post_types as $post_type_slug => $post_type ) {
					if( in_array( $post_type_slug, $wpcf->excluded_post_types ) ) {
						continue;
					}
					$form[ 'option_' . $post_type_slug ] = array(
						'#name'          => esc_attr( $post_type_slug ),
						'#type'          => 'checkbox',
						'#value'         => 1,
						'#default_value' => $this->ajax_filter_default_value( $post_type_slug, $currently_supported, 'post-type' ),
						'#inline'        => true,
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#title'         => $post_type->label,
						'#attributes'    => array(
							'data-wpcf-value'  => esc_attr( $post_type_slug ),
							'data-wpcf-prefix' => 'post-type-'
						),
					);
				}
				$form['post-types-ul-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;

			/**
			 * taxonomies
			 */
			case 'taxonomies':
				$form['taxonomies-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-wpcf-description">' . __( 'Select ' . 'specific Terms from Taxonomies below that you want to use with this Field Group:', 'wpcf' ) . '</p>'
				);

				include_once WPCF_INC_ABSPATH . '/fields.php';
				$currently_supported = wpcf_admin_get_taxonomies_by_group( $_REQUEST['id'] );
				$taxonomies          = apply_filters( 'wpcf_group_form_filter_taxonomies', get_taxonomies( '', 'objects' ) );
				$taxonomies_settings = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

				$form['taxonomies-div-open'] = array(
					'#type'   => 'markup',
					'#markup' => '<div id="poststuff" class="meta-box-sortables">',
				);
				foreach( $taxonomies as $category_slug => $category ) {
					if( $category_slug == 'nav_menu' || $category_slug == 'link_category' || $category_slug == 'post_format' || ( isset( $taxonomies_settings[ $category_slug ]['disabled'] ) && $taxonomies_settings[ $category_slug ]['disabled'] == 1 ) || empty( $category->labels->name ) ) {
						continue;
					}

					$terms = apply_filters( 'wpcf_group_form_filter_terms', get_terms( $category_slug, array('hide_empty' => false) ) );
					if( empty( $terms ) ) {
						continue;
					}

					$form_tax                               = array();
					$form_tax[ $category_slug . '-search' ] = array(
						'#type'       => 'textfield',
						'#name'       => $category_slug . '-search',
						'#attributes' => array(
							'class'       => 'widefat js-wpcf-taxonomy-search',
							'placeholder' => esc_attr__( 'Search', 'wpcf' ),
						),
					);
					foreach( $terms as $term ) {
						$form_tax[ $term->term_taxonomy_id ] = array(
							'#type'          => 'checkbox',
							'#name'          => esc_attr( sprintf( 'tax-%d', $term->term_taxonomy_id ) ),
							'#value'         => 1,
							'#inline'        => true,
							'#before'        => '<li>',
							'#after'         => '</li>',
							'#title'         => $term->name,
							'#default_value' => $this->ajax_filter_default_value( $term->term_taxonomy_id, $currently_supported, 'taxonomy', $category_slug ),
							'#attributes'    => array(
								'data-wpcf-value'  => esc_attr( $term->term_taxonomy_id ),
								'data-wpcf-slug'   => esc_attr( $term->slug ),
								'data-wpcf-name'   => esc_attr( $term->name ),
								'data-wpcf-taxonomy-slug' => esc_attr( $category_slug ),
								'data-wpcf-prefix' => ''
							),
						);
					}
					$form += $this->ajax_filter_add_box( $category_slug, $category->labels->name, $form_tax );
				}
				$form['taxonomies-div-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</div>',
				);
				break;

			/**
			 * templates
			 */
			case 'templates':
				$form['templates-description'] = array(
					'#type'   => 'markup',
					'#markup' => '<p class="description js-wpcf-description">' . __( 'Select specific Template that you want to use with this Field Group:', 'wpcf' ) . '</p>'
				);

				$form['templates-ul-open'] = array(
					'#type'   => 'markup',
					'#markup' => '<ul>',
				);
				include_once WPCF_INC_ABSPATH . '/fields.php';
				$currently_supported      = wpcf_admin_get_templates_by_group( sanitize_text_field( $_REQUEST['id'] ) );
				$templates                = get_page_templates();
				$templates_views          = get_posts( array(
					'post_type'   => 'view-template',
					'numberposts' => - 1,
					'post_status'      => 'publish',
				) );
				$form['default-template'] = array(
					'#type'          => 'checkbox',
					'#default_value' => $this->ajax_filter_default_value( 'default', $currently_supported, 'template' ),
					'#name'          => 'default',
					'#value'         => 1,
					'#inline'        => true,
					'#title'         => __( 'Default', 'wpcf' ),
					'#before'        => '<li>',
					'#after'         => '</li>',
					'#attributes'    => array(
						'data-wpcf-value'  => esc_attr( 'default' ),
						'data-wpcf-prefix' => 'templates-'
					),
				);
				foreach( $templates as $template_name => $template_filename ) {
					$form[ $template_filename ] = array(
						'#type'          => 'checkbox',
						'#default_value' => $this->ajax_filter_default_value( $template_filename, $currently_supported, 'template' ),
						'#value'         => 1,
						'#inline'        => true,
						'#title'         => $template_name,
						'#name'          => sanitize_title_with_dashes( $template_filename ),
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#attributes'    => array(
							'data-wpcf-value'  => esc_attr( $template_filename ),
							'data-wpcf-prefix' => 'templates-'
						),
					);
				}
				foreach( $templates_views as $template_view ) {
					$form[ $template_view->post_name ] = array(
						'#type'          => 'checkbox',
						'#value'         => 1,
						'#default_value' => $this->ajax_filter_default_value( $template_view->ID, $currently_supported, 'template' ),
						'#inline'        => true,
						'#title'         => apply_filters( 'the_title', $template_view->post_title, $template_view->ID ),
						'#name'          => $template_view->ID,
						'#before'        => '<li>',
						'#after'         => '</li>',
						'#attributes'    => array(
							'data-wpcf-value' => esc_attr( $template_view->ID ),
							'data-wpcf-prefix' => 'templates-'
						),
					);
				}
				$form['templates-ul-close'] = array(
					'#type'   => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;

			/**
			 * data dependant
			 */
			case 'data-dependant':
				require_once WPCF_INC_ABSPATH . '/classes/class.types.fields.conditional.php';
				$data_dependant = new Types_Fields_Conditional();
				$form += $data_dependant->group_condition_get( true );
				break;
		}
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	private function ajax_filter_add_box( $slug, $title, $data ) {
		$form = array(
			$slug . '-begin' => array(
				'#type'   => 'markup',
				'#markup' => sprintf( '<div class="postbox"><div class="handlediv" title="%s"><br></div><h3 class=""><span>%s</span></h3><div class="inside"><ul>', esc_attr__( 'Click to toggle', 'wpcf' ), $title )
			)
		);
		$form += $data;
		$form[ $slug . '-end' ] = array(
			'#type'   => 'markup',
			'#markup' => '</ul><br class="clear" /></div></div>',
		);

		return $form;
	}


	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param string $value Description.
	 * @param array $currently_supported Optional. Description.
	 * @param boolean|string $type Optional. Description.
	 * @param boolean|string $type_category Optional. Description.
	 *
	 * @return type Description.
	 */
	private function ajax_filter_default_value(
		$value, $currently_supported = array(), $type = false, $type_category = false
	) {
		if( $type && isset( $_REQUEST['all_fields'] ) && is_array( $_REQUEST['all_fields'] ) ) {
			switch( $type ) {
				case 'post-type':
					if( isset( $_REQUEST['all_fields']['wpcf']['group']['supports'] ) && in_array( $value, $_REQUEST['all_fields']['wpcf']['group']['supports'] ) ) {
						return true;
					}
					break;
				case 'taxonomy':
					if( $type_category && isset( $_REQUEST['all_fields']['wpcf']['group']['taxonomies'][ $type_category ] ) && in_array( $value, $_REQUEST['all_fields']['wpcf']['group']['taxonomies'][ $type_category ] ) ) {
						return true;
					}
					break;
				case 'template':
					if( isset( $_REQUEST['all_fields']['wpcf']['group']['templates'] ) && in_array( $value, $_REQUEST['all_fields']['wpcf']['group']['templates'] ) ) {
						return true;
					}
					break;
			}
			// not selected
			return false;
		}

		if( isset( $_REQUEST['current'] ) ) {
			if( is_array( $_REQUEST['current'] ) && in_array( $value, $_REQUEST['current'] ) ) {
				return true;
			}
		} else if( $currently_supported && ! empty( $currently_supported ) && in_array( $value, $currently_supported ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	protected function save() {
		// abort if no post data
		if( ! isset( $_POST['wpcf'] ) )
			return;

		// abort when no group id isset
		if( ! isset( $_POST['wpcf']['group']['id'] ) )
			$this->verification_failed_and_die( 1 );

		// nonce verification
		$nonce_name = $this->get_nonce_action( $_POST['wpcf']['group']['id'] );
		if( ! wp_verify_nonce( $_REQUEST['wpcf_save_group_nonce'], $nonce_name ) )
			$this->verification_failed_and_die( 2 );

		// get group_id
		$group_id = wpcf_admin_fields_save_group( $_POST['wpcf']['group'], TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, 'custom' );

		// abort if does not exist
		if( empty( $group_id ) )
			return;

		$_REQUEST[ $this->get_id ] = $group_id;

		// save
		$this->save_group_fields( $group_id );
		$this->save_condition_post_types( $group_id );
		$this->save_condition_templates( $group_id );
		$this->save_condition_taxonomies( $group_id );

		do_action( 'types_fields_group_saved', $group_id );
		do_action( 'types_fields_group_post_saved', $group_id );

		// do not use these hooks anymore
		do_action( 'wpcf_fields_group_saved', $group_id );
		do_action( 'wpcf_postmeta_fields_group_saved', $group_id );

		// redirect
		$args = array(
			'page'        => 'wpcf-edit',
			$this->get_id => $group_id
		);

		if( isset( $_GET['ref'] ) )
			$args['ref'] = $_GET['ref'];

		wp_safe_redirect( esc_url_raw( add_query_arg( $args, admin_url( 'admin.php' ) ) ) );

		die;
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @see Function/method/class relied on
	 * @link URL
	 * @global type $varname Description.
	 * @global type $varname Description.
	 *
	 * @param type $var Description.
	 * @param type $var Optional. Description.
	 *
	 * @return type Description.
	 */
	private function save_group_fields( $group_id ) {
		if( empty( $_POST['wpcf']['fields'] ) ) {
			delete_post_meta( $group_id, '_wp_types_group_fields' );

			return;
		}
		$fields = array();

		// First check all fields
		foreach( $_POST['wpcf']['fields'] as $key => $field ) {
			$field = wpcf_sanitize_field( $field );
			$field = apply_filters( 'wpcf_field_pre_save', $field );
			if( ! empty( $field['is_new'] ) ) {
				// Check name and slug
				if( wpcf_types_cf_under_control( 'check_exists', sanitize_title( $field['name'] ) ) ) {
					$this->triggerError();
					wpcf_admin_message( sprintf( __( 'Field with name "%s" already exists', 'wpcf' ), $field['name'] ), 'error' );

					return $form;
				}
				if( isset( $field['slug'] ) && wpcf_types_cf_under_control( 'check_exists', sanitize_title( $field['slug'] ) ) ) {
					$this->triggerError();
					wpcf_admin_message( sprintf( __( 'Field with slug "%s" already exists', 'wpcf' ), $field['slug'] ), 'error' );

					return $form;
				}
			}
			$field['submit-key'] = $key;
			// Field ID and slug are same thing
			$field_id = wpcf_admin_fields_save_field( $field );
			if( is_wp_error( $field_id ) ) {
				$this->triggerError();
				wpcf_admin_message( $field_id->get_error_message(), 'error' );

				return;
			}
			if( ! empty( $field_id ) ) {
				$fields[] = $field_id;
			}
			// WPML
			/** @var string $field_id */
			if( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
				if( function_exists( 'wpml_cf_translation_preferences_store' ) ) {
					$real_custom_field_name = wpcf_types_get_meta_prefix( wpcf_admin_fields_get_field( $field_id ) ) . $field_id;
					wpml_cf_translation_preferences_store( $key, $real_custom_field_name );
				}
			}
		}
		wpcf_admin_fields_save_group_fields( $group_id, $fields );
	}

	/**
	 * @param $post_type
	 * @param $post_type_slug
	 * @param $wpcf
	 *
	 * @return bool
	 */
	private function show_post_type_in_ui( $post_type, $post_type_slug ) {
		global $wpcf;

		return $post_type->show_ui && ! in_array( $post_type_slug, $wpcf->excluded_post_types );
	}

	/**
	 * Summary.
	 *
	 * Description.
	 *
	 * @since x.x.x
	 * @access (for functions: only use if private)
	 *
	 * @param $group_id
	 */
	private function save_condition_post_types( $group_id ) {
		$post_types = isset( $_POST['wpcf']['group']['supports'] )
			? $_POST['wpcf']['group']['supports']
			: array();
		wpcf_admin_fields_save_group_post_types( $group_id, $post_types );
	}

	/**
	 * @param $group_id
	 */
	private function save_condition_taxonomies( $group_id ) {
		$post_taxonomies = isset( $_POST['wpcf']['group']['taxonomies'] )
			? $_POST['wpcf']['group']['taxonomies']
			: array();

		$taxonomies = array();
		foreach( $post_taxonomies as $taxonomy ) {
			foreach( $taxonomy as $tax => $term ) {
				if( ! empty( $term ) )
					$taxonomies[] = $term;
			}
		}

		wpcf_admin_fields_save_group_terms( $group_id, $taxonomies );
	}

	/**
	 * @param $group_id
	 */
	private function save_condition_templates( $group_id ) {
		$post_templates = (
			isset( $_POST['wpcf']['group']['templates'] )
			&& ! empty( $_POST['wpcf']['group']['templates'] )
		)
			? $_POST['wpcf']['group']['templates']
			: array();

		wpcf_admin_fields_save_group_templates( $group_id, $post_templates );
	}
}
