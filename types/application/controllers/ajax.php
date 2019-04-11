<?php

/**
 * Main AJAX call controller for Types.
 *
 * This class can be used in any way only after the Common Library is loaded.
 *
 * Please read the important usage instructions for the superclass:
 *
 * @inheritdoc
 *
 * @since 2.0
 */
class Types_Ajax extends Toolset_Ajax {
	const HANDLER_CLASS_PREFIX = 'Types_Ajax_Handler_';

	// Action names
	const CALLBACK_FIELD_CONTROL_ACTION = 'field_control_action';
	const CALLBACK_CHECK_SLUG_CONFLICTS = 'check_slug_conflicts';
	const CALLBACK_SETTINGS_ACTION = 'settings_action';
	const CALLBACK_M2M_MIGRATION_PREVIEW_RELATIONSHIPS = 'm2m_migration_preview_relationships';
	const CALLBACK_M2M_MIGRATION_PREVIEW_ASSOCIATIONS = 'm2m_migration_preview_associations';
	const CALLBACK_CUSTOM_FIELDS_ACTION = 'custom_fields_action';
	const CALLBACK_RELATIONSHIPS_ACTION = 'relationships_action';
	const CALLBACK_RELATED_CONTENT_ACTION = 'related_content_action';
	const CALLBACK_FIELD_GROUP_EDIT_ACTION = 'field_group_edit_action';
	const CALLBACK_REPEATABLE_GROUP = 'repeatable_group';
	const CALLBACK_POST_REFERENCE_FIELD = 'post_reference_field';


	private static $callbacks = array(
		self::CALLBACK_FIELD_CONTROL_ACTION,
		self::CALLBACK_CHECK_SLUG_CONFLICTS,
		self::CALLBACK_SETTINGS_ACTION,
		self::CALLBACK_M2M_MIGRATION_PREVIEW_RELATIONSHIPS,
		self::CALLBACK_M2M_MIGRATION_PREVIEW_ASSOCIATIONS,
		self::CALLBACK_CUSTOM_FIELDS_ACTION,
		self::CALLBACK_RELATIONSHIPS_ACTION,
		self::CALLBACK_RELATED_CONTENT_ACTION,
		self::CALLBACK_FIELD_GROUP_EDIT_ACTION,
		self::CALLBACK_REPEATABLE_GROUP,
		self::CALLBACK_POST_REFERENCE_FIELD,
	);


	private static $types_instance;


	public static function get_instance() {
		if( null === self::$types_instance ) {
			self::$types_instance = new self();
		}
		return self::$types_instance;
	}

	/**
	 * @inheritdoc
	 * @param bool $capitalized
	 * @return string
	 * @since m2m
	 */
	protected function get_plugin_slug( $capitalized = false ) {
		return ( $capitalized ? 'Types' : 'types' );
	}


	/**
	 * @inheritdoc
	 * @return array
	 * @since m2m
	 */
	protected function get_callback_names() {
		return self::$callbacks;
	}


	/**
	 * Handles all initialization of everything except AJAX callbacks itself that is needed when
	 * we're DOING_AJAX.
	 *
	 * Since this is executed on every AJAX call, make sure it's as lightweight as possible.
	 *
	 * @since 2.1
	 */
	protected function additional_ajax_init() {

		// On the Add Term page, we need to initialize the page controller WPCF_GUI_Term_Field_Editing
		// so that it saves term fields (if there are any).
		add_action( 'create_term', array( $this, 'prepare_for_term_creation' ) );

		add_action( 'updated_user_meta', array( $this, 'capture_columnshidden_update' ), 10, 4 );

		// Handle partially refactored AJAX callbacks coming from wpcf_ajax_embedded()
		// or from wpcf_ajax(). The wp_ajax_wpcf_ajax action will be reached only if the $fallthrough variables
		// in those functions are set to true (which means that the call was not handled).
		add_action( 'wp_ajax_wpcf_ajax', array( $this, 'do_legacy_wpcf_ajax' ) );
	}


	/**
	 * On the Add Term page, we need to initialize the page controller WPCF_GUI_Term_Field_Editing
	 * so that it saves term fields (if there are any).
	 *
	 * @since 2.1
	 */
	public function prepare_for_term_creation() {

		// Takes care of the rest, mainly we're interested about the create_{$taxonomy} action which follows
		// immediately after create_term.
		//
		// On actions fired on the Add Term page, the action POST variable is allways add-tag and the screen is set
		// to edit-{$taxonomy}. When creating the term on the post edit page, for example, the screen is not set. We use
		// this to further limit the resource wasting. However, initializing the controller even if it's not supposed to
		// will not lead to any errors - it gives up gracefully.
		$action = toolset_getpost( 'action' );
		$screen = toolset_getpost( 'screen', null );
		if( 'add-tag' == $action && null !== $screen ) {
			WPCF_GUI_Term_Field_Editing::initialize();
		}

	}


	/**
	 * When updating screen options with hidden listing columns, we may need to store additional data.
	 *
	 * See WPCF_GUI_Term_Field_Editing::maybe_disable_column_autohiding() for details.
	 *
	 * @param mixed $meta_id Ignored.
	 * @param mixed $object_id Ignored.
	 * @param string $meta_key Meta key.
	 * @param mixed $_meta_value Meta value. We expect it to be an array.
	 * @since 2.1
	 */
	public function capture_columnshidden_update(
		/** @noinspection PhpUnusedParameterInspection */ $meta_id, $object_id, $meta_key, $_meta_value )
	{
		// We're looking for a meta_key that looks like "manage{$page_name}columnshidden".
		$txt_columnshidden = 'columnshidden';
		$is_columnshidden_option = ( 0 == strcmp( $txt_columnshidden, substr( $meta_key, strlen( $txt_columnshidden ) * -1 ) ) );

		if( $is_columnshidden_option ) {

			// Extract the page name from the meta_key
			$strip_begin = strlen( 'manage' );
			$strip_end = strlen( $txt_columnshidden );
			$page_name = substr( $meta_key, $strip_begin, strlen( $meta_key ) - ( $strip_begin + $strip_end ) );

			// Determine if we're editing a taxonomy
			$txt_edit = 'edit-';
			$txt_edit_len = strlen( $txt_edit );
			$is_tax_edit_page = ( 0 == strcmp( $txt_edit, substr( $page_name, 0, $txt_edit_len ) ) );

			// This is not 100% certain but attempting to handle a taxonomy that doesn't exist does no harm.
			if( $is_tax_edit_page ) {

				// Now we know that we need to perform the extra action.
				$taxonomy_name = substr( $page_name, $txt_edit_len );
				$edit_term_page_extension = WPCF_GUI_Term_Field_Editing::get_instance();
				$edit_term_page_extension->maybe_disable_column_autohiding( $taxonomy_name, $_meta_value, $page_name );
			}
		}
	}


	/**
	 * This offers a possibility to handle legacy AJAX wp_ajax_wpcf_ajax calls
	 * if they're not handled in the legacy code anymore.
	 *
	 * Note that the method needs to always finish with die() to keep consistency with the legacy code.
	 *
	 * @since 2.2.16
	 */
	public function do_legacy_wpcf_ajax() {
		die();
	}
}