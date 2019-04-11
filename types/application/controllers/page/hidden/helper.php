<?php

/**
 * Types_Page_Hidden_Helper
 *
 * @since 2.0
 */
class Types_Page_Hidden_Helper extends Types_Page_Abstract {

	private static $instance;

	private $redirect_url = false;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->add_sneaky_hidden_helper();
		}
	}

	public function add_sneaky_hidden_helper() {
		add_submenu_page(
			'options.php', // hidden
			$this->get_title(),
			$this->get_title(),
			$this->get_required_capability(),
			$this->get_page_name(),
			array( $this, $this->get_load_callback() )
		);
	}

	public function get_title() {
		return 'Loading...';
	}

	public function get_render_callback() {
		return null;
	}

	public function get_load_callback() {
		return 'route';
	}

	public function get_page_name() {
		return Types_Admin_Menu::PAGE_NAME_HELPER;
	}

	public function get_required_capability() {
		return 'manage_options';
	}

	public function route() {

		$this->redirect_url = false;

		if( isset( $_GET['action'] ) && isset( $_GET['type'] ) ) {
			
			$action	= sanitize_text_field( $_GET['action'] );
			$type	= sanitize_text_field( $_GET['type'] );

			switch( $action ) {
				case 'new-form':
					$this->redirect_url = $this->new_form_action( $type );
					break;
				case 'new-view':
					$this->redirect_url = $this->new_view_action( $type );
					break;
				case 'new-layout-template':
					$this->redirect_url = $this->new_layout_template_action( $type );
					break;
				case 'new-content-template':
					$this->redirect_url = $this->new_content_template_action( $type );
					break;
				case 'new-wordpress-archive':
					$this->redirect_url = $this->new_wordpress_archive_action( $type );
					break;
				case 'new-post-field-group':
					$this->redirect_url = $this->new_post_field_group_action( $type );
					break;
			}

		}

		$this->redirect_url = $this->add_params_to_url( $this->redirect_url );
		$this->redirect();
	}

	private function new_form_action( $type ) {
		$new_form = new Types_Helper_Create_Form();

		if( $id = $new_form->for_post( $type ) ) {
			return get_edit_post_link( $id, 'Please WordPress, be so nice and do not encode &.' );
		}

		return false;
	}

	private function new_view_action( $type ) {
		$new_view = new Types_Helper_Create_View();

		if( $id = $new_view->for_post( $type ) ) {
			return admin_url() . 'admin.php?page=views-editor&view_id='.$id;
		}

		return false;
	}

	private function new_layout_template_action( $type ) {
		$new_layout = new Types_Helper_Create_Layout();

		if( $id = $new_layout->for_post( $type ) ) {
			return admin_url() . 'admin.php?page=dd_layouts_edit&action=edit&layout_id='.$id;
		}

		return false;
	}

	private function new_content_template_action( $type ) {
		$new_layout = new Types_Helper_Create_Content_Template();

		if( $id = $new_layout->for_post( $type ) ) {
			return admin_url() . 'admin.php?page=ct-editor&ct_id='.$id;
		}

		return false;
	}

	private function new_wordpress_archive_action( $type ) {
		$new_wordpress_archive = new Types_Helper_Create_Wordpress_Archive();

		if( $id = $new_wordpress_archive->for_post( $type ) ) {
			return admin_url() . 'admin.php?page=view-archives-editor&view_id='.$id;
		}

		return false;
	}

	private function new_post_field_group_action( $type ) {

		$type_object = get_post_type_object( $type );
		$title = sprintf( __( 'Field Group for %s', 'wpcf' ), $type_object->labels->name );
		$name = sanitize_title( $title );

		$new_post_field_group = Types_Field_Group_Post_Factory::get_instance()->create( $name, $title, 'publish' );

		if( ! $new_post_field_group )
			return false;

		$new_post_field_group->assign_post_type( $type );

		$url = isset( $_GET['ref'] )
			? 'admin.php?page=wpcf-edit&group_id='.$new_post_field_group->get_id().'&ref='.sanitize_text_field( $_GET['ref'] )
			: 'admin.php?page=wpcf-edit&group_id='.$new_post_field_group->get_id();

		return admin_url( $url );
	}

	private function add_params_to_url( $url ) {
		// forward parameter toolset_help_video
		if( isset( $_GET['toolset_help_video'] ) )
			$url = add_query_arg( 'toolset_help_video', sanitize_text_field( $_GET['toolset_help_video'] ), $url );

		// forward parameter ref
		if( isset( $_GET['ref'] ) )
			$url = add_query_arg( 'ref', sanitize_text_field( $_GET['ref'] ), $url );

		return $url;
	}

	/**
	 * hidden page, but only when redirect after doing what we have to do
	 */
	private function redirect() {
		// shouldn't happen but if we have no redirect_url here: goto admin main page.
		if( ! $this->redirect_url )
			$this->redirect_url = admin_url();

		die( '<script type="text/javascript">'.'window.location = "' . $this->redirect_url . '";'.'</script>' );
	}
}