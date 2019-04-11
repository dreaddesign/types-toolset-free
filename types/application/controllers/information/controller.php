<?php

/**
 * Types_Information_Controller
 *
 * @since 2.0
 */
class Types_Information_Controller {

	protected $information;
	protected $twig;

	private function requirements_met() {
		if(
			! current_user_can( 'manage_options' )
			|| ! apply_filters( 'types_information_table', true )
			|| $this->embedded_plugin_running()
			|| ! Types_Helper_Condition::get_post_type()
		) {
			return false;
		}

		return true;
	}

	/**
	 * We don't want to show "archive" for media/pages and "template" for media
	 *
	 * @param $columns
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public function filter_columns( $columns, $post_type ) {
		if( $post_type != 'attachment' && $post_type != 'page' ) {
			return $columns;
		}

		if( array_key_exists( 'archive', $columns ) ) {
			unset( $columns['archive'] );
		}

		if( $post_type == 'attachment' && array_key_exists( 'template', $columns ) ) {
			unset( $columns['template'] );
		}

		return $columns;
	}

	public function prepare() {
		if( ! $this->requirements_met() )
			return false;

		// filter columns for specific post types
		add_filter( 'types_information_table_columns', array( $this, 'filter_columns' ), 10, 2 );

		// twig
		$this->twig = new Types_Helper_Twig();

		// script / style
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );

		/* data files */
		$data_files = array(
			TYPES_DATA . '/information/table/template.php',
			TYPES_DATA . '/information/table/archive.php',
			TYPES_DATA . '/information/table/views.php',
			TYPES_DATA . '/information/table/forms.php',
		);

		$this->show_data_as_table_in_meta_box( $data_files );
	}

	public function on_admin_enqueue_scripts() {

		// script
		wp_enqueue_script(
			'types-information',
			TYPES_RELPATH . '/public/js/information.js',
			array( 'jquery-ui-dialog', 'wp-pointer' ),
			TYPES_VERSION,
			true
		);

		// style
		wp_enqueue_style(
			'types-information',
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

	protected function show_data_as_table_in_meta_box( $data_files ) {
		// prepare meta box
		$this->prepare_table_in_meta_box();

		// thead
		$thead_views = $this->thead_views_template_archive_views_forms();

		// load infos
		foreach( $data_files as $data_file ) {
			$this->load_data_to_table( $data_file );
		}

		// table view
		$output_inner = $this->twig->render(
			'/information/table.twig',
			array(
				'labels' => array(
					'or' => __( 'Or...', 'wpcf' )
				),
				'thead' => $thead_views,
				'table' => $this->information
			)
		);

		// Replace Placeholders
		Types_Helper_Placeholder::replace( $output_inner );

		// no "echo" because we use meta-box as output
		$this->information->render( $output_inner );
	}


	protected function show_data_as_container_in_meta_box( $data_files ) {
		// add container for warning messages
		$this->information = new Types_Information_Container( 'types-informations-container' );
		// $this->information->cache_on_hook( 'edit_post' );

		// add messages
		foreach( $data_files as $data_file ) {
			$this->load_data_to_table( $data_file );
		}

		// outer box
		$output_meta_box = new Types_Helper_Output_Meta_Box();
		$output_meta_box->set_id( $this->information->get_id() );
		$output_meta_box->set_title( __( 'Front-end Display', 'wpcf' ) );
		// $output_meta_box->set_css_class( 'types-table-in-meta-box' );

		$this->information->set_output_container( $output_meta_box );

		$output_inner = $this->twig->render(
			'/information/single.twig',
			array(
				'icon' => 'dashicons dashicons-warning',
				'information' => $this->information
			)
		);

		// Replace Placeholders
		Types_Helper_Placeholder::replace( $output_inner );

		// no "echo" because we use meta-box as output
		$this->information->render( $output_inner );
	}

	protected function load_data_to_table( $path ) {
		$data = require( $path );

		foreach( $data as $msg_id => $msg_data ) {
			$msg = new Types_Information_Message();
			$msg_data['id'] = $msg_id;
			$msg->data_import( $msg_data );
			$this->information->add_message( $msg );
		}
	}

	protected function thead_views_template_archive_views_forms() {

		$thead_data =  require( TYPES_DATA . '/information/table/question-marks.php' );
		$views = array();

		$post_type = Types_Helper_Condition::get_post_type();

		$allowed_columns = apply_filters( 'types_information_table_columns', array_fill_keys( array( 'template', 'archive', 'views', 'forms' ), '' ), $post_type->name );

		foreach( $thead_data as $key => $column ) {
			if( ! array_key_exists( $key, $allowed_columns ) )
				unset( $thead_data[$key] );
		}

		foreach( $thead_data as $data ) {
			$views[] = $this->twig->render(
				'/information/table/thead-cell.twig',
				$data
			);
		}

		return $views;
	}

	protected function prepare_table_in_meta_box() {
		// add dashboard
		$this->information = new Types_Information_Table( 'types-information-table' );

		// save on edit post
		//$this->information->cache_on_hook( 'edit_post' );

		// we want to display dashboard in a meta-box
		$output_meta_box = new Types_Helper_Output_Meta_Box();
		$output_meta_box->set_id( $this->information->get_id() );
		$output_meta_box->set_title( __( 'Front-end Display', 'wpcf' ) );
		$output_meta_box->set_css_class( 'types-table-in-meta-box' );

		$this->information->set_output_container( $output_meta_box );
	}

	/**
	 * Check if any embedded plugin is running.
	 *
	 * @todo Would be better placed in a helper.
	 * @return bool
	 */
	protected function embedded_plugin_running() {

		// check Layouts
		if( defined( 'WPDDL_EMBEDDED' ) )
			return true;

		// check CRED
		if( defined( 'CRED_FE_VERSION' ) && class_exists('CRED_Admin') === false )
			return true;

		// check Views
		if( defined( 'WPV_VERSION' ) ) {
			global $WP_Views;

			if( is_object( $WP_Views ) && method_exists( $WP_Views, 'is_embedded' ))
				return $WP_Views->is_embedded();
		}
		
		return false;
	}
}