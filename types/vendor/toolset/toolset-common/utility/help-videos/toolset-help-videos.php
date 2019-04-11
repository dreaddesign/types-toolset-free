<?php

if( ! class_exists( 'Toolset_HelpVideo', false ) ) {

    class Toolset_HelpVideo extends stdClass
    {
        private $name = 'video';
        private $url = '';
        private $screens = array();
        private $element = 'body';
        private $width = 820;
        private $height = 506;
        private $title = 'Tutorial Video';
        private $append_to = '';
        private static $instances = array();
        protected static $current = null;
        const KEY = 'toolset_help_video';
        const GENERIC_ELEMENT = 'toolset_video_wrap';

        public function __construct(array $arguments = array())
        {

            if (empty($arguments)) return;

            self::$current = isset($_REQUEST[self::KEY]) ? sanitize_text_field( $_REQUEST[self::KEY] ) : null;

            if (self::$current === null) return;

            if (!empty($arguments)) {
                foreach ($arguments as $property => $argument) {
                    $this->{$property} = $argument;
                }
            }

            add_filter('toolset_video_instances', array(&$this, 'push_tooolset_video_instances'), 10, 1);
            add_filter('toolset_add_registered_script', array(&$this, 'add_register_scripts'));
            add_filter('toolset_add_registered_styles', array(&$this, 'add_register_styles'));
            add_action('admin_print_scripts', array(&$this, 'admin_enqueue_scripts'));
            add_action('admin_footer', array(&$this, 'load_template'));
            add_action( 'admin_footer', array(&$this, 'add_user_meta'), 999 );
        }

        public function add_user_meta(){
            if( self::$current == null || $this->name != self::$current ) return;
            add_user_meta( $this->get_current_user_id(), self::$current, 'seen', true );
        }

        private function get_current_user_id(){
            global $current_user;
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            return $user_id;
        }

	    private function is_wp_older_than_4_dot_9(){
		    $current = get_bloginfo('version');
		    return version_compare( $current, '4.9-RC1' ) === -1;
	    }

        public function add_register_scripts($scripts)
        {
			if( $this->is_wp_older_than_4_dot_9() ){
				$scripts['toolset-help-video'] = new Toolset_Script('toolset-help-video', TOOLSET_COMMON_URL . '/utility/help-videos/res/js/toolset-help-videos-retro.js', array('jquery', 'underscore', 'backbone', 'wp-mediaelement', 'toolset-utils'), '1.0', true);
			} else {
				$scripts['toolset-help-video'] = new Toolset_Script('toolset-help-video', TOOLSET_COMMON_URL . '/utility/help-videos/res/js/toolset-help-videos.js', array('jquery', 'underscore', 'backbone', 'wp-mediaelement', 'toolset-utils'), '1.0', true);
			}

            return $scripts;
        }

	    public function add_register_styles($styles)
	    {
		    if( $this->is_wp_older_than_4_dot_9() ) {
			    $styles['toolset-help-video'] = new Toolset_Style( 'toolset-help-video', TOOLSET_COMMON_URL . '/utility/help-videos/res/css/toolset-help-videos-retro.css' );
		    } else {
			    $styles['toolset-help-video'] = new Toolset_Style( 'toolset-help-video', TOOLSET_COMMON_URL . '/utility/help-videos/res/css/toolset-help-videos.css' );
		    }

		    return $styles;
	    }

        public function admin_enqueue_scripts()
        {
            if (
                !is_admin()
                || !function_exists('get_current_screen')
            ) {
                return;
            }

            $screen = get_current_screen();

            $this->screens[] = Toolset_VideoDetachedPage::get_screen();

            if (!empty($this->screens) && !in_array($screen->id, $this->screens)) {
                return;
            }

            $instances = apply_filters('toolset_video_instances', self::$instances);
            $current = apply_filters('toolset_current_video', self::$current);

            if (isset($instances[$current]) === false) return;

            do_action('toolset_enqueue_scripts', array(
                'toolset-help-video'
            ));
            do_action('toolset_enqueue_styles', array(
                'wp-mediaelement',
                'font-awesome',
                'toolset-help-video'
            ));

            do_action('toolset_localize_script', 'toolset-help-video', 'WP_ToolsetVideoSettings', array(
                'video_instances' => $instances,
                'current' => $current,
                'seen' => get_user_meta( $this->get_current_user_id(), self::$current, true ),
                'detached_page' => Toolset_VideoDetachedPage::get_screen(),
                'detach_url' => admin_url(sprintf('admin.php?page=toolset_video_tutorials&toolset_help_video=%s', $instances[$current]['name'])),
                'GENERIC_ELEMENT' => self::GENERIC_ELEMENT,
                'VIDEOS_LIST_TITLE' => sprintf(__('%sVideo Tutorials%s', 'toolset-common'), '<h2 class="js-videos-list-title">', '</h2>')
            ));
        }

        public function push_tooolset_video_instances($instances)
        {

            self::$instances[$this->name] = array(
                'name' => $this->name,
                'url' => $this->url,
                'element' => $this->element,
                'screens' => $this->screens,
                'width' => $this->width,
                'height' => $this->height,
                'title' => $this->title,
                'append_to' => $this->append_to
            );

            return self::$instances;
        }

        public function load_template()
        {
            include_once TOOLSET_COMMON_PATH . '/utility/help-videos/templates/help-video.tpl.php';
        }

        public static function get_current()
        {
            return isset(self::$instances[self::$current]) ? self::$instances[self::$current] : null;
        }
    }

    abstract class Toolset_HelpVideosFactoryAbstract
    {

        const WIDTH = '820px';
        const HEIGHT = '506px';
        protected $videos = array();

        protected function __construct()
        {
            $this->process_videos();
        }

        final public static function getInstance()
        {
            static $instances = array();
            $called_class = get_called_class();
            if ( !isset($instances[$called_class]) && class_exists($called_class) ) {
                $instances[$called_class] = new $called_class();
            }
            return isset( $instances[$called_class] ) ? $instances[$called_class] : null;
        }

        protected abstract function define_toolset_videos();

        protected function process_videos()
        {

            $videos = $this->define_toolset_videos();

            if (!$videos || empty($videos)) return;

            foreach ($videos as $video) {

                $video = wp_parse_args(
                    $video,
                    array('width' => self::WIDTH, 'height' => self::HEIGHT)
                );

                $this->videos[] = new Toolset_HelpVideo(
                    $video
                );
            }
            return $this->videos;
        }
    }

    class Toolset_VideoDetachedPage
    {
        private static $instance;
        const SLUG = 'toolset_video_tutorials';
        const SCREEN = 'admin_page_toolset_video_tutorials';

        protected function __construct()
        {
            if ( is_admin() && function_exists('add_submenu_page') ) {
                add_submenu_page(null, __('Video Tutorial'), '', 'manage_options', self::SLUG, array(&$this, 'load_template'));
            }
        }

        final public static function getInstance()
        {
            if (!self::$instance) {
                self::$instance = new Toolset_VideoDetachedPage();
            }

            return self::$instance;
        }

        function load_template()
        {
            $current = Toolset_HelpVideo::get_current();

            if (null === $current) {
                printf(__('%sNo videos to play%s'), '<h2>', '</h2>');
            } else {
                $element = isset($current['element']) ? trim($current['element'], '.,#') : Toolset_HelpVideo::GENERIC_ELEMENT;
                require_once TOOLSET_COMMON_PATH . '/utility/help-videos/templates/tutorial-video-page.tpl.php';
            }

        }

        public static function get_screen()
        {
            return self::SCREEN;
        }
    }

    add_action( 'admin_menu', array( 'Toolset_VideoDetachedPage', 'getInstance' ), 99 );
    

}
