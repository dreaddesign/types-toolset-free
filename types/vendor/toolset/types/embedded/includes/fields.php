<?php

function wpcf_admin_fields_get_active_fields_by_post_type($post_type)
{
    $custom_fields = array();
    $groups = wpcf_admin_fields_get_groups(TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, true, true);
    if ( empty($groups) ) {
        return $custom_fields;
    }
    foreach ($groups as $group) {
        $types = wpcf_admin_get_post_types_by_group($group['id']);
        if ( !empty($types) && !in_array($post_type, $types) ) {
            continue;
        }
        if (isset($group['fields'])) {
            $custom_fields += $group['fields'];
        }
    }
    return $custom_fields;
}


/**
 * Gets all groups.
 *
 * @param string $post_type
 * @param boolean|string $only_active
 * @param boolean|string $add_fields - 'field_active', 'field_all', false (to omitt fields)
 * @return array array of custom fields definition
 */
function wpcf_admin_fields_get_groups( $post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, $only_active = false, $add_fields = false ) {
    $cache_group = 'types_cache_groups';
    $cache_key = md5( 'group::_get_group' . $post_type );
    $cached_object = wp_cache_get( $cache_key, $cache_group );
    if ( false === $cached_object ) {
        $groups = get_posts( 'numberposts=-1&post_type=' . $post_type . '&post_status=null' );
        wp_cache_add( $cache_key, $groups, $cache_group );
    } else {
        $groups = $cached_object;
    }
    $_groups = array();
    if ( !empty( $groups ) ) {
        foreach ( $groups as $k => $group ) {
            $group = wpcf_admin_fields_adjust_group( $group, $add_fields );
            if ( $only_active && !$group['is_active'] ) {
                continue;
            }
            $_groups[$k] = $group;
        }
    }
    return $_groups;
}

/**
 * Gets group by ID.
 *
 * Since 1.2 we enabled fetching by post title.
 *
 * @param $group_id
 * @param string $post_type
 * @param bool $add_fields
 * @return array
 */
function wpcf_admin_fields_get_group( $group_id, $post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME,
        $add_fields = false ) {
    $group = get_post( $group_id );
    if ( empty( $group->ID ) ) {
        $group = get_page_by_title( $group_id, OBJECT, $post_type );
    }
    if ( empty( $group->ID ) ) {
        $group = get_page_by_path( $group_id, OBJECT, $post_type );
    }
    if ( empty( $group->ID ) || $group->post_type != $post_type ) {
        return array();
    }
    return wpcf_admin_fields_adjust_group( $group, $add_fields );
}

/**
 * Converts post data.
 *
 * @param $post
 * @param bool $add_fields
 * @return array
 */
function wpcf_admin_fields_adjust_group( $post, $add_fields = false ) {
    if ( empty( $post ) ) {
        return false;
    }
    $group = array();
    $group['id'] = $post->ID;
    $group['slug'] = $post->post_name;
    $group['name'] = $post->post_title;
    $group['description'] = $post->post_content;
    $group['meta_box_context'] = 'normal';
    $group['meta_box_priority'] = 'high';
    $group['is_active'] = $post->post_status == 'publish' ? true : false;
    $group['filters_association'] = get_post_meta( $post->ID, '_wp_types_group_filters_association', true );
    $group[WPCF_AUTHOR] = $post->post_author;

    /**
     * add _wp_types_group_showfor
     *
     * "all" - for all groups
     * array() - if any
     */
    if ( TYPES_USER_META_FIELD_GROUP_CPT_NAME == $post->post_type ) {
        $data = get_post_meta($post->ID, '_wp_types_group_showfor', true);
        if ( 'all' != $data ) {
            $data = array_values(array_filter(explode(',',$data)));
        }
        if ( empty($data) ) {
            $data = 'all';
        }
        $group['_wp_types_group_showfor'] = $data;
    }

    // Attach fields if required (since 1.3)
    if ( $add_fields ) {
        $active = $add_fields == 'fields_active' ? true : false;
        $group['fields'] = wpcf_admin_fields_get_fields_by_group( $post->ID, 'slug', $active );
    }

    return $group;
}

/**
 * Gets Fields Admin Styles supported by specific group.
 *
 * @param int $group_id
 * @return string
 */
function wpcf_admin_get_groups_admin_styles_by_group( $group_id ) {
	if (
		defined( 'TYPES_USE_STYLING_EDITOR' )
		&& TYPES_USE_STYLING_EDITOR
	) {
		$admin_styles = get_post_meta( $group_id, '_wp_types_group_admin_styles', true );

		return trim( $admin_styles );
	}
}

/**
 * Saves group's admin styles
 *
 * @param type $group_id
 * @param type $padmin_styles
 */
function wpcf_admin_fields_save_group_admin_styles( $group_id, $admin_styles ) {
    update_post_meta( $group_id, '_wp_types_group_admin_styles', $admin_styles );
}

/**
 * Gets all fields.
 *
 * @todo Move to WPCF_Fields
 * @param bool $only_active
 * @param bool $disabled_by_type
 * @param bool $strictly_active
 * @param string $option_name
 * @param bool $use_cache
 * @param bool $clear_cache
 * @return array
 *
 * added param $use_cache by Gen (used when adding new fields to group)
 * added param $use_cache by Gen (used when adding new fields to group)
 */
function wpcf_admin_fields_get_fields( $only_active = false,
        $disabled_by_type = false, $strictly_active = false,
        $option_name = 'wpcf-fields', $use_cache = true, $clear_cache = false ) {

    static $cache = array();

    if ( $clear_cache ) {
        $cache = array();
    }

    /**
     * Sanitize option name
     */
    switch( $option_name ) {
	    case 'wpcf-usermeta':
	    case 'wpcf-fields':
	    case WPCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION:
		    break;
	    default:
		    $option_name = 'wpcf-fields';
		    break;
    }

    $cache_key = md5( $only_active . $disabled_by_type . $strictly_active . $option_name . $use_cache );
    if ( isset( $cache[$cache_key] ) && $use_cache == true ) {
        return $cache[$cache_key];
    }
    $required_data = array('id', 'name', 'type', 'slug');
    $fields = (array) get_option( $option_name, array() );
    foreach ( $fields as $k => $v ) {
        $failed = false;
        foreach ( $required_data as $required ) {
            if ( !isset( $v[$required] ) ) {
                $failed = true;
                continue;
            }
            /* if ( is_numeric($v[$required]) === true) {
                $failed = true;
                continue;
            } */
        }
        if ( $failed ) {
            unset( $fields[$k] );
            continue;
        }
        // This call loads config file
        $data = wpcf_fields_type_action( $v['type'] );
        if ( empty( $data ) ) {
            unset( $fields[$k] );
            continue;
        }
        if ( isset( $data['wp_version'] )
                && wpcf_compare_wp_version( $data['wp_version'], '<' ) ) {
            unset( $fields[$k] );
            continue;
        }
        if ( $strictly_active ) {
            if ( !empty( $v['data']['disabled'] ) || !empty( $v['data']['disabled_by_type'] ) ) {
                unset( $fields[$k] );
                continue;
            }
        } else {
            if ( ($only_active && !empty( $v['data']['disabled'] ) ) ) {
                unset( $fields[$k] );
                continue;
            }
            if ( !$disabled_by_type && !empty( $v['data']['disabled_by_type'] ) ) {
                unset( $fields[$k] );
                continue;
            }
        }
        $v['id'] = $k;
        $v['meta_key'] = wpcf_types_get_meta_prefix( $v ) . $k;

	    $option_name_to_meta_type = array(
		    'wpcf-fields' => 'postmeta',
		    'wpcf-usermeta' => 'usermeta',
		    WPCF_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION => 'termmeta'
	    );

        $v['meta_type'] = $option_name_to_meta_type[ $option_name ];
        $fields[$k] = wpcf_sanitize_field( $v );

        $fields[ $k ] = apply_filters( 'toolset_filter_field_definition_array', $v, 'types_fields' );
    }
    $cache[$cache_key] = apply_filters( 'types_fields', $fields );
    return $cache[$cache_key];
}


add_filter( 'toolset_filter_field_definition_array', 'wpcf_update_mandatory_validation_rules', 10, 2 );


/**
 * Modify field validation rules.
 *
 * Hooked into toolset_filter_field_definition_array. Not to be used elsewhere.
 *
 * Add mandatory validation rules that have not been stored in the database but are needed by Types and toolset-forms
 * to work properly. Namely it's the URL validation for file fields. On the contrary CRED needs these validation rules
 * removed.
 *
 * @param array $field_definition A field definition array.
 * @return array
 * @since 2.2.4
 */
function wpcf_update_mandatory_validation_rules( $field_definition, $ignored ) {

    // Add URL validation to file fields (containing URLs).
    //
    // This doesn't include embed files because they are more variable and the URL validation can be
    // configured on the Edit Field Group page.
    $file_fields = array( 'file', 'image', 'audio', 'video' );

    $field_type = toolset_getarr( $field_definition, 'type' );
    $is_file_field = in_array( $field_type, $file_fields );
    $validation_rules = wpcf_ensarr( wpcf_getnest( $field_definition, array( 'data', 'validate' ) ) );
    $has_url2_validation = array_key_exists( 'url2', $validation_rules );

    if ( $is_file_field ) {

        unset( $validation_rules['url'] );

        if ( ! $has_url2_validation ) {

            $default_validation_error_message = __( 'Please enter a valid URL address pointing to the file.', 'wpcf' );
            $validation_error_messages = array(
                'file' => $default_validation_error_message,
                'audio' => __( 'Please enter a valid URL address pointing to the audio file.', 'wpcf' ),
                'image' => __( 'Please enter a valid URL address pointing to the image file.', 'wpcf' ),
                'video' => __( 'Please enter a valid URL address pointing to the video file.', 'wpcf' )
            );

            // The url2 validation doesn't require the TLD part of the URL.
            $validation_rules['url2'] = array(
                'active' => '1',
                'message' => toolset_getarr( $validation_error_messages, $field_type, $default_validation_error_message ),
                'suppress_for_cred' => true
            );
        }

        $field_definition['data']['validate'] = $validation_rules;
    }


    // On the contrary, CRED file fileds MUST NOT use this validation otherwise it won't be possible to
    // submit not changed fields on the edit form. Thus we're making sure that no such rule goes through.
    //
    // These field types come via WPToolset_Types::filterValidation().
    $cred_file_fields = array( 'credfile', 'credimage', 'credaudio', 'credvideo' );
    $is_cred_field = in_array( $field_type, $cred_file_fields );

    if( $is_cred_field ) {

        unset( $validation_rules['url'] );
        unset( $validation_rules['url2'] );

        $field_definition['data']['validate'] = $validation_rules;
    }


    return $field_definition;
}



function wpcf_admin_fields_get_field_by_meta_key( $meta_key )
{
    $fields = wpcf_admin_fields_get_fields();
    foreach( $fields as $field) {
        if ( $meta_key == $field['meta_key'] ) {
            return $field;
        }
    }
}

/**
 * Gets field by ID.
 * Modified by Gen, 13.02.2013
 *
 * @param string $field_id
 * @param bool $only_active
 * @param bool $disabled_by_type
 * @param bool $strictly_active
 * @param string $option_name
 * @return array
 */
function wpcf_admin_fields_get_field( $field_id, $only_active = false,
        $disabled_by_type = false, $strictly_active = false,
        $option_name = 'wpcf-fields' ) {
    $fields = wpcf_admin_fields_get_fields( $only_active, $disabled_by_type,
            $strictly_active, $option_name );
    $field = !empty( $fields[$field_id] ) ? $fields[$field_id] : array();
    if ( !empty( $field ) ) {
        return apply_filters( 'wpcf_field', $field );
    }
    return array();
}

/**
 * Gets field by slug.
 *
 * @param string $slug
 * @param string $meta_name
 * @return array
 */
function wpcf_fields_get_field_by_slug( $slug, $meta_name = 'wpcf-fields' ) {
    return wpcf_admin_fields_get_field( $slug, false, false, false, $meta_name );
}

add_filter('wpcf_fields_by_group', 'wpcf_admin_fields_get_fields_by_group', 10, 1);

/**
 * Gets all fields that belong to specific group.
 *
 * @param int $group_id
 * @param string $key
 * @param bool $only_active
 * @param bool $disabled_by_type
 * @param bool $strictly_active
 * @param string $post_type
 * @param string $option_name
 * @param bool $use_cache
 *
 * @return array
 */
function wpcf_admin_fields_get_fields_by_group( $group_id, $key = 'slug',
        $only_active = false, $disabled_by_type = false,
        $strictly_active = false, $post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME,
        $option_name = 'wpcf-fields', $use_cache = true ) {
    static $cache = array();
    $cache_key = md5( serialize( func_get_args() ) );
    if ( $use_cache && isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }
    $group_fields = get_post_meta( $group_id, '_wp_types_group_fields', true );
    if ( empty( $group_fields ) ) {
        return array();
    }
    $group_fields = explode( ',', trim( $group_fields, ',' ) );
    $fields = wpcf_admin_fields_get_fields( $only_active, $disabled_by_type,
            $strictly_active, $option_name );
    $results = array();
    foreach ( $group_fields as $field_id ) {
        if ( !isset( $fields[$field_id] ) ) {
            continue;
        }
        $results[$field_id] = $fields[$field_id];
    }
    if ( $use_cache ) {
        $cache[$cache_key] = $results;
    }
    return $results;
}

/**
 * Gets groups that have specific term.
 *
 * @param type $term_id
 * @param type $fetch_empty
 * @param type $only_active
 * @return type
 */
function wpcf_admin_fields_get_groups_by_term( $term_id = false,
        $fetch_empty = true, $post_type = false, $only_active = true ) {
    $args = array();
    $args['post_type'] = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME;
    $args['numberposts'] = -1;
    // Active
    if ( $only_active ) {
        $args['post_status'] = 'publish';
    }
    // Fetch empty
    if ( $fetch_empty ) {
        if ( $term_id ) {
            $args['meta_query']['relation'] = 'OR';
            $args['meta_query'][] = array(
                'key' => '_wp_types_group_terms',
                'value' => ',' . $term_id . ',',
                'compare' => 'LIKE',
            );
        }
        $args['meta_query'][] = array(
            'key' => '_wp_types_group_terms',
            'value' => 'all',
            'compare' => '=',
        );
    } else if ( $term_id ) {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_types_group_terms',
                'value' => ',' . $term_id . ',',
                'compare' => 'LIKE',
            ),
        );
    } else {
        return array();
    }
    $groups = get_posts( $args );
    foreach ( $groups as $k => $post ) {
        $temp = get_post_meta( $post->ID, '_wp_types_group_post_types', true );
        if ( $fetch_empty && $temp == 'all' ) {
            $groups[$k] = wpcf_admin_fields_adjust_group( $post );
        } else if ( strpos( $temp, ',' . $post_type . ',' ) !== false ) {
            $groups[$k] = wpcf_admin_fields_adjust_group( $post );
        } else {
            unset( $groups[$k] );
        }
    }
    return $groups;
}

add_filter('wpcf_get_groups_by_post_type', 'wpcf_admin_get_groups_by_post_type', 10, 1);

/**
 * Gets groups that have specific post_type.
 *
 * @global object $wpdb
 * @param type $post_type
 * @param type $fetch_empty
 * @param type $only_active
 * @return type
 */
function wpcf_admin_get_groups_by_post_type( $post_type, $fetch_empty = true, $terms = null, $only_active = true )
{
    $args = array();
    $args['post_type'] = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME;
    $args['numberposts'] = -1;
    // Active
    if ( $only_active ) {
        $args['post_status'] = 'publish';
    }
    // Fetch empty
    if ( $fetch_empty ) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_wp_types_group_post_types',
                'value' => ',' . $post_type . ',',
                'compare' => 'LIKE',
            ),
            array(
                'key' => '_wp_types_group_post_types',
                'value' => 'all',
                'compare' => '=',
            ),
        );
    } else {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_types_group_post_types',
                'value' => ',' . $post_type . ',',
                'compare' => 'LIKE',
            ),
        );
    }

    $results_by_post_type = array();
    $results_by_terms = array();

    // Get posts by post type
    $groups = get_posts( $args );
    if ( !empty( $groups ) ) {
        foreach ( $groups as $key => $group ) {
            $group = wpcf_admin_fields_adjust_group( $group );
            $results_by_post_type[$group['id']] = $group;
        }
    }

    // Distinct terms
    if ( !is_null( $terms ) ) {
        if ( !empty( $terms ) ) {
            $terms_sql = array();
            $add = '';
            if ( $fetch_empty ) {
                $add = " OR m.meta_value LIKE 'all'";
            }
            foreach ( $terms as $term ) {
                $terms_sql[] = esc_sql( $term );
            }
            $terms_sql = "AND (m.meta_value LIKE '%%," . implode( ",%%' OR m.meta_value LIKE '%%,", $terms_sql ) . ",%%' $add)";
            global $wpdb;
            $terms_sql = "SELECT * FROM $wpdb->posts p
                JOIN $wpdb->postmeta m
                WHERE p.post_type=".TYPES_CUSTOM_FIELD_GROUP_CPT_NAME." AND p.post_status='publish'
                AND p.ID = m.post_id AND m.meta_key='_wp_types_group_terms'
                $terms_sql";
            $groups = $wpdb->get_results( $terms_sql );
            if ( !empty( $groups ) ) {
                foreach ( $groups as $key => $group ) {
                    $group = wpcf_admin_fields_adjust_group( $group );
                    $results_by_terms[$group['id']] = $group;
                }
            }
            foreach ( $results_by_post_type as $key => $value ) {
                if ( !array_key_exists( $key, $results_by_terms ) ) {
                    unset( $results_by_post_type[$key] );
                }
            }
        }
    }

    return $results_by_post_type;
}

/**
 * Gets groups that have specific template.
 *
 * @param type $post_type
 * @param type $fetch_empty
 * @param type $only_active
 * @return type
 */
function wpcf_admin_get_groups_by_template( $templates = array('default'),
        $fetch_empty = true, $only_active = true ) {
    $args = array();
    $args['post_type'] = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME;
    $args['numberposts'] = -1;
    $meta_query = array();
    // Active
    if ( $only_active ) {
        $args['post_status'] = 'publish';
    }

    // Fetch empty
    if ( $fetch_empty ) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_wp_types_group_templates',
                'value' => 'all',
                'compare' => '=',
            ),
        );
    } else {
        $args['meta_query'] = array(
            'relation' => 'OR');
    }
    foreach ( $templates as $template ) {
        $args['meta_query'][] = array(
            'key' => '_wp_types_group_templates',
            'value' => ',' . $template . ',',
            'compare' => 'LIKE',
        );
    }

    $results_by_template = array();

    // Get posts by template
    $groups = get_posts( $args );
    if ( !empty( $groups ) ) {
        foreach ( $groups as $key => $group ) {
            $group = wpcf_admin_fields_adjust_group( $group );
            $results_by_template[$group['id']] = $group;
        }
    }

    return $results_by_template;
}

/**
 * Get file fullpath to include
 *
 * param @string $basename
 *
 * return @string
 *
 */
function wpcf_get_fullpath_by_field_type($basename)
{
    return sprintf(
        '%s/fields/%s.php',
        dirname( __FILE__ ),
        preg_replace('/[^\w]+/', '', $basename)
    );
}

/**
 * Loads type configuration file and calls action.
 *
 * @param type $type
 * @param type $action
 * @param type $args
 */
function wpcf_fields_type_action( $type, $func = '', $args = array() ) {
    static $actions = array();
    $func_in = $func;

    $md5_args = md5( serialize( $args ) );

    if ( !isset( $actions[$type . '-' . $func_in . '-' . $md5_args] ) ) {
        $fields_registered = wpcf_admin_fields_get_available_types();
        if ( isset( $fields_registered[$type] ) && isset( $fields_registered[$type]['path'] ) ) {
            $file = $fields_registered[$type]['path'];
        } else if ( defined( 'WPCF_INC_ABSPATH' ) ) {
            $file = WPCF_INC_ABSPATH . '/fields/' . $type . '.php';
        } else {
            $file = '';
        }
        $file_embedded = wpcf_get_fullpath_by_field_type($type);
        if ( file_exists( $file ) || file_exists( $file_embedded ) ) {
            if ( file_exists( $file ) ) {
                require_once $file;
            }
            if ( file_exists( $file_embedded ) ) {
                require_once $file_embedded;
            }
            if ( empty( $func ) ) {
                $func = 'wpcf_fields_' . $type;
            } else {
                $func = 'wpcf_fields_' . $type . '_' . $func;
            }
            if ( function_exists( $func ) ) {
                $actions[$type . '-' . $func_in . '-' . $md5_args] = call_user_func( $func,
                        $args );
            } else {
                $actions[$type . '-' . $func_in . '-' . $md5_args] = false;
            }
        } else {
            $actions[$type . '-' . $func_in . '-' . $md5_args] = false;
        }
    }
    return $actions[$type . '-' . $func_in . '-' . $md5_args];
}

/**
 * Returns shortcode for specified field.
 *
 * @param type $field
 * @param type $add Additional attributes
 * @param string $content
 */
function wpcf_fields_get_shortcode( $field, $add = '', $content = '' )
{
    $shortcode = '[';
    $shortcode .= "types field='" . $field['slug'] . "'" . $add;
    $shortcode .= ']' . $content . '[/types]';
    $shortcode = apply_filters( 'wpcf_fields_shortcode', $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_type_' . $field['type'], $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_slug_' . $field['slug'], $shortcode, $field );
    return $shortcode;
}

function wpcf_termmeta_get_shortcode( $field, $add = '', $content = '' ) {
    $shortcode = '[';
    $shortcode .= "types termmeta='" . $field['slug'] . "'" . $add;
    $shortcode .= ']' . $content . '[/types]';
    $shortcode = apply_filters( 'wpcf_fields_shortcode', $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_type_' . $field['type'], $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_slug_' . $field['slug'], $shortcode, $field );
    return $shortcode;
}

function wpcf_get_termmeta_form_addon_submit() {
	$add = '';
    if ( ! empty( $_POST['is_termmeta'] ) ) {
		
    }
    return $add;
}

/**
 * Returns shortcode for specified usermeta field.
 *
 * @param type $field
 * @param type $add Additional attributes
 */
function wpcf_usermeta_get_shortcode( $field, $add = '', $content = '' ) {
    /*if ( isset($_GET['field_type']) && $_GET['field_type'] =='views-usermeta' ) {
            $add .= ' user_from_this_loop="true"';
    }*/
    $shortcode = '[';
    $shortcode .= "types usermeta='" . $field['slug'] . "'" . $add;
    $shortcode .= ']' . $content . '[/types]';
    $shortcode = apply_filters( 'wpcf_fields_shortcode', $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_type_' . $field['type'], $shortcode, $field );
    $shortcode = apply_filters( 'wpcf_fields_shortcode_slug_' . $field['slug'], $shortcode, $field );
    return $shortcode;
}

/*
 * Usermeta fields addon.
 * Add form user users
 *
 * @global object $wpdb
 *
 */

function wpcf_get_usermeta_form_addon( $settings = array() ) {
	global $wpdb;
	$form = array();
	$users = $wpdb->get_results(
		"SELECT ID, user_login, display_name 
		FROM {$wpdb->users} 
		LIMIT 5"
	);
	$form[] = array(
		'#type' => 'hidden',
		'#value' => 'true',
        '#name' => 'is_usermeta',
	);
	$__default = 'post_autor';
	$form[] = array(
		'#type' => 'radio',
		'#before' => '<div class="fieldset"><p class="form-inline">',
		'#suffix' => '</p>',
		'#value' => 'post_autor',
		'#title' => 'Author of this post',
        '#name' => 'display_username_for',
		'#default_value' => isset( $settings['user_is_author'] ) && $settings['user_is_author'] == 'true' ? 'post_autor' : $__default,
        '#inline' => true,
		'#attributes' => array('onclick' => 'wpcf_showmore(false)')
	);
	$form[] = array(
		'#type' => 'radio',
		'#before' => '<p class="form-inline">',
		'#suffix' => '</p>',
		'#value' => 'current_user',
		'#title' => 'The current logged in user',
        '#name' => 'display_username_for',
		'#default_value' => isset( $settings['user_current'] ) && $settings['user_current'] == 'true' ? 'current_user' : $__default,
        '#inline' => true,
		'#attributes' => array('onclick' => 'wpcf_showmore(false)')
	);
	$form[] = array(
		'#type' => 'radio',
		'#before' => '<p class="form-inline">',
		'#suffix' => '</p>',
		'#title' => 'A specific user',
		'#value' => 'pecific_user',
		'#id' => 'display_username_for_suser',
        '#name' => 'display_username_for',
		'#default_value' => isset( $settings['user_id'] ) || isset( $settings['user_name'] ) ? 'pecific_user' : $__default,
		'#after' => '',
        '#inline' => true,
		'#attributes' => array('onclick' => 'wpcf_showmore(true)')
	);
    $__username = isset( $settings['user_name'] ) ? $settings['user_name'] : '';
    $__userid = isset( $settings['user_id'] ) ? intval( $settings['user_id'] ) : '';
    $__hidden = !isset( $settings['user_id'] ) && !isset( $settings['user_name'] ) ? ' style="display:none;"' : '';
    $__hiddenId = !isset( $settings['user_id'] ) && isset( $settings['user_name'] ) ? ' style="display:none;"' : '';
	$form[] = array(
		'#type' => 'radio',
		'#title' => 'User ID',
		'#value' => 'specific_user_by_id',
		'#id' => 'display_username_for_suser_id',
        '#name' => 'display_username_for_suser_selector',
		'#before' => '<div class="group-nested form-inline" id="specific_user_div"' . $__hidden . '><p>',
		'#after' => '<input type="text" class="wpcf-form-textfield form-textfield textfield" name="display_username_for_suser_id_value" value="' . $__userid . '"'.
		' id="display_username_for_suser_id_value" value=""' . $__hiddenId . '></p>',
		'#default_value' => isset( $settings['user_id'] ) || !isset( $settings['user_name'] ) ? 'specific_user_by_id' : '',
        '#inline' => true,
		'#attributes' => array('onclick' => 'hideControls(\'display_username_for_suser_username_value\',\'display_username_for_suser_id_value\')')
	);
	$dropdown_users = '';
	foreach ($users as $u) {
        $dropdown_users .= '<option value="' . $u->user_login . '">' . $u->display_name . ' (' . $u->user_login . ')' . '</option>';
    }
    $__hidden = !isset( $settings['user_name'] ) ? ' style="display:none;"' : '';
	$form[] = array(
		'#type' => 'radio',
		'#title' => 'User name',
		'#value' => 'specific_user_by_username',
		'#id' => 'display_username_for_suser_username',
        '#name' => 'display_username_for_suser_selector',
		'#before' => '<p class="types-suggest-user types-suggest" id="types-suggest-user">',
		'#after' => '<input type="text" class="input wpcf-form-textfield form-textfield textfield"'. $__hidden .
		' name="display_username_for_suser_username_value" id="display_username_for_suser_username_value" value="' . $__username . '"></p></div></div>',
		'#default_value' => isset( $settings['user_name'] ) ? 'specific_user_by_username' : '',
        '#inline' => true,
		'#attributes' => array('onclick' => 'hideControls(\'display_username_for_suser_id_value\',\'display_username_for_suser_username_value\')')
	);

	return $form;
}

/*
 * Callback sumit form usermeta addon
 */

function wpcf_get_usermeta_form_addon_submit() {
    $add = '';
    if ( !empty( $_POST['is_usermeta'] ) ) {
        if ( $_POST['display_username_for'] == 'post_autor' ) {
            $add .= ' user_is_author=\'true\'';
        } elseif ( $_POST['display_username_for'] == 'current_user' ) {
            $add .= ' user_current=\'true\'';
        }
         else {
            if ( $_POST['display_username_for_suser_selector'] == 'specific_user_by_id' ) {
                $add .= ' user_id=\'' . sanitize_text_field($_POST['display_username_for_suser_id_value']) . '\'';
            } else {
                $add .= ' user_name=\'' . sanitize_text_field($_POST['display_username_for_suser_username_value']) . '\'';
            }
        }
    }
    return $add;
}

/**
 * Gets all available types and their config data.
 */
function wpcf_admin_fields_get_available_types() {
    return WPCF_Fields::getFieldsTypesData();
}

/**
 * Sanitizes field.
 *
 * @param type $field
 */
function wpcf_sanitize_field( $field ) {
    // Sanitize name
    if ( isset( $field['name'] ) ) {
        $field['name'] = sanitize_text_field( $field['name'] );
    }
    /**
     * autocreate name based on field type
     */
    if ( empty($field['name'] ) && isset($field['type']) ) {
        $field['name'] = sprintf(
            '%s - %s',
            ucfirst($field['type']),
            substr(md5(rand()), 0, 8)
        );
    }
    // Sanitize slug
    if ( !empty( $field['slug'] ) ) {
        $field['slug'] = sanitize_key( $field['slug'] );
    } else if ( isset( $field['name'] ) ) {
        $field['slug'] = sanitize_title( $field['name'] );
    }
    return $field;
}

/**
 * Gets all groups that contain specified field.
 *
 * @static $cache
 * @param type $field_id
 */
function wpcf_admin_fields_get_groups_by_field( $field_id,
        $post_type = TYPES_CUSTOM_FIELD_GROUP_CPT_NAME ) {
    static $cache = array();
    $groups = wpcf_admin_fields_get_groups( $post_type );
	switch ( $post_type ) {
		case TYPES_TERM_META_FIELD_GROUP_CPT_NAME:
			$meta_name = 'wpcf-termmeta';
			break;
		case TYPES_USER_META_FIELD_GROUP_CPT_NAME:
			$meta_name = 'wpcf-usermeta';
			break;
		case TYPES_CUSTOM_FIELD_GROUP_CPT_NAME:
		default:
			$meta_name = 'wpcf-fields';
			break;
	}
    $return = array();
    foreach ( $groups as $group_id => $group ) {
        if ( isset( $cache['groups'][$group_id] ) ) {
            $fields = $cache['groups'][$group_id];
        } else {
            $fields = wpcf_admin_fields_get_fields_by_group( $group['id'],
                    'slug', false, false, false, $post_type, $meta_name );
        }
        if ( array_key_exists( $field_id, $fields ) ) {
            $return[$group['id']] = $group;
        }
        $cache['groups'][$group_id] = $fields;
    }
    return $return;
}

/**
 * Saves last field settings when inserting from toolbar.
 *
 * @param $field_id
 * @param $settings
 * @param bool $append
 * @param bool $overwrite
 */
function wpcf_admin_fields_save_field_last_settings(
	$field_id, $settings, $append = false, $overwrite = false
) {
	$data = wpcf_ensarr( get_user_meta( get_current_user_id(), 'wpcf-field-settings', true ) );
	if ( $append && isset( $data[ $field_id ] ) && is_array( $data[ $field_id ] ) ) {
		$data[ $field_id ] = (
			$overwrite
			? array_merge( $data[ $field_id ], $settings )
			: array_merge( $settings, $data[ $field_id ] )
		);
	} else {
		$data[ $field_id ] = $settings;
	}
	update_user_meta( get_current_user_id(), 'wpcf-field-settings', $data );
}


/**
 * Gets last field settings when inserting from toolbar.
 *
 * @param $field_id
 *
 * @return array
 */
function wpcf_admin_fields_get_field_last_settings( $field_id ) {
    $data = get_user_meta( get_current_user_id(), 'wpcf-field-settings', true );
    if ( isset( $data[$field_id] ) ) {
        return $data[$field_id];
    }
    return array();
}

/**
 * Get active fields by post type
 *
 * Get active fields by post type chosing it from active groups and this 
 * groups which are connected to certain post type.
 *
 * @since: 1.7
 *
 * @param string $post_type post type
 *
 * @return array allowed meta keys
 */
function wpcf_admin_get_allowed_fields_by_post_type($post_type)
{
    $allowed_fields = $active_groups = array();
    $all_groups = wpcf_admin_get_groups_by_post_type($post_type);
    foreach( $all_groups as $group_id => $group_data ) {
        $active_groups[] = $group_data['slug'];
    }
    $all_groups = wpcf_admin_fields_get_groups(TYPES_CUSTOM_FIELD_GROUP_CPT_NAME, true, true);
    foreach( $all_groups as $group ) {
        if ( !in_array($group['slug'], $active_groups) ) {
            continue;
        }
        foreach( $group['fields'] as $field_key => $field_data ) {
            $allowed_fields[] = $field_data['meta_key'];
        }
    }
    return $allowed_fields;
}

/**
 * Returns all used field slugs except current group
 */
function wpcf_get_all_field_slugs_except_current_group( $current_group = false ) {
    $all_slugs       = array();
    $all_post_fields = get_option( 'wpcf-fields', array() );
    $all_user_fields = get_option( 'wpcf-usermeta', array() );
    $all_term_fields = get_option( 'wpcf-termmeta', array() );

    $all_fields = array_merge( $all_post_fields, $all_user_fields, $all_term_fields );

    if( !empty( $all_fields ) ) {
        foreach( $all_fields as $field ) {
            $all_slugs[] = $field['slug'];
        }
    }
    if( !$current_group && isset( $_REQUEST['group_id'] ) )
        $current_group = (int) $_REQUEST['group_id'];

    // if no new group
    if( $current_group && !empty( $all_fields ) ) {

        $current_group_fields = get_post_meta( $current_group, '_wp_types_group_fields', true );
        $current_group_fields = explode( ',', trim( $current_group_fields, ',' ) );

        if( !empty( $current_group_fields) ) {
            foreach( $current_group_fields as $field ) {
                $all_slugs = array_diff( $all_slugs, array( $field ) );
            }
        }

    }
    $all_slugs = array_values( $all_slugs );
    if( isset( $_POST['return'] )
        && $_POST['return'] == 'ajax-json' ) {
        echo json_encode( $all_slugs );
        die();
    }

    return $all_slugs;
}
add_action('wp_ajax_wpcf_get_all_field_slugs_except_current_group', 'wpcf_get_all_field_slugs_except_current_group');


/**
 * Helper function used on submitting a form with custom fields.
 *
 * When there's nothing checked in a checkbox/checkboxes/radio fields, there is no corresponding element
 * in $_POST['wpcf'][ $field_slug ], which would lead to _not removing_ the value from the database because of
 * the way the submission data is further processed.
 *
 * Here, we just create the key for the field without any further notification, and actual saving
 * these fields' values will be handled later as a special case via wpcf_update_checkboxes_field().
 *
 * @param array $wpcf_form_data Form data, usually coming from $_POST['wpcf'].
 * @param array $fields An associative array with checkbox/checkboxes/radio fields,
 *     where keys are field slugs with the 'wpcf-' prefix.
 *
 * @return array Updated form data with added keys for provided fields.
 * @since 2.2.7
 */
function wpcf_adjust_form_input_for_checkboxlike_fields( $wpcf_form_data, $fields ) {

	if( ! is_array( $wpcf_form_data ) ) {
		return array();
	}

	if( is_array( $fields ) ) {
		foreach ( $fields as $field_key => $field_value ) {
			$field_slug = preg_replace( '/^wpcf\-/', '', $field_key );
			if ( array_key_exists( $field_slug, $wpcf_form_data ) ) {
				continue;
			}
			$wpcf_form_data[ $field_slug ] = false;
		}
	}

	return $wpcf_form_data;
}