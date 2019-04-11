<?php

/**
 * "Dashboard" page controller.
 *
 * @since 2.1
 */
final class Types_Page_Dashboard extends Types_Page_Abstract {

	protected $dashboard;
	protected $twig;

	protected $table_toolset = false;
	protected $table_3rd = false;
	protected $table_wordpress;

	protected $types_by_toolset;
	protected $types_by_3rd;
	protected $types_by_wordpress;

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
			add_filter( 'toolset_filter_register_menu_pages', array( Types_Page_Dashboard::$instance, 'register_page_dashboard_in_menu' ), 1000 );
			add_action( 'load-toplevel_page_toolset-dashboard', array( Types_Page_Dashboard::$instance, 'on_load_page' ) );
			add_filter( 'set-screen-option', array( Types_Page_Dashboard::$instance, 'screen_settings_save') , 11, 3);
		}
		return self::$instance;
	}

	private function __construct() { }

	private function __clone() { }

	public function on_load_page() {
		add_filter( 'screen_settings', array( Types_Page_Dashboard::$instance, 'screen_settings' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( Types_Page_Dashboard::$instance, 'on_admin_enqueue_scripts' ) );

		$this->help_information();
	}

	public function register_page_dashboard_in_menu( $pages ) {
		array_unshift( $pages, array(
			'slug'			=> 'toolset-dashboard',
			'menu_title'	=> $this->get_menu_title(),
			'page_title'	=> $this->get_title(),
			'callback'		=> $this->get_render_callback()
		) );

		return $pages;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_title() {
		return __( 'Toolset Dashboard', 'wpcf' );
	}

	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_menu_title() {
		return __( 'Dashboard', 'wpcf' );
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_render_callback() {
		return array( $this, 'render_page' );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_page_name() {
		return Types_Admin_Menu::PAGE_NAME_DASHBOARD;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_required_capability() {
		return 'manage_options'; // todo better role/cap handling
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_load_callback() {
		return null;
	}

	
	public function on_admin_enqueue_scripts() {
		$main_handle = 'types-page-dashboard-main';

		// script
		wp_enqueue_script(
			$main_handle,
			TYPES_RELPATH . '/public/js/information.js',
			array( 'jquery-ui-dialog', 'wp-pointer' ),
			TYPES_VERSION,
			true
		);

		// style
		wp_enqueue_style(
			$main_handle,
			TYPES_RELPATH . '/public/css/information.css',
			array( 'wp-jquery-ui-dialog', 'wp-pointer' ),
			TYPES_VERSION
		);

		// load icons
		wp_enqueue_style(
			'onthegosystems-icons',
			WPCF_EMBEDDED_TOOLSET_RELPATH . '/onthego-resources/onthegosystems-icons/css/onthegosystems-icons.css',
			array(),
			TYPES_VERSION
		);
	}


	private function get_twig() {
		if( $this->twig == null )
			$this->twig = new Types_Helper_Twig();

		return $this->twig;
	}


	/**
	 * @inheritdoc
	 * 
	 * @since 2.1
	 */
	public function render_page() {

		$context = $this->build_page_context();

		echo $this->get_twig()->render( '/page/dashboard/main.twig', $context );
	}


	/**
	 * Build the context for main page template.
	 *
	 * @return array Page context. See the main page template for details.
	 * @since 2.1
	 */
	private function build_page_context() {
		$this->get_dashboard();

		$context = array(
			'page' => self::get_instance(),
			'table_toolset' => $this->table_toolset,
			'table_3rd' => $this->table_3rd,
			'table_wordpress' => $this->table_wordpress,
			'labels' => array(
				'create_type' => __( 'Add new post type', 'wpcf' ),
				'msg_no_custom_post_types' =>
					__( 'To get started, create your first custom type. Then, you will be able to add fields and taxonomy and design how it displays.', 'wpcf' )
			)

		);

		return $context;
	}

	/**
	 * Types by Toolset
	 *
	 * @return array
	 */
	private function get_types_by_toolset() {
		if( $this->types_by_toolset !== null )
			return $this->types_by_toolset;

		$post_type_option = new Types_Utils_Post_Type_Option();

		$cpts_raw = ! isset( $_GET['toolset-dashboard-simulate-no-custom-post-types'] )
			? $post_type_option->get_post_types()
			: array();

		// remove buildin types
		$cpts_raw = array_diff_key( $cpts_raw, $this->get_types_by_wordpress() );

		$cpts = array();

		foreach( $cpts_raw as $cpt_raw ) {
			$post_type = new Types_Post_Type( $cpt_raw['slug'] );
			// only use active post types
			if( isset( $post_type->name ) )
				$cpts[$cpt_raw['slug']] = $post_type;
		}

		uasort( $cpts, array( $this, 'sort_post_types_by_name' ) );

		$this->types_by_toolset = $cpts;
		return $this->types_by_toolset;
	}

	/**
	 * Types by WordPress
	 * @return array
	 */
	private function get_types_by_wordpress() {
		if( $this->types_by_wordpress !== null )
			return $this->types_by_wordpress;

		$cpts_raw = array(
			'post' => array(
				'slug'      => 'post',
				'_buildin'  => 1
			),
			'page' => array(
				'slug'      => 'page',
				'_buildin'  => 1
			),
			'attachment' => array(
				'slug'      => 'attachment',
				'_buildin'  => 1
			),
		);

		$cpts = array();
		foreach( $cpts_raw as $cpt_raw ) {
			$post_type = new Types_Post_Type( $cpt_raw['slug'] );
			// only use active post types
			if( isset( $post_type->name ) )
				$cpts[$cpt_raw['slug']] = $post_type;
		}

		uasort( $cpts, array( $this, 'sort_post_types_by_name' ) );
		$this->types_by_wordpress = $cpts;

		return $this->types_by_wordpress;
	}

	/**
	 * Types by 3rd (by themes/plugins)
	 * @return array
	 */
	private function get_types_by_3rd() {
		if( $this->types_by_3rd !== null )
			return $this->types_by_3rd;

		$cpts_raw = get_post_types( array( 'public' => true ) );
		$cpts = array();
		foreach( $cpts_raw as $cpt_slug => $cpt_raw ) {
			$post_type = new Types_Post_Type( $cpt_slug );
			// only use active post types
			if( isset( $post_type->name ) )
				$cpts[$cpt_slug] = $post_type;
		}

		$cpts = array_diff_key( $cpts, $this->get_types_by_wordpress(), $this->get_types_by_toolset() );

		uasort( $cpts, array( $this, 'sort_post_types_by_name' ) );
		$this->types_by_3rd = $cpts;

		return $this->types_by_3rd;
	}

	private function sort_post_types_by_name( $a, $b ) {
		return strcasecmp( $a->name, $b->name ) > 0 ? true : false;
	}

	private function get_post_types_filtered_by_screen_options( $cpts = false ) {
		if( $cpts === false )
			$cpts = array_merge( $this->get_types_by_toolset(), $this->get_types_by_wordpress(), $this->get_types_by_3rd() );

		$cpts_filtered = array();

		$user = get_current_user_id();
		$user_settings = get_user_meta($user, 'toolset_dashboard_screen_post_types', true );

		// no user settings yet
		if( empty( $user_settings ) ) {

			// by default no media
			if( isset( $cpts['attachment'] ) ) {
				unset( $cpts['attachment'] );
			}


			$cpts_filtered = $cpts;
		} else {
			foreach( $cpts as $post_type => $cpt ) {
				// default for post/page/media is unchecked
				if( ! isset( $user_settings[$post_type] )
					&& ( $post_type == 'post' || $post_type == 'page' || $post_type == 'attachment' )
				) continue;

				if( !empty( $user_settings ) ) {

					if( ! isset( $user_settings[$post_type] ) // default = checked
					    || $user_settings[$post_type] == 'on' )   // checked by user
						$cpts_filtered[$post_type] = $cpt;
				}
			}
		}

		return $cpts_filtered;
	}

	private function get_dashboard() {
		// Types by Toolset
		$post_types = $this->get_post_types_filtered_by_screen_options( $this->get_types_by_toolset() );

		if( ! empty( $post_types ) )
			$this->table_toolset = $this->get_dashboard_types_table(
				$post_types,
				__( 'Custom post types that you created with Toolset', 'wpcf' )
			);


		// Types by 3rd
		$post_types = $this->get_post_types_filtered_by_screen_options( $this->get_types_by_3rd() );

		if( ! empty( $post_types ) )
			$this->table_3rd = $this->get_dashboard_types_table(
				$post_types,
				__( 'Custom post types created by the theme and other plugins', 'wpcf' ),
				false
			);


		// Types by Wordpress
		$post_types = $this->get_post_types_filtered_by_screen_options( $this->get_types_by_wordpress() );

		if( ! empty( $post_types ) )
			$this->table_wordpress = $this->get_dashboard_types_table(
				$post_types,
				__( 'Built-in post types created by WordPress', 'wpcf' ),
				false
			);

	}

	protected function load_data_to_table( $path, &$info ) {
		$data = require( $path );

		foreach( $data as $msg_id => $msg_data ) {
			$msg = new Types_Information_Message();
			$msg_data['id'] = $msg_id;
			$msg->data_import( $msg_data );
			$info->add_message( $msg );
		}
	}

	public function screen_settings( $status, $args ) {
		$return = $status;

		$cpts_filtered = $this->get_post_types_filtered_by_screen_options();

		// Types by Toolset
		$cpts = $this->get_types_by_toolset();
		if( ! empty( $cpts ) ) {
			$string_legend = __( 'Custom post types that you created with Toolset', 'wpcf' );
			$return .= $this->screen_settings_fieldset( $cpts, $cpts_filtered, $string_legend );
		}

		// Types by 3rd
		$cpts = $this->get_types_by_3rd();
		if( ! empty( $cpts ) ) {
			$string_legend = __( 'Custom post types created by the theme and other plugins', 'wpcf' );
			$return .= $this->screen_settings_fieldset( $cpts, $cpts_filtered, $string_legend );
		}

		// Types by WordPress
		$cpts = $this->get_types_by_wordpress();
		$string_legend = __( 'Built-in post types created by WordPress', 'wpcf' );
		$return .= $this->screen_settings_fieldset( $cpts, $cpts_filtered, $string_legend );

		$return .= get_submit_button( __( 'Apply' ), 'button button-primary', 'screen-options-apply', false );
		return $return;
	}
	
	private function screen_settings_fieldset( $cpts, $cpts_filtered, $legend ) {
		$string = '
        <fieldset>
        <legend>' . $legend . '</legend>
        <div class="metabox-prefs">
        <div><input type="hidden" name="wp_screen_options[option]" value="toolset_dashboard_screen_post_types" /></div>
        <div><input type="hidden" name="wp_screen_options[value]" value="yes" /></div>
        <div class="toolset-dashboard-screen-post-types">';
		foreach( $cpts as $cpt ) {
			$checked = isset( $cpts_filtered[$cpt->get_name()] ) ? ' checked="checked" ' : ' ';
			$string .= '<input type="hidden" value="off" name="toolset_dashboard_screen_post_types['.$cpt->get_name().']" />';
			$string .= '<label for="toolset-dashboard-screen-post-type-'.$cpt->get_name().'"><input type="checkbox"' . $checked . 'value="on" name="toolset_dashboard_screen_post_types['.$cpt->get_name().']" id="toolset-dashboard-screen-post-type-'.$cpt->get_name().'" /> '.$cpt->name.'</label>';
		}
		$string .= '</div>
        </div>
        </fieldset>
        <br class="clear">
        ';

		return $string;
	}

	public function screen_settings_save($pre_save_value, $option, $value) {
		if ( 'toolset_dashboard_screen_post_types' == $option ) {
			if ( is_array( $_POST['toolset_dashboard_screen_post_types'] ) ) {
				$toolset_dashboard_screen_post_types = array();
				foreach( $_POST['toolset_dashboard_screen_post_types'] as $tdspt_key => $tdspt_value ) {
					$tdspt_key = sanitize_text_field( $tdspt_key );
					$tdspt_value = sanitize_text_field( $tdspt_value );
					$toolset_dashboard_screen_post_types[ $tdspt_key ] = $tdspt_value;
				}
			} else {
				$toolset_dashboard_screen_post_types = sanitize_text_field( $_POST['toolset_dashboard_screen_post_types'] );
			}
			$value = $toolset_dashboard_screen_post_types;
			return $value;
		}
		return $pre_save_value;
	}


	private function help_information() {
		$title = __('Toolset Dashboard', 'wpcf');
		$help_content = $this->get_twig()->render(
			'/page/dashboard/help.twig',
			array( 'title' => $title )
		);

		$screen = get_current_screen();
		$screen->add_help_tab(
			array(
				'id'		=> 'toolset-dashboard-information',
				'title'		=> $title,
				'content'	=> $help_content,
			)
		);
	}

	/**
	 * @param $post_types
	 * @param $headline
	 *
	 * @return string
	 */
	private function get_dashboard_types_table( $post_types, $headline, $post_type_edit_link = true ) {
		// documentation urls
		$documentation_urls = include( TYPES_DATA . '/documentation-urls.php' );

		// add links to use analytics
		Types_Helper_Url::add_urls( $documentation_urls );

		// set analytics medium
		Types_Helper_Url::set_medium( 'dashboard' );

		/* messages */
		$messages_files = array(
			TYPES_DATA . '/dashboard/table/template.php',
			TYPES_DATA . '/dashboard/table/archive.php',
			TYPES_DATA . '/dashboard/table/views.php',
			TYPES_DATA . '/dashboard/table/forms.php',
		);

		// add dashboard
		$rows = '';

		foreach( $post_types as $post_type ) {
			$info_post_type = new Types_Information_Table( 'types-information-table' );
			Types_Helper_Condition::set_post_type( $post_type->get_name() );
			Types_Helper_Placeholder::set_post_type( $post_type->get_name() );

			foreach( $messages_files as $message_file ) {
				$this->load_data_to_table( $message_file, $info_post_type );
			}

			$row = $this->get_twig()->render(
				'/page/dashboard/table/tbody-row.twig',
				array(
					'labels'    => array(
						'or'                 => __( 'Or...', 'wpcf' ),
						'create_taxonomy'    => __( 'Create taxonomy', 'wpcf' ),
						'create_field_group' => __( 'Create field group', 'wpcf' ),
						'no_archive_for'     => __( 'No archive available for %s', 'wpcf' ),
					),
					'admin_url' => admin_url(),
					'post_type' => $post_type,
					'table'     => $info_post_type,
					'post_type_edit_link' => $post_type_edit_link
				)
			);

			Types_Helper_Placeholder::replace( $row );
			$rows .= $row;
		}


		// table view
		$data_thead          = require( TYPES_DATA . '/dashboard/table/head.php' );
		$table = $this->get_twig()->render(
			'/page/dashboard/table.twig',
			array(
				'labels'    => array(
					'headline' => $headline,
					'admin'    => __( 'WordPress admin', 'wpcf' ),
					'frontend' => __( 'Front-end', 'wpcf' ),
					'or'       => __( 'Or...', 'wpcf' ),
				),
				'admin_url' => admin_url(),
				'thead'     => $data_thead,
				'rows'      => $rows
			)
		);

		return $table;
	}
}