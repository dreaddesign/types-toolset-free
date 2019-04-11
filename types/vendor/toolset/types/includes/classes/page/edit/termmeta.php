<?php

/**
 * Edit Term Field Group page handler.
 *
 * This is a wrapper around an implementation taken from the legacy code. All of it needs complete refactoring.
 *
 * @since 1.9
 */
final class WPCF_Page_Edit_Termmeta extends WPCF_Page_Abstract {

	const PAGE_NAME = 'wpcf-termmeta-edit';

	/**
	 * Name of the form rendered by wpcf_form.
	 */
	const FORM_NAME = 'wpcf_form_termmeta_fields';


	/**
	 * @return WPCF_Page_Edit_Termmeta
	 */
	public static function get_instance() {
		return parent::get_instance();
	}
	
	
	public function add_submenu_page() {
		$page = array(
			'slug'						=> self::PAGE_NAME,
			'menu_title'				=> $this->get_menu_title(),
			'page_title'				=> $this->get_menu_title(),
			'callback'					=> array( $this, 'page_handler' ),
			'load_hook'					=> array( $this, 'load_hook' ),
			'capability'				=> WPCF_TERM_FIELD_EDIT,
			'contextual_help_legacy'	=> $this->get_contextual_help_legacy(),
			'contextual_help_hook'		=> array( $this, 'add_contextual_help' )
		);
		$capability = $page['capability'];
		$wpcf_capability = apply_filters( 'wpcf_capability', $capability, $page, $page['slug'] );
		$wpcf_capability = apply_filters( 'wpcf_capability' . $page['slug'], $capability, $page, $page['slug'] );
		$page['capability'] = $wpcf_capability;
		return $page;
	}
	
	public function load_hook() {
		
		$this->prepare_form_maybe_redirect();
		
		wpcf_admin_enqueue_group_edit_page_assets();
		
	}


	public function initialize_ajax_handler() {
		new WPCF_Page_Edit_Termmeta_Form();
	}


	public function get_menu_title() {
		return __( 'Edit Term Field Group', 'wpcf' );
	}


	public function get_page_title( $purpose = 'edit' ) {
		switch ( $purpose ) {
			case 'add':
				return __( 'Add New Term Field Group', 'wpcf' );
			case 'view':
				return __( 'View Term Field Group', 'wpcf' );
			default:
				return __( 'Edit Term Field Group', 'wpcf' );
		}
	}


	public function page_handler() {

		// Following code taken from the legacy parts. Needs refactoring.

		// By now we expect that prepare_form_maybe_redirect() was already called. If not, something went terribly wrong.
		if( null == $this->wpcf_admin ) {
			return;
		}

		// Well this doesn't look right.
		$post_type = current_filter();

		// Start rendering the page.

		// Header and title
		$page_purpose = $this->wpcf_admin->get_page_purpose();
		$add_new_button = ( 'edit' == $page_purpose ) ? array( 'page' => self::PAGE_NAME ) : false;
		wpcf_add_admin_header( $this->get_page_title( $page_purpose ), $add_new_button );

		// Display WPML admin notices if there are any.
		wpcf_wpml_warning();

		// Transform the form data into an Enlimbo form
		$form = wpcf_form( self::FORM_NAME, $this->form );

		// Dark magic happens here.
		echo '<form method="post" action="" class="wpcf-fields-form wpcf-form-validate js-types-show-modal">';
		wpcf_admin_screen( $post_type, $form->renderForm() );
		echo '</form>';

		wpcf_add_admin_footer();

	}

	/**
	 * @var null|array
	 */
	private $form = null;

	/**
	 * @var WPCF_Page_Edit_Termmeta_Form
	 */
	private $wpcf_admin = null;


	/**
	 * Prepare the form data.
	 *
	 * That includes saving, which may also include redirecting to the edit page with newly created group's ID
	 * in a GET parameter.
	 */
	public function prepare_form_maybe_redirect() {

		// Following code taken from the legacy parts. Needs refactoring.

		require_once WPCF_INC_ABSPATH . '/fields.php';
		require_once WPCF_INC_ABSPATH . '/fields-form.php';
		require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.edit.custom.fields.group.php';

		$wpcf_admin = new WPCF_Page_Edit_Termmeta_Form();
		$wpcf_admin->init_admin();

		$this->form = $wpcf_admin->form();
		$this->wpcf_admin = $wpcf_admin;
	}
	
	public function add_contextual_help() {
		
		$screen = get_current_screen();
	
		if ( is_null( $screen ) ) {
			return;
		}
		
		$args = array(
			'title'		=> __( 'Term Field Group', 'wpcf' ),
			'id'		=> 'wpcf',
			'content'	=> $this->get_contextual_help_legacy(),
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );

		/**
		 * Need Help section for a bit advertising
		 *
		 * @note this is available because in wpcf_admin_toolset_register_menu_pages we are requiring once WPCF_ABSPATH . '/help.php', mind that when refactoring
		 */
		$args = array(
			'title'		=> __( 'Need More Help?', 'wpcf' ),
			'id'		=> 'custom_fields_group-need-help',
			'content'	=> wpcf_admin_help( 'need-more-help' ),
			'callback'	=> false,
		);
		$screen->add_help_tab( $args );
		
	}
	
	
	public function get_contextual_help_legacy() {
		
		$contextual_help = ''
			.__('This is the edit page for your Term Field Groups.', 'wpcf')
			.PHP_EOL
			.PHP_EOL
			. __('On this page you can create and edit your groups. To create a group, do the following:', 'wpcf')
			.'<ol><li>'
			. __('Add a Title.', 'wpcf')
			.'</li><li>'
			. __('Choose where to display your group. You can attach this to any taxonomy.', 'wpcf')
			.'</li><li>'
			. __('To add a field, click on "Add New Field" and choose the field you desire. This will be added to your Term Field Group.', 'wpcf')
			.'</li><li>'
			. __('Add information about your Term Field.', 'wpcf')
			.'</li></ol>'
			.'<h3>' . __('Tips', 'wpcf') .'</h3>'
			.'<ul><li>'
			. __('To ensure a user fills out a field, check Required in Validation section.', 'wpcf')
			.'</li><li>'
			. __('Once you have created a field, it will be saved for future use under "Choose from previously created fields" of "Add New Field" dialog.', 'wpcf')
			.'</li><li>'
			. __('You can drag and drop the order of your term fields.', 'wpcf')
			.'</li></ul>';
			
		return wpautop( $contextual_help );
	}

}