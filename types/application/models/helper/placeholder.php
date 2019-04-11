<?php

/**
 * Types_Helper_Placeholder
 *
 * @since 2.0
 */
class Types_Helper_Placeholder {

	private static $cache = array();

	private static $post_type;

	public static function set_post_type( $posttype = false ) {

		if( ! $posttype ) {
			global $typenow;

			$posttype = isset( $typenow ) && ! empty( $typenow ) ? $typenow : false;
		}

		if( $posttype )
			self::$post_type = get_post_type_object( $posttype );
	}

	public static function replace( &$original ) {
		if( self::$post_type == null )
			self::set_post_type();

		if( is_array( $original ) ) {
			foreach( $original as &$nested ) {
				self::replace( $nested );
			}
			return;
		}

		// skip if there are no placeholders
		if( strpos( $original, '%' ) === false )
			return;


		// placeholders
		$admin_url = admin_url();
		$placeholders = array(
			'%POST-LABEL-PLURAL%'               => self::$post_type->labels->name,
			'%POST-PERMALINK%'                  => self::get_permalink(),
			'%POST-ARCHIVE-PERMALINK%'          => self::get_archive_permalink(),
			'%POST-LABEL-SINGULAR%'             => self::$post_type->labels->singular_name,
			'%POST-TYPE-NAME%'                  => self::$post_type->name,
			'%THEME-NAME%'                      => wp_get_theme(),
			'%POST-TEMPLATE-FILE%'              => '<nobr>' . self::get_post_template_file() . '</nobr>',
			'%POST-ARCHIVE-FILE%'               => '<nobr>' . self::get_post_archive_file() . '</nobr>',
			'%POST-FORMS-LIST%'                 => self::get_post_type_forms_list(),
			'%POST-CREATE-FORM%'                => self::add_referer( $admin_url . 'admin.php?page=types-helper&action=new-form&type=' . self::$post_type->name.'&toolset_help_video=cred_form' ),
			'%POST-TYPE-EDIT-HAS-ARCHIVE%'      => self::add_referer( $admin_url . 'admin.php?page=wpcf-edit-type&wpcf-post-type=' . self::$post_type->name . '#types_options' ),
		);

		// Views specifics
		if( defined( 'WPV_VERSION' ) ) {
			$placeholders = array_merge( $placeholders, array(
				//'%POST-CREATE-LAYOUT-TEMPLATE%'           => self::get_post_create_layout_template(),
				'%POST-CONTENT-TEMPLATE-NAME%'              => Types_Helper_Condition_Views_Template_Exists::get_template_name(),
				'%POST-VIEWS-ARCHIVE%'                      => Types_Helper_Condition_Views_Archive_Exists::get_template_name(),
				'%POST-EDIT-VIEWS-ARCHIVE%'                 => self::get_post_edit_views_archive(),
				'%POST-EDIT-CONTENT-TEMPLATE%'              => self::get_post_edit_views_template(),
				'%POST-CREATE-CONTENT-TEMPLATE%'            => self::add_referer( $admin_url . 'admin.php?page=types-helper&action=new-content-template&type='.self::$post_type->name.'&toolset_help_video=views_template' ),
				'%POST-CREATE-VIEWS-ARCHIVE%'               => self::add_referer( $admin_url . 'admin.php?page=types-helper&action=new-wordpress-archive&type='.self::$post_type->name.'&toolset_help_video=views_archives' ),
				'%POST-CREATE-VIEWS-ARCHIVE-TYPE%'          => self::$post_type->name,
				'%POST-CREATE-VIEWS-ARCHIVE-REDIRECT-URL%'  => '&' . self::add_referer( 'toolset_help_video=views_archives' ),
                '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-TITLE%' => self::$post_type->label,
                '%POST-CREATE-VIEWS-ARCHIVE-FOR-WHOM-LOOP%' => esc_attr( 'wpv-view-loop-cpt_' . self::$post_type->name ),
				//'%POST-CREATE-VIEWS-ARCHIVE%'             => self::get_post_create_views_archive(),
				'%POST-VIEWS-LIST%'                         => self::get_post_type_views_list(),
				'%POST-CREATE-VIEW%'                        => self::add_referer( $admin_url . 'admin.php?page=types-helper&action=new-view&type=' . self::$post_type->name . '&toolset_help_video=views_view' ),
				'%POST-CREATE-VIEW-TYPE%'                   => self::$post_type->name,
				'%POST-CREATE-VIEW-REDIRECT-URL%'           => '&' . self::add_referer( 'toolset_help_video=views_view' ),
			) );

		}

		// Layouts specifics
		if( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) )  {
			$placeholders = array_merge( $placeholders, array(
				//'%POST-CREATE-LAYOUT-TEMPLATE%'     => self::get_post_create_layout_template(),
				'%POST-CREATE-LAYOUT-TEMPLATE%'     => self::add_referer( $admin_url . 'admin.php?page=types-helper&action=new-layout-template&type='.self::$post_type->name.'&toolset_help_video=layouts_template' ),
				'%POST-CREATE-LAYOUT-ARCHIVE%'      => self::add_referer( self::get_post_create_layout_archive() . '&toolset_help_video=layouts_archive' ),
				'%POST-EDIT-LAYOUT-TEMPLATE%'       => self::get_post_edit_layout_template(),
				'%POST-EDIT-LAYOUT-ARCHIVE%'        => self::get_post_edit_layout_archive(),
				'%POST-LAYOUT-TEMPLATE%'            => Types_Helper_Condition_Layouts_Template_Exists::get_layout_name(),
				'%POST-LAYOUT-ARCHIVE%'             => Types_Helper_Condition_Layouts_Archive_Exists::get_layout_name(),
			) );
		}

		$original = strtr( $original, $placeholders );
	}

	private static function get_post_create_views_archive() {
		$tool_admin_bar = Toolset_Admin_Bar_Menu::get_instance();

		$post_type = self::$post_type->name == 'post' ? 'home-blog' : self::$post_type->name;
		return $tool_admin_bar->get_edit_link( 'views', true, $post_type, 'archive', 0 );
	}

	private static function get_post_edit_views_template() {
		$url = admin_url() . 'admin.php?page=ct-editor&ct_id='
		       . Types_Helper_Condition_Views_Template_Exists::get_template_id();

		return self::add_referer( $url );
	}

	private static function get_post_edit_views_archive() {
		$url = admin_url() . 'admin.php?page=view-archives-editor&view_id='
				. Types_Helper_Condition_Views_Archive_Exists::get_template_id();

		return self::add_referer( $url );
	}

	private static function get_post_create_layout_archive() {
		$tool_admin_bar = Toolset_Admin_Bar_Menu::get_instance();

		// change name to 'home-blog' for build-in "Posts"
		$post_type = self::$post_type->name == 'post'
			? 'home-blog'
			: self::$post_type->name;

		return $tool_admin_bar->get_edit_link( 'layouts', true, $post_type, 'archive', 0 );
	}

	private static function get_post_edit_layout_archive() {
		$url = admin_url() . 'admin.php?page=dd_layouts_edit&action=edit&layout_id='
				. Types_Helper_Condition_Layouts_Archive_Exists::get_layout_id();

		return self::add_referer( $url );
	}

	private static function get_post_edit_layout_template() {
		$url = admin_url() . 'admin.php?page=dd_layouts_edit&action=edit&layout_id='
		       . Types_Helper_Condition_Layouts_Template_Exists::get_layout_id();
		return self::add_referer( $url );
	}

	private static function add_referer( $url ) {
		if( isset( $_GET['page'] ) && $_GET['page'] == 'toolset-dashboard' )
			$url = add_query_arg( 'ref', 'dashboard', $url );

		return $url;
	}

	private static function get_post_template_file() {
		$helper = new Types_Helper_Condition_Single_Exists();
		return basename( $helper->find_template() );
	}

	private static function get_post_archive_file() {
		$helper = new Types_Helper_Condition_Archive_Exists();
		return basename( $helper->find_template() );
	}

	public static function get_permalink( $id = 0 ) {
		$permalink = get_permalink( $id );

		if( $permalink ) {
			$query_args['preview'] = 'true';
			$permalink = add_query_arg( $query_args, $permalink );
			return $permalink;
		}

		if( isset( $_GET['post'] ) && $id != $_GET['post'] ) {
			$get_permalink_id = (int) $_GET['post'];
			return self::get_permalink( $get_permalink_id );
		}

		// cpt edit page
		if( isset( $_GET['wpcf-post-type'] ) && $id == 0 ) {
			$get_permalink_post_type = sanitize_text_field( $_GET['wpcf-post-type'] );
			$query = new WP_Query( 'post_type=' . $get_permalink_post_type . '&posts_per_page=1' );
			if( $query->have_posts() )
				return self::get_permalink( $query->posts[0]->ID );
		}

		// fields edit page
		if( is_object( self::$post_type ) && $id == 0 ){
			$query = new WP_Query( 'post_type=' . self::$post_type->name . '&posts_per_page=1' );
			if( $query->have_posts() )
				return self::get_permalink( $query->posts[0]->ID );
		}

		return false;
	}

	public static function get_archive_permalink() {
		if( array_key_exists( 'wpcf-post-type', $_GET ) ) {
			$post_type = sanitize_text_field( $_GET['wpcf-post-type'] );
		} else {
			if( ! is_object( self::$post_type ) )
				self::set_post_type();

			$post_type = self::$post_type->name;
		}

		// check cache
		if( array_key_exists( $post_type, self::$cache ) && array_key_exists( 'archive_permalink', self::$cache[$post_type] ) )
			return self::$cache[$post_type]['archive_permalink'];

		// get new
		$query = new WP_Query( 'post_type=' . $post_type . '&post_status=publish&posts_per_page=1' );

		self::$cache[$post_type]['archive_permalink'] = $query->have_posts()
			? get_post_type_archive_link( $post_type )
			: false;

		return self::$cache[$post_type]['archive_permalink'];
	}

	private static function get_post_type_views_list() {
		// @todo use twig
		if( $views = Types_Helper_Condition_Views_Views_Exist::get_views_of_post_type() ) {
			$output = '<ul>';
			foreach( $views as $view ) {
				$view_edit_link = admin_url() . 'admin.php?page=views-editor&view_id=' . $view['id'];
				$output .= '<li><a href="'. self::add_referer( $view_edit_link )  . '">'. $view['name'].'</a></li>';
			}
			$output .= '</ul>';

			return $output;
		}

		return __( 'No Views', 'wpcf' );
	}

	private static function get_post_type_forms_list() {
		// @todo use twig
		if( $forms = Types_Helper_Condition_Cred_Forms_Exist::get_forms_of_post_type() ) {
			$output = '<ul>';
			foreach( $forms as $form ) {
				$form_edit_link = get_edit_post_link( $form['id'] );
				$output .= '<li><a href="'. self::add_referer( $form_edit_link ) . '">'. $form['name'].'</a></li>';
			}
			$output .= '</ul>';

			return $output;
		}

		return __( 'No Forms', 'wpcf' );
	}
}