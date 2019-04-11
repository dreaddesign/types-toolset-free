<?php

define("CRED_WP_REF_FILE", "wp-blog-header.php");

function cred_find_abspath($dir, $file2search) {
    if (file_exists($dir . "/" . $file2search)) {
        return $dir . "/";
    }
    $search_in_dir = cred_find_wp_config_path($dir, CRED_WP_REF_FILE);
    if (!empty($search_in_dir))
        return $search_in_dir;
    return cred_find_abspath(dirname($dir), $file2search);
}

function cred_find_wp_config_path($dir, $file2search) {
    if (file_exists($dir . "/" . $file2search)) {
        return $dir . "/";
    }
    $list = scandir($dir, 1);
    foreach ($list as $ele) {
        if ($ele == '.' || $ele == '..')
            continue;
        $newdir = $dir . "/" . $ele;
        if (is_dir($newdir)) {
            return cred_find_wp_config_path($newdir, $file2search);
        }
    }
    return "";
}

function cred_get_root_path() {
    return cred_find_abspath(dirname(__FILE__), CRED_WP_REF_FILE);
}

function cred_get_local($url) {
    $urlParts = parse_url($url);
    return cred_get_root_path() . $urlParts['path'];
}

function cred_clean($string) {
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

define('WP_USE_THEMES', false);
define('DOING_AJAX', true);

require_once( cred_get_root_path() . 'wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

/** Allow for cross-domain requests (from the frontend). */
send_origin_headers();

$data = array();

if (isset($_REQUEST['nonce']) && check_ajax_referer('ajax_nonce', 'nonce', false)) {

    if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['file'])) {
        $file = esc_url_raw($_POST['file']);
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $data = array('result' => true);
        $local_file = cred_get_local($file);
        $attachments = get_children(
                array(
                    'post_parent' => $id,
                    'post_type' => 'attachment'
                )
        );
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attach_file = strtolower(basename($attachment->guid));
                $my_local_file = strtolower(basename($local_file));
                if ($attach_file == $my_local_file)
                    wp_delete_attachment($attachment->ID);
            }
        }
//        if (file_exists($local_file)) {
//            $res = unlink($local_file);
//        }
//        $data = ($res) ? array('result' => $res) : array('result' => $res, 'error' => 'Error Deleting ' . $file);
    } else {
        if (isset($_GET['id'])) {
            $post_id = intval($_GET['id']);
            $post_type = get_post_type($post_id);
            $form_id = intval($_GET['formid']);
            $form = get_post($form_id);
            $form_type = $form->post_type;
            $form_slug = $form->post_name;

            $thisform = array(
                'id' => $form_id,
                'post_type' => $post_type,
                'form_type' => $form_type
            );

            $error = false;
            $files = array();
            $previews = array();

            $upload_overrides = array('test_form' => false);
            if (!empty($_FILES)) {

                //Control file size wp_max_upload_size()
                foreach ($_FILES as $uploaded_file) {
                    if (filesize($uploaded_file["tmp_name"]) > wp_max_upload_size()) {
                        $data = array('result' => false, 'error' => __('Error: Files is too big, Max upload size is', 'wpv-views') . ': ' . number_format((wp_max_upload_size() / 1048576), 2) . " MB");
                        break;
                    }
                }

                //If no size errors
                if (empty($data)) {

                    $fields = array();
                    foreach ($_FILES as $name => $v) {
                        $fields[$name]['field_data'] = $v;
                    }

                    $errors = array();

                    list($fields, $errors) = apply_filters('cred_form_ajax_upload_validate_' . $form_slug, array($fields, $errors), $thisform);
                    list($fields, $errors) = apply_filters('cred_form_ajax_upload_validate_' . $form_id, array($fields, $errors), $thisform);
                    list($fields, $errors) = apply_filters('cred_form_ajax_upload_validate', array($fields, $errors), $thisform);

                    if (!empty($errors)) {
                        foreach ($errors as $fname => $err) {
                            $data = array('result' => false, 'error' => $fname . ': ' . $err);
                        }
                        echo json_encode($data);
                        die;
                    } else {
                        foreach ($_FILES as $file) {
                            //For repetitive
                            foreach ($file as &$f) {
                                if (is_array($f)) {
                                    foreach ($f as $p) {
                                        $f = $p;
                                        break;
                                    }
                                }
                            }

                            $res = wp_handle_upload($file, $upload_overrides);

                            if (!isset($res['error'])) {
                                //StaticClass::_pre($res);

                                $bname = basename($res['file']);
                                $attachment = array(
                                    'post_mime_type' => $res['type'],
                                    'post_title' => $bname,
                                    'post_content' => '',
                                    'post_status' => 'inherit',
                                    'post_parent' => $post_id,
                                    'post_type' => 'attachment',
                                    'guid' => $res['url'],
                                );
                                $attach_id = wp_insert_attachment($attachment, $res['file']);
                                $attach_data = wp_generate_attachment_metadata($attach_id, $res['file']);
                                wp_update_attachment_metadata($attach_id, $attach_data);

                                //Fixing S3 Amazon rewriting compatibility
                                if (wp_attachment_is_image($attach_id)) {
                                    $_rewrited_url = wp_get_attachment_image_src($attach_id, 'full');
                                    $_rewrited_url_prw = wp_get_attachment_image_src($attach_id);
                                    $attach_data = wp_generate_attachment_metadata($attach_id, $_rewrited_url);
                                } else {
                                    $_rewrited_url = wp_get_attachment_url($attach_id);
                                }

                                if (isset($_rewrited_url)) {
                                    $files[] = (is_array($_rewrited_url) && isset($_rewrited_url[0])) ? $_rewrited_url[0] : $_rewrited_url; //$res['url'];
                                    $attaches[] = $attach_id;
                                    if (isset($_rewrited_url_prw))
                                        $previews[] = (is_array($_rewrited_url_prw) && isset($_rewrited_url_prw[0])) ? $_rewrited_url_prw[0] : $_rewrited_url_prw; //$res['url'];
                                } else {
                                    $files[] = $res['url'];
                                    $attaches[] = $attach_id;
                                }
                            } else {
                                $error = true;
                            }
                        }
                    }
                    $data = ($error) ? array('result' => false, 'error' => __('There was an error uploading your files', 'wpv-views') . ': ' . $res['error']) : array('files' => $files, 'attaches' => $attaches, 'previews' => $previews, 'delete_nonce' => time());
                }
            } else {
                $data = array('result' => false, 'error' => __('Error: Files is too big, Max upload size is', 'wpv-views') . ': ' . ini_get('post_max_size'));
            }
        } else {
            $data = array('result' => false, 'error' => __('Error post id: check _cred_cred_prefix_post_id', 'wpv-views'));
        }
    }
} else {
    $data = array('result' => false, 'error' => __('Upload Error: Invalid NONCE', 'wpv-views'));
}

echo json_encode($data);
?>