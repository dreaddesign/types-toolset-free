<?php
/**
 *
 * Types Marketing Class
 *
 *
 */

include_once WPCF_INC_ABSPATH.'/classes/class.wpcf.marketing.php';
require_once WPCF_INC_ABSPATH.'/classes/class.types.admin.taxonomies.php';

/**
 * Types Marketing Class
 *
 * @since Types 1.6.5
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category Help
 * @author marcin <marcin.p@icanlocalize.com>
 */
class WPCF_Types_Marketing_Messages extends WPCF_Types_Marketing
{
    private $state;
    private $taxonomies;

    public function __construct()
    {
        parent::__construct();
        add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 1);
        $this->set_state();
        $this->taxonomies = new Types_Admin_Taxonomies();
    }

    private function set_state()
    {
        $this->state = '0' == get_option($this->option_disable, '0')? 'endabled':'disabled';

        if ('disabled' == $this->state) {
            return;
        }
        if ( self::check_register() ) {
            $this->state = 'disabled';
        }
    }

    public static function check_register()
    {
        if(!function_exists('WP_Installer')){
            return false;
        }
        $repos = array(
            'toolset'
        );
        foreach( $repos as $repository_id ) {
            $key = WP_Installer()->repository_has_subscription($repository_id);
            if ( empty($key) ) {
                continue;
            }
            return true;
        }
        return false;
    }

    private function get_data()
    {
		/*
		* Legacy
		*
		* THere was a time where we had a weird getting started page, and $kind was stored depending on a user selection
		* It was one of the following values: brochure | directory_classifieds | classifieds | e-commerce | blog
		* It sets which message is shown when updating a post type, taxonomy or fields group
		* As we are reviewing marketing messages it might be a good time to do it.
		*/
		$kind = 'brochure';
		
        /**
         * check exists?
         */
        if ( empty($kind) || !array_key_exists($kind, $this->adverts ) ) {
            return;
        }

        /**
         * check type
         */
        $type = $this->get_page_type();
        if ( empty($type) || !array_key_exists($type, $this->adverts[$kind]) ) {
            return;
        }
        if ( !is_array($this->adverts[$kind][$type]) ) {
            return;
        }
        /**
         * get number
         */
        $number = intval(get_user_option('types-modal'));
        if ( !isset($this->adverts[$kind][$type][$number]) ) {
            if ( empty($this->adverts[$kind][$type]) ) {
                return;
            }
                $number = 0;
        }

        $data = $this->adverts[$kind][$type][$number];
        $data['number'] = $number;
        $data['count'] = count($this->adverts[$kind][$type]);
        return $data;
    }

    private function replace_placeholders($text)
    {
        $type = $this->get_page_type();
        switch($type) {
        case 'cpt':
            if (
                is_array($_GET)
                && array_key_exists('wpcf-post-type', $_GET)
            ) {
            	$post_type_option = new Types_Utils_Post_Type_Option();
                $types = $post_type_option->get_post_types();
                $candidate_key = sanitize_text_field( $_GET['wpcf-post-type'] );
                if ( array_key_exists($candidate_key, $types ) ) {
                    $text = preg_replace( '/PPP/', $types[$candidate_key]['labels']['name'], $text);
                }
            }
            break;

        case 'taxonomy':
            if (
                is_array($_GET)
                && array_key_exists('wpcf-tax', $_GET)
            ) {
                $taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
                $taxonomies = $this->taxonomies->get();

                $candidate_key = sanitize_text_field( $_GET['wpcf-tax'] );
                if ( array_key_exists($candidate_key, $taxonomies) ) {
                    $ttt = __('unknown', 'wpcf');
                    if (
                        true
                        && isset($taxonomies[$candidate_key])
                        && isset($taxonomies[$candidate_key]['labels'])
                    ) {
                        if ( isset( $taxonomies[$candidate_key]['labels']['name'] ) ) {
                            $ttt = $taxonomies[$candidate_key]['labels']['name'];
                        } else 
                        if ( isset( $taxonomies[$candidate_key]['labels']['singular_name'] ) ) {
                            $ttt = $taxonomies[$candidate_key]['labels']['singular_name'];
                        }

                    }
                    $text = preg_replace( '/TTT/', $ttt, $text);
                    if ( array_key_exists('supports', $taxonomies[$candidate_key]) ) {
                        $post_type = array_keys($taxonomies[$candidate_key]['supports']);
                        if ( !empty($post_type) ) {
                            $post_type = $post_type[array_rand($post_type)];
                            $post_type = get_post_type_object($post_type);
                            if ( $post_type ) {
                                $text = preg_replace( '/PPP/', $post_type->labels->name, $text);
                            }
                        }
                    }
                }
            }
            break;
        }
        /**
         * defaults
         */
        $text = preg_replace( '/PPP/', __('Posts'), $text);
        $text = preg_replace( '/TTT/', __('Tags'), $text);

        return $text;
    }

    public function register_scripts()
    {

        $data = $this->get_data();
        if ( empty($data) ) {
            return;
        }
        /**
         * common question
         */
        $data['message'] = __('Saving your changes', 'wpcf');
        $data['spinner'] = apply_filters('wpcf_marketing_message', admin_url('/images/spinner.gif'), $data, 'spinner');
        $data['question'] = apply_filters('wpcf_marketing_message', __('Did you know?', 'wpcf'), $data, 'question');
        /**
         * random image & class
         */
        $image = isset($data['image'])? $data['image']:'views';
        $src = sprintf(
            '%s/marketing/assets/images/%s.png',
            WPCF_RELPATH,
            $image
        );
        $data['image'] = apply_filters('wpcf_marketing_message', $src, $data, 'image');
        $data['class'] = apply_filters('wpcf_marketing_message', $image, $data, 'class');
        /**
         * values depend on type
         */
        foreach ( array('header', 'description') as $key ) {
            $value = '';
            if ( isset($data[$key]) && $data[$key] ) {
                $value = $this->replace_placeholders($data[$key]);
            }
            $data[$key] = apply_filters('wpcf_marketing_message', $value, $data, $key );
            $data['state'] = $this->state;
        }
    }

    public function update_message($message = false)
    {
        if (empty($message)) {
            return;
        }
        echo '<div class="updated"><p>', $message, '</p></div>';
    }

	// @todo this might be deprecated, could not locate where this is being triggered...
    public function update_options()
    {
        if(!isset($_POST['marketing'])) {
            return;
        }
        if ( !wp_verify_nonce($_POST['marketing'], 'update')) {
            return;
        }
        $this->set_state();
    }

    public function delete_option_kind()
    {
        delete_option($this->option_name);
    }

    public function show_top($update = true)
    {
        $data = $this->get_data();
        if ( empty($data) ) {
            return false;
        }
        $content = '<div class="icon-toolset-logo icon-toolset">';
        $content .= sprintf('<p class="wpcf-notif-header">%s</p>', $update? __('Updated!', 'wpcf'):__('Created!', 'wpcf') );
        if ( 'endabled' == $this->state) {
            $content .= '<p class="wpcf-notif-description">';
            if ( isset($data['link']) ) {
                $content .= sprintf(
                    '<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'utm_source' => 'typesplugin',
								'utm_medium' =>  'save-updated',
							),
							$data['link']
						)
					),
                    $data['description']
                );
            } else {
                $content .= $data['description'];
            }
            $content .= '</p>';
        }
        $content .= '</div>';

        $content = $this->replace_placeholders($content);

        /**
         * after all set up types-modal for next time
         */
        $key = rand( 0, $data['count']-1 );
        $user_id = get_current_user_id();
        update_user_option($user_id, 'types-modal', $key);

        return $content;
    }

}

