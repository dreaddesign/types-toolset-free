<?php

/**
* ########################################
* Common Relevanssi compatibility
* ########################################
*/

if ( ! class_exists( 'Toolset_Relevanssi_Compatibility' ) ) {
	
	class Toolset_Relevanssi_Compatibility {
		
		function __construct() {
			
			$this->relevanssi_installed		= false;
			$this->toolset_types_installed	= false;
			$this->toolset_views_installed	= false;
			
			$this->toolset_settings_url		= '';
			
			$this->pending_to_add			= array();
			$this->pending_to_remove		= array();
			
			add_action( 'init',				array( $this, 'init' ) );
			
		}
		
		function init() {
			
			if ( function_exists( 'relevanssi_init' ) ) {
				$this->relevanssi_installed = true;
			}
			$this->toolset_types_installed = apply_filters( 'types_is_active', false );
			$this->toolset_views_installed = apply_filters( 'toolset_is_views_available', false );
			
			if ( $this->toolset_types_installed ) {
				// Toolset Common
				
				$this->register_assets();
				add_action( 'toolset_menu_admin_enqueue_scripts',							array( $this, 'enqueue_scripts' ) );
				
				add_filter( 'toolset_filter_toolset_register_settings_section',				array( $this, 'register_relevanssi_settings_section' ), 120 );
				add_filter( 'toolset_filter_toolset_register_settings_relevanssi_section',	array( $this, 'toolset_relevanssi_settings' ), 10, 2 );
				add_action( 'wp_ajax_toolset_update_toolset_relevanssi_settings',			array( $this, 'toolset_update_toolset_relevanssi_settings' ) );
				
				$this->toolset_settings_url = admin_url( 'admin.php?page=toolset-settings&tab=relevanssi' );
				
				// Toolset Types
				
				add_filter( 'wpcf_form_field',												array( $this, 'types_extend_field_settings' ), 5, 3 );
				add_filter( 'wpcf_field_pre_save',											array( $this, 'types_flag_field_on_save' ) );
				add_action( 'wpcf_postmeta_fields_group_saved',								array( $this, 'types_store_fields_on_group_save' ) );
				
				add_action( 'admin_footer',													array( $this, 'types_manage_extended_field_settings' ), 25 );
				
				// Toolset Views
				
				if ( 
					$this->toolset_views_installed 
					&& $this->relevanssi_installed 
				) {
					// Views queries compatibility
					add_filter( 'wpv_filter_query',											array( $this, 'wpv_filter_query_compatibility' ), 99, 3 );
					add_filter( 'wpv_filter_query_post_process',							array( $this, 'wpv_filter_query_post_proccess_compatibility' ), 99, 3 );
					// Register search content options
					add_filter( 'wpv_filter_wpv_extend_post_search_content_options',		array( $this, 'wpv_extend_post_search_content_options' ) );
					// Fix Relevanssi on archive pages by forcing the Relevanssi query when needed
					add_action( 'wpv_action_wpv_before_clone_archive_loop',					array( $this, 'wpv_fix_relevanssi_on_archive_loops' ), 10, 2 );
					// Fix Relevanssi sorting as we were getting the Views objects one
					// Note that we do not allow table sorting on Relevanssi searches for now
					add_action( 'toolset_action_toolset_relevanssi_do_query_before',		array( $this, 'wpv_fix_relevanssi_orderby' ) );
					// Fix Relevanssi max_num_pages
					add_action( 'toolset_action_toolset_relevanssi_do_query_processed',		array( $this, 'wpv_fix_relevanssi_max_num_pages' ) );
					// Fix Relevanssi returning posts as objects but not as WP_Post objects
					add_action( 'toolset_action_toolset_relevanssi_do_query_processed',		array( $this, 'wpv_fix_relevanssi_return_as_post_objects' ) );
				}
			}
			
		}
		
		/*
		* ---------------------
		* Toolset Common integration
		* ---------------------
		*/
		
		function register_assets() {
			$toolset_bootstrap	= Toolset_Common_Bootstrap::getInstance();
			$toolset_assets		= $toolset_bootstrap->assets_manager;
			$toolset_assets->register_script(
				'toolset-relevanssi-settings-script', 
				$toolset_assets->get_assets_url() . '/res/js/toolset-settings-relevanssi.js', 
				array( 'jquery', 'underscore' ), 
				TOOLSET_VERSION, 
				true
			);
		}
		
		function enqueue_scripts( $page ) {
			if ( $page == 'toolset-settings' ) {
				$toolset_bootstrap	= Toolset_Common_Bootstrap::getInstance();
				$toolset_assets		= $toolset_bootstrap->assets_manager;
				$toolset_assets->enqueue_scripts( 'toolset-relevanssi-settings-script' );
			}
		}
		
		/**
		* Text search tab inside the Toolset Settings page.
		*
		* @since 2.2
		*/
		
		function register_relevanssi_settings_section( $registered_sections ) {
			$registered_sections['relevanssi'] = array(
				'slug'	=> 'relevanssi',
				'title'	=> __( 'Text Search', 'wpv-views' )
			);
			return $registered_sections;
		}
		
		/**
		* Text search tab content inside the Toolset Settings page.
		*
		* @since 2.2
		*/
		
		function toolset_relevanssi_settings( $sections, $toolset_options ) {
			
			$section_content = '';
			$text_search_documentation_link = 'https://toolset.com/documentation/user-guides/filtering-views-for-a-specific-text-string-search/?utm_source=viewsplugin&utm_campaign=views&utm_medium=views-text-search-settings&utm_term=Text Search documentation';

			
			if ( ! $this->relevanssi_installed ) {
				
				ob_start();
				
				?>
				<div class="notice inline notice-warning notice-alt">
					<p><?php _e( 'You need to install <strong>Relevanssi</strong>', 'wpv-views' ); ?></p>
				</div>
				<p>
				<?php
				echo sprintf( 
					__( '%1$sRelevanssi%2$s plugin extends and improves the WordPress text search. Relevanssi allows to search in custom fields and returns the most relevant results first.', 'wpv-views' ),
					'<a href="https://www.relevanssi.com/" target="_blank">',
					'</a>'
				);
				?>
				</p>
				<p>
				<?php
				if ( current_user_can( 'install_plugins' ) ) {
					echo sprintf(
						__( 'Please %1$sinstall Relevanssi%2$s to allow Toolset to search texts in custom fields.', 'wpv-views' ),
						'<a href="' . admin_url( 'plugin-install.php?s=relevanssi&tab=search&type=term' ) . '">',
						'</a>'
					);
				} else {
					echo sprintf(
						__( 'You can download Relevanssi it from %3$swordpress.org%4$s and ask your site administrator to install it.', 'wpv-views' ),
						'<a href="https://wordpress.org/plugins/relevanssi/" target="_blank">',
						'</a>'
					);
				}
				?>
				</p>
				<?php
				
				$section_content = ob_get_clean();
				
			} else {
			
				$relevanssi_fields_to_index	= isset( $toolset_options['relevanssi_fields_to_index'] ) ? $toolset_options['relevanssi_fields_to_index'] : array();
				$actual_fields_to_index		= array();
				$indexable_fields			= array();
				
				$groups_args = array(
					'domain' => 'posts',
					'is_active' => true
				);
				$groups = apply_filters( 'types_query_groups', array(), $groups_args );

				// Survive null output (error value)
				if( ! is_array( $groups ) ) {
					$groups = array();
				}

				/** @var Types_Field_Group $group */
				foreach ( $groups as $group ) {
					$args = array(
						'domain' => 'posts',
						'field_type' => array( 'textfield', 'textarea', 'wysiwyg' ),
						'group_id' => $group->get_id()
					);
					$fields_in_group = apply_filters( 'types_filter_query_field_definitions', array(), $args );
					if ( ! empty( $fields_in_group ) ) {
						$indexable_fields[ $group->get_display_name() ] = $fields_in_group;
					}
				}
				
				
				ob_start();
				
				if ( empty( $indexable_fields ) ) {
					?>
					<div class="notice inline notice-warning notice-alt">
						<p>
						<?php
						echo sprintf(
							__( '%1$sOnce you %2$ssetup textual custom fields in Types%3$s (single line, multiple lines, WYSIWYG), you will be able to include them in text searches here.', 'wpv-views' ),
							'<i class="icon-types-logo ont-color-orange ont-icon-24" style="margin-right:5px;vertical-align:-2px;"></i>',
							'<a href="' . admin_url( 'admin.php?page=wpcf-cf' ) . '">',
							'</a>'
						);
						?>
						</p>
					</div>
					<?php
				} else {
					?>
					<h3>
						<?php echo esc_html( __( 'Select which custom fields to include in text searches', 'wpv-views' ) ); ?>
					</h3>
					<div class="toolset-advanced-setting">
						<?php
						foreach( $indexable_fields as $indexable_fields_group_name => $indexable_fields_data ) {
							?>
							<h4><?php echo esc_html( $indexable_fields_group_name ); ?></h4>
							<ul class="toolset-mightlong-list js-toolset-relevanssi-list">
							<?php
							foreach ( $indexable_fields_data as $indexable_field_candidate ) {
								$candidate_checked = in_array( $indexable_field_candidate['meta_key'], $relevanssi_fields_to_index );
								if ( $candidate_checked ) {
									$actual_fields_to_index[] = $indexable_field_candidate['meta_key'];
								}
								?>
								<li>
									<label>
										<input type="checkbox" name="toolset-relevanssi-list-item" class="js-toolset-relevanssi-list-item" value="<?php echo esc_attr( $indexable_field_candidate['meta_key'] ); ?>" <?php checked( $candidate_checked ); ?> autocomplete="off" />
										<?php echo esc_html( $indexable_field_candidate['name'] ); ?>
									</label>
								</li>
								<?php
							}
							?>
							</ul>
							<?php
						}
						?>
					</div>
					<div class="js-toolset-relevanssi-list-summary"<?php echo ( count( $actual_fields_to_index ) > 0 ) ? '' : ' style="display:none"'; ?>>
						<h3>
							<?php echo esc_html( __( 'Update the Relevanssi settings and rebuild the index', 'wpv-views' ) ); ?>
						</h3>
						<ol style="list-style-type:upper-roman">
						<li>
						<?php
						_e( 'Copy this list of field names:', 'wpv-views' );
						?>
						<input type="text" 
							readonly="readonly" 
							class="js-toolset-relevanssi-list-summary-fields large-text" 
							style="display:block;padding:5px 10px;transition: 0.5s linear;" 
							value="<?php echo esc_attr( implode( ', ', $actual_fields_to_index ) ); ?>" 
						/>
						</li>
						<li>
						<?php
						echo sprintf(
							__( 'Paste into "Custom fields to index" in the %1$sRelevanssi settings%2$s. Remember to double check the post types included in the index, and notice that you can also add taxonomies to it.', 'wpv-views' ),
							'<a href="' . admin_url( 'options-general.php?page=relevanssi/relevanssi.php#indexing' ) . '">',
							'</a>'
						);
						?>
						</li>
						<li>
						<?php
						echo sprintf(
							__( 'Click on "Save indexing options, erase index and rebuild the index" in the %1$sRelevanssi settings%2$s.', 'wpv-views' ),
							'<a href="' . admin_url( 'options-general.php?page=relevanssi/relevanssi.php#indexing' ) . '">',
							'</a>'
						);
						?>
						</li>
						</ol>
						<p class="toolset-alert toolset-alert-info">
						<?php
						_e( 'Your site has one list of custom fields to index. This means that all the text searches will return results from these custom fields.', 'wpv-views' );
						?>
						</p>
						<p>
						<?php
						echo sprintf(
							__( 'See how to do this in the %1$sText Search documentation%2$s.', 'wpv-views' ),
							'<a href="' . $text_search_documentation_link . '" target="_blank">',
							'</a>'
						);
						?>
						</p>
					</div>
					<div class="js-toolset-relevanssi-list-summary-empty"<?php echo ( count( $actual_fields_to_index ) == 0 ) ? '' : ' style="display:none"'; ?>>
						<p class="toolset-alert toolset-alert-warning">
						<?php
						_e( 'You have not selected any field to be indexed in Relevanssi searches. Please select some below.', 'wpv-views' );
						?>
						</p>
					</div>					
					<?php
					wp_nonce_field( 'toolset_relevanssi_settings_nonce', 'toolset_relevanssi_settings_nonce' );
				}
				
				$section_content = ob_get_clean();
			
			}
			
			$sections['relevanssi-settings'] = array(
				'slug'		=> 'relevanssi-settings',
				'title'		=> __( 'Text search in custom fields', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}
		
		function toolset_update_toolset_relevanssi_settings() {
			$toolset_options = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			if ( 
				! isset( $_POST["wpnonce"] )
				|| ! wp_verify_nonce( $_POST["wpnonce"], 'toolset_relevanssi_settings_nonce' ) 
			) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			$fields = ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) ? array_map( 'sanitize_text_field', $_POST['fields'] ) : array();
			$toolset_options['relevanssi_fields_to_index'] = $fields;
			$toolset_options->save();
			wp_send_json_success();
		}
		
		/*
		* ---------------------
		* Types integration
		* ---------------------
		*/
		
		/**
		* Types textfield, textarea and WYSIWYG extended field settings.
		*
		* Add a section with a checkbox to register fields for Relevanssi integration.
		*
		* @param $form			array	Enlinbo definitions for form elements for a given field.
		* @param $data			array	Data for the current field being rendered. The only trusted keys that you get are:
		* 			- meta_type	string	'postmeta'|'termmeta'|'usermeta'.
		* 			- meta_key	string	The field meta_key.
		* @param $field_type	string	'textfield'|'textarea'|'wysiwyg'|... The field type.
		*
		* @since 2.2
		*/
		
		function types_extend_field_settings( $form, $data, $field_type = '' ) {
			$meta_type	= isset( $data['meta_type'] ) ? $data['meta_type'] : '';
			if (
				'postmeta' == $meta_type 
				&& in_array( $field_type, array( 'textfield', 'textarea', 'wysiwyg' ) )
			) {
				$toolset_settings			= Toolset_Settings::get_instance();
				$relevanssi_fields_to_index	= isset( $toolset_settings['relevanssi_fields_to_index'] ) ? $toolset_settings['relevanssi_fields_to_index'] : array();
				$this_field_to_index		= ( isset( $data['meta_key'] ) && in_array( $data['meta_key'], $relevanssi_fields_to_index ) );
				$this_field_description_style = $this_field_to_index ? 
											'' : 
											' style="display:none"';
				$this_field_description		= '<span class="js-toolset-toggle-relevanssi-index-description"' . $this_field_description_style . '>' . sprintf(
							__( 'Go to %1$sText Search settings%2$s to build the search index.', 'wpcf' ),
							'<a href="' . esc_url( $this->toolset_settings_url ) . '" target="_blank">',
							'</a>'
						) . '</span>';
				$form['relevanssi_index'] = array(
						'#type' => 'checkbox',
						'#name' => 'relevanssi_index',
						'#inline' => true,
						'#title' => __( 'Include in search', 'wpcf' ),
						'#label' => __( 'Include this field in text searches throughout the site', 'wpcf' ),
						'#description' => $this_field_description,
						'#attributes' => array(
							'autocomplete'	=> 'off',
							'class'			=> 'js-toolset-toggle-relevanssi-index'
						),
						'#pattern' => '<tr class="wpcf-border-top"><td><TITLE></td><td><ERROR><BEFORE><ELEMENT><LABEL><AFTER><DESCRIPTION></td></tr>'
					);
				if ( $this_field_to_index ) {
					$form['relevanssi_index']['#attributes']['checked'] = 'checked';
				}
			}
			return $form;
		}
		
		/**
		* Store the fields that need to be saved or deleted from the stored data when saving a fields group.
		*
		* Executed every time a field is saved, so we can check whether it belongs to the right types and has the right setting.
		* Note that we do nto store this setting in the field data.
		*
		* @param $field	array	Field data definitions to be saved.
		*
		* @since 2.2
		*/
		
		function types_flag_field_on_save( $field ) {
			
			$field_type	= isset( $field['type'] ) ? $field['type'] : '';
			if ( in_array( $field_type, array( 'textfield', 'textarea', 'wysiwyg' ) ) ) {
				
				$toolset_settings			= Toolset_Settings::get_instance();
				$relevanssi_fields_to_index	= isset( $toolset_settings['relevanssi_fields_to_index'] ) ? $toolset_settings['relevanssi_fields_to_index'] : array();
				
				if ( isset( $field['relevanssi_index'] ) ) {
					unset( $field['relevanssi_index'] );
					$this->pending_to_add[] = $field['slug'];
				} else {
					$this->pending_to_remove[] = $field['slug'];
				}
				
			}
			
			return $field;
		}
		
		/**
		* Update the stored data once a fields group has been saved.
		*
		* @param $group_id	string	The ID of the group being saved.
		*
		* @since 2.2
		*/
		
		function types_store_fields_on_group_save( $group_id ) {
			
			if ( 
				empty( $this->pending_to_add ) 
				&& empty( $this->pending_to_remove )
			) {
				return;
			}
			
			$relevanssi_fields_to_remove	= array();
			$relevanssi_fields_changed		= false;
			
			$toolset_settings			= Toolset_Settings::get_instance();
			$relevanssi_fields_to_index	= isset( $toolset_settings['relevanssi_fields_to_index'] ) ? $toolset_settings['relevanssi_fields_to_index'] : array();
			
			// Note that the 'refresh' argument is temporary and will not me needed much longer
			$args = array(
				'domain'		=> 'posts',
				'field_type'	=> array( 'textfield', 'textarea', 'wysiwyg' ),
				'group_id'		=> $group_id,
				'refresh'		=> true
			);
			// https://onthegosystems.myjetbrains.com/youtrack/issue/types-742
			$group_fields = apply_filters( 'types_filter_query_field_definitions', array(), $args );
			
			foreach ( $group_fields as $field ) {
				if ( in_array( $field['slug'], $this->pending_to_add ) ) {
					$real_custom_field_name = wpcf_types_get_meta_prefix( $field ) . $field['slug'];
					if ( ! in_array( $real_custom_field_name, $relevanssi_fields_to_index ) ) {
						$relevanssi_fields_to_index[] = $real_custom_field_name;
						$relevanssi_fields_changed = true;
					}
				}
				if ( in_array( $field['slug'], $this->pending_to_remove ) ) {
					$real_custom_field_name = wpcf_types_get_meta_prefix( $field ) . $field['slug'];
					if ( in_array( $real_custom_field_name, $relevanssi_fields_to_index ) ) {
						$relevanssi_fields_to_remove[] = $real_custom_field_name;
						$relevanssi_fields_changed = true;
					}
				}
			}
			
			if ( count( $relevanssi_fields_to_remove ) > 0 ) {
				$relevanssi_fields_to_index = array_diff( $relevanssi_fields_to_index, $relevanssi_fields_to_remove );
				$relevanssi_fields_to_index = is_array( $relevanssi_fields_to_index ) ? array_values( $relevanssi_fields_to_index ) : array();
			}
			
			if ( $relevanssi_fields_changed ) {
				$toolset_settings->relevanssi_fields_to_index = $relevanssi_fields_to_index;
				$toolset_settings->save();
			}
		}
		
		/**
		* Manage the Relevanssi section behavior based on its state.
		*
		* When the Relevanssi indexing chckbox is checked for the first time, glow the helper text linking to the documentation and Settings tab.
		* When the Relevanssi indexing checkbox is unchecked, hide the helper text.
		*
		* @since 2.2
		*/
		
		function types_manage_extended_field_settings() {
			
			$current_page = '';
			if ( isset( $_GET['page'] ) ) {
				$current_page = sanitize_text_field( $_GET['page'] );
			}
			
			if ( ! $current_page == 'wpcf-edit' ) {
				return;
			}
			
			if ( wp_script_is( 'jquery' ) ) {
				?>
				<script type="text/javascript">
					jQuery( document ).ready( function() {
						jQuery( document ).on( 'change', '.js-toolset-toggle-relevanssi-index', function() {
							
							var thiz = jQuery( this ),
								thiz_description = thiz.closest( 'td' ).find( '.js-toolset-toggle-relevanssi-index-description' );
								
							thiz_description.css( { 
								'transition':	'all 0.5s',
								'display':		'block' 
							} );
							
							if ( thiz.prop( 'checked' ) ) {
								
								thiz_description
									.fadeIn( 'fast', function() {
										if ( ! thiz.hasClass( 'js-toolset-toggle-relevanssi-index-inited' ) ) {
											thiz.addClass( 'js-toolset-toggle-relevanssi-index-inited' )
											thiz_description.css(
													{
														'box-shadow':		'0 0 5px 1px #f6921e',
														'background-color':	'#f6921e',
														'color':			'#fff'
													}
												);
											setTimeout( function() {
												thiz_description.css(
													{
														'box-shadow':		'none',
														'background-color':	'transparent',
														'color':			'#444'
													}
												);
											}, 500 );
										}
									});
									
							} else {
								
								thiz_description.hide();
								
							}
						});
					});
				</script>
				<?php
			}
		}
		
		/*
		* ---------------------
		* Views integration
		* ---------------------
		*/
		
		/**
		* wpv_filter_query_compatibility
		*
		* Disable Relevanssi when the search is set to only target post title or title plus content.
		* When set to target also extended data, do nothing as we will adjust the search after the query is run.
		*
		* @note we might want to easily abort the query, but might have side effects.
		*
		* @since 2.2
		*/
		
		function wpv_filter_query_compatibility( $query, $view_settings, $view_id ) {
			if ( isset( $view_settings['search_mode'] ) ) {
				$search_in = isset( $view_settings['post_search_content'] ) ? $view_settings['post_search_content'] : 'full_content';
				if ( $search_in != 'content_extended' ) {
					remove_filter('posts_request', 'relevanssi_prevent_default_request', 10, 3 );
				}
			}

			return $query;
		}
		
		/**
		* wpv_filter_query_post_proccess_compatibility
		*
		* Enable Relevanssi when the search is set to only target post title or title plus content, as it was disabled.
		* When set to target also extended data, replace the query with the one provided by Relevanssi.
		*
		* @since 2.2
		*/
		
		function wpv_filter_query_post_proccess_compatibility( $post_query, $view_settings, $view_id ) {
			if ( 
				isset( $view_settings['search_mode'] ) 
				&& function_exists( 'relevanssi_prevent_default_request' ) 
			) {
				$search_in = isset( $view_settings['post_search_content'] ) ? $view_settings['post_search_content'] : 'full_content';
				if ( $search_in == 'content_extended' ) {
					// This modifies $post_query as it is passed by reference
					if (
						isset( $_GET['wpv_post_search'] ) 
						&& ! empty( $_GET['wpv_post_search'] )
					) {
						// Modify the POSTed search
						do_action( 'toolset_action_toolset_relevanssi_do_query_before', $post_query );
						$relevanssi_posts = relevanssi_do_query( $post_query );
						do_action( 'toolset_action_toolset_relevanssi_do_query_processed', $post_query );
					} else if ( 
						in_array( 'specific', $view_settings['search_mode'] ) 
						&& isset( $view_settings['post_search_value'] ) 
						&& ! empty( $view_settings['post_search_value'] )
					) {
						// Modify the specific search
						//add_filter('posts_request', 'relevanssi_prevent_default_request', 10, 3 );
						do_action( 'toolset_action_toolset_relevanssi_do_query_before', $post_query );
						$relevanssi_posts = relevanssi_do_query( $post_query );
						do_action( 'toolset_action_toolset_relevanssi_do_query_processed', $post_query );
					}
				} else {
					add_filter('posts_request', 'relevanssi_prevent_default_request', 10, 3 );
				}
			}

			return $post_query;
		}
		
		/**
		* wpv_extend_post_search_content_options
		*
		* Extend the available searching options to include the Relevanssi index as source.
		*
		* @since 2.2
		*/
		
		function wpv_extend_post_search_content_options( $options ) {
			$options['content_extended'] = array(
				'label'			=> __( 'Title, body and custom fields', 'wpv-views' ),
				'description'	=> sprintf(
										__( 'Search in titles, content and custom fields. %1$sText Search settings%2$s.', 'wpv-views' ),
										'<a href="' . admin_url( 'admin.php?page=toolset-settings&tab=relevanssi' ) . '" target="_blank">',
										'</a>'
									),
				'summary'		=> __( 'post title, content and fields', 'wpv-views' )
			);
			return $options;
		}
		
		/**
		* wpv_fix_relevanssi_on_archive_loops
		*
		* On archive loops different from view_search-page that contain a search filter using content_extended modes, replace the query with the Relevanssi one.
		* On search archive loops, apply Relevanssi when needed.
		* In both cases, adjust when no post is returned by creating a dummy one and setting the loop_has_no_posts flag.
		*
		* @param $query	WP_Query object
		* @param $args	array
		* 		wpa_id
		* 		wpa_slug
		* 		wpa_settings
		* 		wpa_object
		*
		* @since 2.2
		*/
		
		function wpv_fix_relevanssi_on_archive_loops( $query, $args ) {
			if ( $query->query_vars["posts_per_page"] == -1 ) {
				$query->max_num_pages = 1;
			}
			if ( ! function_exists( 'relevanssi_prevent_default_request' ) ) {
				return;
			}
			
			$do_relevanssi_query = false;
			
			if (
				isset( $args['wpa_slug'] ) 
				&& $args['wpa_slug'] != 'view_search-page'
			) {
				$wpa_settings = isset( $args['wpa_settings'] ) ? $args['wpa_settings'] : array();
				if ( 
					isset( $wpa_settings['search_mode'] ) 
					&& isset( $_GET['wpv_post_search'] )
					&& ! empty( $_GET['wpv_post_search'] )
				) {
					$search_in = isset( $wpa_settings['post_search_content'] ) ? $wpa_settings['post_search_content'] : 'full_content';
					if ( $search_in == 'content_extended' ) {
						$do_relevanssi_query = true;
					}
				}
			} else if (
				is_search() 
				&& isset( $query->query_vars['s'] )
				&& ! empty( $query->query_vars['s'] )
			) {
				$do_relevanssi_query = true;
			}
			
			if ( $do_relevanssi_query ) {
				do_action( 'toolset_action_toolset_relevanssi_do_query_before', $query );
				$relevanssi_posts = relevanssi_do_query( $query );
				do_action( 'toolset_action_toolset_relevanssi_do_query_processed', $query );
				$wpa_object = isset( $args['wpa_object'] ) ? $args['wpa_object'] : null;
				if ( $wpa_object ) {
					if ( empty( $relevanssi_posts ) ) {
						$wpa_object->loop_has_no_posts = true;
						$query->post_count = 1;
						$dummy_post_obj = (object) array(
							'ID'				=> $args['wpa_id'],
							'post_author'		=> '1',
							'post_name'			=> '',
							'post_type'			=> '',
							'post_title'		=> '',
							'post_date'			=> '0000-00-00 00:00:00',
							'post_date_gmt'		=> '0000-00-00 00:00:00',
							'post_content'		=> '',
							'post_excerpt'		=> '',
							'post_status'		=> 'publish',
							'comment_status'	=> 'closed',
							'ping_status'		=> 'closed',
							'post_password'		=> '',
							'post_parent'		=> 0,
							'post_modified'		=> '0000-00-00 00:00:00',
							'post_modified_gmt'	=> '0000-00-00 00:00:00',
							'comment_count'		=> '0',
							'menu_order'		=> '0'
						);
						$dummy_post = new WP_Post( $dummy_post_obj );
						$query->posts = array( $dummy_post );
					} else {
						$wpa_object->loop_has_no_posts = false;
					}
				}
			}
		}
		
		/**
		 * wpv_fix_relevanssi_orderby
		 *
		 * Relevanssi only applies its sorting options when no other sorting setting was passed to the query it modifies.
		 * Views will always set a default sorting setting, hence on Relevanssi queries we need to null it.
		 *
		 * @since 2.3.0
		 */
		
		function wpv_fix_relevanssi_orderby( $query ) {
			$query->set( 'orderby', null );
		}
		
		/**
		* wpv_fix_relevanssi_max_num_pages
		*
		* Relevanssi stores an incorrect number in max_num_pages when posts_per_page = -1
		*
		* @since 2.2
		*/
		
		function wpv_fix_relevanssi_max_num_pages( $query ) {
			if ( 
				isset( $query->query_vars["posts_per_page"] ) 
				&& $query->query_vars["posts_per_page"] == -1 
			) {
				$query->max_num_pages = 1;
			}
		}
		
		/**
		* wpv_fix_relevanssi_return_as_post_objects
		*
		* Relevanssi return posts as dummy objects instead of WP_Post instances, and we need them.
		*
		* @since 2.3
		*/
		
		function wpv_fix_relevanssi_return_as_post_objects( $query ) {
			if ( $query->posts ) {
				$query->posts = array_map( 'get_post', $query->posts );
			}
		}

	}

}