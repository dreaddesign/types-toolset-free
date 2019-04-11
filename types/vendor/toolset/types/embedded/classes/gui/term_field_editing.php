<?php

/**
 * Adds support for displaying and updating term fields on Edit Term page.
 *
 * Hooks into taxonomy-specific actions if there are some term field groups associated. Handles rendering fields
 * through toolset-forms (hidden inside the renderer).
 *
 * @since 1.9
 */
final class WPCF_GUI_Term_Field_Editing {


	// This class is a singleton.
	private static $instance = null;


	/**
	 * ID of the form for toolset-forms.
	 *
	 * The value is not arbitrary, it must match the actual ID of the form tag, otherwise JS validation will break
	 * (and who knows what else). In this case the ID is dictated by the add and edit term pages.
	 */
	const EDIT_FORM_ID = 'edittag';
	const ADD_FORM_ID = 'addtag';

	const PAGE_SCRIPT_NAME = 'types-add-term-page';


	/**
	 * Initialize the page extension.
	 *
	 * @since 1.9
	 */
	public static function initialize() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		self::$instance->add_hooks();
	}


	/**
	 * Get an instance of the page extension but don't initialize it.
	 *
	 * Useful for using some methods during AJAX call.
	 *
	 * @return WPCF_GUI_Term_Field_Editing
	 * @since 2.1
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	private function __construct() { }


	/**
	 * Hooks into taxonomy-specific actions if there are some term field groups associated.
	 */
	private function add_hooks() {

		$factory = Types_Field_Group_Term_Factory::get_instance();
		$groups_by_taxonomies = $factory->get_groups_by_taxonomies();

		$is_toolset_forms_support_needed = false;

		// Hooks for editing term fields
		foreach( $groups_by_taxonomies as $taxonomy => $groups ) {
			if( !empty( $groups ) ) {

				add_action( "{$taxonomy}_add_form_fields", array( $this, 'on_term_add' ) );
				add_action( "{$taxonomy}_edit_form_fields", array( $this, 'on_term_edit' ), 10, 2 );
				add_action( "create_{$taxonomy}", array( $this, 'on_term_update' ), 10, 2 );
				add_action( "edit_{$taxonomy}", array( $this, 'on_term_update' ), 10, 2 );

				$is_toolset_forms_support_needed = true;
			}
		}

		// Columns on the term listing
		$main_controller = Types_Main::get_instance();
		$is_term_listing_page = ( $main_controller->is_admin() && 'edit' != wpcf_getget( 'action' ) );
		
		if( $is_term_listing_page ) {
			$screen = get_current_screen();
			add_action( "manage_{$screen->id}_columns", array( $this, 'manage_term_listing_columns' ) );
			add_filter( "manage_{$screen->taxonomy}_custom_column", array( $this, 'manage_term_listing_cell' ), 10, 3 );
			add_filter( 'hidden_columns', array( $this, 'filter_hidden_columns' ), 10, 3 );
		}

		if( $is_toolset_forms_support_needed ) {
			$this->add_toolset_forms_support();
		}
	}


	public function on_term_add( $taxonomy_slug ) {
		$factory = Types_Field_Group_Term_Factory::get_instance();
		$groups = $factory->get_groups_by_taxonomy( $taxonomy_slug );

		if( empty( $groups ) ) {
			return;
		}

		// Indicate (by existence of this id) that we need to clean up after custom fields on submit.
		echo( '<input type="hidden" id="types-groups-exist" />' );

		foreach( $groups as $group ) {
			$this->render_field_group_add_page( $group, null );
		}
	}


	/**
	 * This will be called when editing an existing term.
	 *
	 * Renders term field groups associated with the taxonomy with all their fields, via toolset-forms.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/taxonomy_edit_form_fields/
	 *
	 * @param WP_Term $term Term that is being edited.
	 * @param string $taxonomy_slug Taxonomy where the term belongs.
	 */
	public function on_term_edit( $term, $taxonomy_slug ) {
		$factory = Types_Field_Group_Term_Factory::get_instance();
		$groups = $factory->get_groups_by_taxonomy( $taxonomy_slug );

		if( empty( $groups ) ) {
			return;
		}

		foreach( $groups as $group ) {
			$this->render_field_group_edit_page( $group, $term->term_id );
		}
	}


	public function on_term_update( $term_id, $tt_id ) {

		// Get an array of fields that we need to update. We don't care about their groups here.
		$term = get_term_by( 'term_taxonomy_id', $tt_id );
		if( ! $term instanceof WP_Term ) {
			return;
		}
		$factory = Types_Field_Group_Term_Factory::get_instance();
		$groups = $factory->get_groups_by_taxonomy( $term->taxonomy );
		if( empty( $groups ) ) {
			return;
		}
		$field_definitions = Types_Field_Utils::get_field_definitions_from_groups( $groups );

		$update_errors = $this->update_term_fields( $term_id, $field_definitions );

		// Display errors if we have any.
		if( !empty( $update_errors ) ) {
			foreach( $update_errors as $update_error ) {
				wpcf_admin_message_store( $update_error->get_error_message(), 'error' );
			}
			wpcf_admin_message_store(
				sprintf(
					'<strong>%s</strong>',
					__( 'There has been a problem while saving custom fields. Please fix it and try again.', 'wpcf' )
				),
				'error'
			);
		}

	}


	/**
	 * Update fields for given term.
	 *
	 * @param int $term_id
	 * @param WPCF_Field_Definition[] $field_definitions
	 * @return WP_Error[]
	 */
	private function update_term_fields( $term_id, $field_definitions ) {
		$update_results = array();
		foreach( $field_definitions as $field_definition ) {
			$update_results[] = $this->update_single_field( $field_definition, $term_id );
		}
		return $this->filter_wp_errors_flat( $update_results );
	}


	/**
	 * From an array that can contain booleans, WP_Error and arrays of WP_Error, create an array containing all
	 * WP_Error instances only.
	 *
	 * @param array $update_results
	 * @return WP_Error[]
	 */
	private function filter_wp_errors_flat( $update_results ) {
		$errors = array();
		foreach( $update_results as $update_result ) {
			if( $update_result instanceof WP_Error ) {
				$errors[] = $update_result;
			} else if( is_array( $update_result ) ) {
				foreach( $update_result as $error ) {
					if( $error instanceof WP_Error ) {
						$errors[] = $error;
					}
				}
			}
		}
		return $errors;
	}


	/**
	 * @param WPCF_Field_Definition $field_definition
	 * @param int $term_id
	 *
	 * @return WP_Error|WP_Error[]|true
	 */
	private function update_single_field( $field_definition, $term_id ) {
		$field = new WPCF_Field_Instance_Term( $field_definition, $term_id );
		$saver = new WPCF_Field_Data_Saver( $field, self::EDIT_FORM_ID );

		$validation_results = $saver->validate_field_data();

		$errors = array();
		foreach( $validation_results as $index => $validation_result ) {

			if( $validation_result instanceof WP_Error ) {
				$error_message = sprintf( '%s %s',
					sprintf( __( 'Field "%s" not updated:', 'wpcf' ), $field_definition->get_name() ),
					implode( ', ', $validation_result->get_error_data() )
				);
				$errors[] = new WP_Error( 'wpcf_field_not_updated', $error_message );
			}
		}

		if( !empty( $errors ) ) {
			return $errors;
		}

		$saving_result = $saver->save_field_data();

		return $saving_result;
	}


	/**
	 * Load various assets needed by the toolset-forms blob.
	 *
	 * @since 1.9
	 */
	private function add_toolset_forms_support() {
		// JS and CSS assets related to fields - mostly generic ones.
		wpcf_edit_post_screen_scripts();

		// Needed for fields that have something to do with files
		WPToolset_Field_File::file_enqueue_scripts();

		// Extra enqueuing of media assets needed since WPToolset_Field_File doesn't know about termmeta.
		wp_enqueue_media();

		// We need to append form-specific data for the JS validation script.
		add_action( 'admin_footer', array( $this, 'render_js_validation_data' ) );

		// Pretend we're about to create new form via toolset-forms, even if we're not going to.
		// This will load some assets needed for image field preview (specifically the 'wptoolset-forms-admin' style).
		// Hacky, but better than re-registering the toolset-forms stylesheet elsewhere.
		$faux_form_bootstrap = new WPToolset_Forms_Bootstrap();
		$faux_form_bootstrap->form( 'faux' );

		$asset_manager = Types_Asset_Manager::get_instance();

		// Enqueue the main page script
		$asset_manager->register_script(
			self::PAGE_SCRIPT_NAME,
			TYPES_RELPATH . '/public/page/add_term.js',
			array( 'jquery', 'toolset-utils' ),
			TYPES_VERSION,
			true
		);

		$asset_manager->enqueue_scripts( self::PAGE_SCRIPT_NAME );
	}


	/**
	 * Appends form-specific data for the JS validation script.
	 *
	 * @since 1.9
	 */
	public function render_js_validation_data() {
		wpcf_form_render_js_validation( '.validate' );
	}


	/**
	 * Render table rows with individual field group.
	 *
	 * @param Types_Field_Group_Term $field_group
	 * @param int|null $term_id ID of the term whose fields are being rendered.
	 */
	private function render_field_group_edit_page( $field_group, $term_id ) {
		$field_definitions = $field_group->get_field_definitions();

		printf(
			'<tr><th scope="row" colspan="2"><hr /><strong>%s</strong></th></tr>',
			$field_group->get_display_name()
		);

		/** @var WPCF_Field_Definition_Term $field_definition */
		foreach( $field_definitions as $field_definition ) {
			printf(
				'<tr class="form-field"><th scope="row">%s</th><td>%s</td></tr>',
				$field_definition->get_display_name(),
				$this->get_toolset_forms_field( $field_definition, self::EDIT_FORM_ID, $term_id, true )
			);
		}
	}


	/**
	 * Render table rows with individual field group.
	 *
	 * @param Types_Field_Group_Term $field_group
	 * @param int|null $term_id ID of the term whose fields are being rendered.
	 * @since 2.1
	 */
	private function render_field_group_add_page( $field_group, $term_id ) {
		$field_definitions = $field_group->get_field_definitions();

		printf(
			'<hr /><h4>%s</h4>',
			$field_group->get_display_name()
		);

		/** @var WPCF_Field_Definition_Term $field_definition */
		foreach( $field_definitions as $field_definition ) {
			printf(
				'<div class="form-field wpcf-add-term-form-field">%s</div>',
				$this->get_toolset_forms_field( $field_definition, self::ADD_FORM_ID, $term_id, false )
			);
		}
	}


	/**
	 * Get the toolset-forms markup for an individual field.
	 *
	 * @param WPCF_Field_Definition_Term $field_definition
	 * @param string $form_id ID of the form for toolset-forms.
	 * @param int|null $term_id ID of the term whose fields are being rendered.
	 * @param bool $hide_field_title Determine if toolset-forms title above the field should be displayed.
	 *
	 * @return string Markup with the field.
	 */
	private function get_toolset_forms_field( $field_definition, $form_id, $term_id, $hide_field_title ) {

		if( null == $term_id ) {
			$field = new WPCF_Field_Instance_Unsaved( $field_definition );
		} else {
			$field = new WPCF_Field_Instance_Term( $field_definition, $term_id );
		}

		$tf_renderer = new WPCF_Field_Renderer_Toolset_Forms( $field, $form_id );
		$tf_renderer->setup( array( 'hide_field_title' => (bool) $hide_field_title ) );

		return $tf_renderer->render( false );

	}


	/** Prefix for column names so we have no conflicts beyond any doubt. */
	const LISTING_COLUMN_PREFIX = 'wpcf_field_';


	private $added_listing_columns = array();


	/**
	 * Add a column for each term field on the term listing page.
	 *
	 * @param string[string] $columns Column definitions (column name => display name).
	 * @return string[string] Updated column definitions.
	 * @link https://make.wordpress.org/docs/plugin-developer-handbook/10-plugin-components/custom-list-table-columns/
	 * @since 1.9.1
	 */
	public function manage_term_listing_columns( $columns ) {

		$factory = Types_Field_Group_Term_Factory::get_instance();
		$taxonomy_slug = sanitize_text_field( wpcf_getget( 'taxonomy' ) );
		$groups = $factory->get_groups_by_taxonomy( $taxonomy_slug );

		$columns_to_insert = array();
		foreach( $groups as $group ) {
			foreach( $group->get_field_definitions() as $field_definition ) {
				$columns_to_insert[ self::LISTING_COLUMN_PREFIX . $field_definition->get_slug() ] = $field_definition->get_display_name();
			}
		}

		$this->added_listing_columns = $columns_to_insert;

		// Insert before the last column, which displays counts of posts using the term (that's probably why column
		// has the label "Count" and name "posts" :-P).
		// If there is no "posts"=>"Count" column, insert at the end.
		if( isset ( $columns['posts'] ) && 'Count' == $columns['posts'] ){
		    $columns = Types_Utils::insert_at_position( $columns, $columns_to_insert, array( 'key' => 'posts', 'where' => 'before' ) );
		} else{
		    $columns = Types_Utils::insert_at_position( $columns, $columns_to_insert, -1 );
		}

		return $columns;
	}


	/**
	 * Render single cell in a term listing table.
	 *
	 * Catch field columns by their name prefix and render field values with preview renderer.
	 *
	 * @param mixed $value ""
	 * @param string $column_name
	 * @param int $term_id
	 * @link https://make.wordpress.org/docs/plugin-developer-handbook/10-plugin-components/custom-list-table-columns/
	 * @return string Rendered HTML with the table cell content.
	 * @since 1.9.1
	 */
	public function manage_term_listing_cell( $value, $column_name, $term_id ) {

		// Deal only with our custom columns.
		if( $this->is_term_field_column( $column_name ) ) {

			try {

				$field_slug = substr( $column_name, strlen( self::LISTING_COLUMN_PREFIX ) );
				$field_definition = WPCF_Field_Definition_Factory_Term::get_instance()->load_field_definition( $field_slug );
				$field = new WPCF_Field_Instance_Term( $field_definition, $term_id );

				$renderer_args = array(
					'maximum_item_count' => 5,
					'maximum_item_length' => 30,
					'maximum_total_length' => 100
				);

				$renderer = WPCF_Field_Renderer_Factory::get_instance()->create_preview_renderer( $field, $renderer_args );

				$value = $renderer->render();

			} catch( Exception $e ) {
				// Do nothing when we're unable to load the field.
			}

		}

		return $value;
	}


	/**
	 * Determine whether a column name belongs to a term field.
	 *
	 * @param string $column_name
	 * @return bool
	 * @since 2.1
	 */
	private function is_term_field_column( $column_name ) {
		return ( substr( $column_name, 0, strlen( self::LISTING_COLUMN_PREFIX ) ) == self::LISTING_COLUMN_PREFIX );
	}


	/** How many columns with term fields will be displayed if the "autohiding" mechanism is triggered. */
	const DEFAULT_DISPLAYED_TERM_FIELD_COLUMN_COUNT = 3;


	/**
	 * Apply the column autohiding mechanism if appropriate.
	 *
	 * @param string[] $columns Column names that are supposed to be hidden.
	 * @param WP_Screen $screen
	 * @param bool $use_defaults If true, $columns doesn't hold any sensible data.
	 * @return string[] Updated column names.
	 * @since 2.1
	 */
	public function filter_hidden_columns( $columns, $screen, $use_defaults ) {

		if( !is_array( $columns ) ) {
			$columns = array();
		}

		// Determine if we should do the autohiding
		$should_autohide = $this->should_autohide_columns( $columns, $screen, $use_defaults );

		if( !$should_autohide ) {
			return $columns;
		}

		$total_term_column_count = count( $this->added_listing_columns );

		/**
		 * types_default_displayed_term_field_column_count
		 *
		 * Allows for specifying how many term field columns will be displayed when an column autohiding mechanism
		 * is triggered.
		 *
		 * @param int $column_count
		 * @param WP_Screen $screen Current screen object.
		 * @param int $total_term_column_count Total count of column with term fields available.
		 * @return int
		 * @since 2.1
		 */
		$term_field_column_display_count = (int) apply_filters(
			'types_default_displayed_term_field_column_count',
			self::DEFAULT_DISPLAYED_TERM_FIELD_COLUMN_COUNT,
			$screen,
			$total_term_column_count
		);

		$term_field_column_display_count = max( 0, $term_field_column_display_count );
		$term_field_column_display_count = min( $term_field_column_display_count, $total_term_column_count );

		// Splice the array into displayed and hidden columns
		$displayed_field_columns = array_reverse( array_keys( $this->added_listing_columns ) );
		$hidden_field_columns = array_splice( $displayed_field_columns, 0, $total_term_column_count - $term_field_column_display_count );

		// Add newly hidden ones to the result
		$columns = array_merge( $columns, $hidden_field_columns );

		// Display an annotation for the last displayed column
		if( !empty( $displayed_field_columns ) && !empty( $hidden_field_columns ) ) {
			// It's a first item because we did array_reverse
			$last_displayed_column = $displayed_field_columns[0];
			$this->annotate_last_displayed_column( $last_displayed_column );
		}

		return $columns;
	}


	/**
	 * Prefix for usermeta key that defines if the autohiding mechanism should be disabled for given screen.
	 *
	 * Use: {$prefix}{$screen->id}
	 *
	 * Stored value is boolean-like, if missing, it should be considered false.
	 *
	 * @since 2.1
	 */
	const USER_OPTION_DISABLE_COLUMN_AUTOHIDING = 'types_disable_column_autohiding_for_screen_';


	/**
	 * Determine if we should trigger the column autohiding.
	 *
	 * @param string[] $columns
	 * @param WP_Screen $screen
	 * @param bool $use_defaults
	 * @return bool
	 * @since 2.1
	 */
	private function should_autohide_columns( $columns, $screen, $use_defaults ) {

		$term_field_column_count = count( $this->added_listing_columns );

		// No autohide if there are no term field columns
		if( 0 === $term_field_column_count ) {
			return false;
		}

		// Autohide if there are no screen options stored yet
		if( $use_defaults ) {
			return true;
		}

		// No autohide if there are already hidden columns
		foreach( $columns as $column ) {
			if( $this->is_term_field_column( $column ) ) {
				return false;
			}
		}

		// No autohide if disabled explicitly by Types
		$disabled_by_types = get_user_option( self::USER_OPTION_DISABLE_COLUMN_AUTOHIDING . $screen->id );
		if( $disabled_by_types ) {
			return false;
		}

		return true;
	}


	private $is_last_displayed_column_annotated = false;


	/**
	 * Localize the Add Term page script so that it adds an annotation to given column.
	 *
	 * We can't modify column headers at the time we need this, so JS is the only way.
	 * The annotation is a question mark icon with a tooltip.
	 *
	 * @param string $column_name
	 * @since 2.1
	 */
	private function annotate_last_displayed_column( $column_name ) {
		if( $this->is_last_displayed_column_annotated ) {
			return;
		}

		wp_localize_script(
			self::PAGE_SCRIPT_NAME,
			'types_page_add_term_l10n',
			array(
				'autohidden_columns' => true,
				'last_displayed_column' => $column_name,
				'annotation' => sprintf(
					' <i class="js-wpcf-tooltip wpcf-tooltip dashicons dashicons-editor-help" data-tooltip="%s"></i>',
					__( 'Some columns with term fields have been automatically hidden. You can control their appearance through the Screen options (on top right of the page).', 'wpcf' )
				)
			)
		);

		$this->is_last_displayed_column_annotated = true;
	}


	/**
	 * This should be executed when user *updates* the screen options for hiding table columns.
	 *
	 * It signifies a deliberate action, which means that the user is already aware of the screen options and we can
	 * disable the autohiding mechanism for good.
	 *
	 * @param string $taxonomy_slug
	 * @param string[] $hidden_columns Hidden column names.
	 * @param string $screen_id ID of the screen where column autohiding might be disabled.
	 * @since 2.1
	 */
	public function maybe_disable_column_autohiding( $taxonomy_slug, $hidden_columns, $screen_id ) {

		$factory = Types_Field_Group_Term_Factory::get_instance();
		$groups = $factory->get_groups_by_taxonomy( $taxonomy_slug );

		if( empty( $groups ) || !is_array( $hidden_columns ) ) {
			// Nothing to do here
			return;
		}

		foreach( $hidden_columns as $column ) {
			if( $this->is_term_field_column( $column ) ) {
				// Some columns are hidden anyway, no need to do anything
				return;
			}
		}

		// All term field columns are being displayed on purpose - after this we'll never do the autohide.
		update_user_option( get_current_user_id(), self::USER_OPTION_DISABLE_COLUMN_AUTOHIDING . $screen_id, 1 );
	}

}
