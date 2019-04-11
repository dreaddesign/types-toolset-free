<?php

/**
 * Generic class for generating and controlling shortcodes.
 *
 * It manages the Toolset shortcodes admin bar entry.
 * It generates templates for shortcode attributes, as well as a target dialog for creating shortcodes.
 * It holds AJAX callbacks executed inside shortcode dialogs.
 *
 * Use the filter toolset_shortcode_generator_register_item before admin_init:99
 * Register your items as follows:
 * 		add_filter( 'toolset_shortcode_generator_register_item', 'register_my_shortcodes_in_the_shortcode_generator' );
 * 		function register_my_shortcodes_in_the_shortcode_generator( $registered_sections ) {
 * 			// Do your logic here to determine whether you need to add your section or not, and check if you need specific assets
 * 			// In case you do, register as follows:
 * 			$registered_sections['section-id'] = array(
 * 				'id'		=> 'section-id',						// The ID of the item
 *				'title'		=> __( 'My fields', 'my-textdomain' ),	// The title for the item
 *				'href'		=> '#my_anchor',						// The href attribute for the link of the item
 *				'parent'	=> 'toolset-shortcodes',				// Set the parent item as the known 'toolset-shortcodes'
 *				'meta'		=> 'js-my-classname'					// Cloassname for the li container of the item
 * 			);
 * 			return $registered_sections;
 * 		}
 *
 * Note that you will have to take care of displaying the dialog after clicking on the item, and deal with what is should do.
 *
 * @since 1.9
 */

abstract class Toolset_Shortcode_Generator {

	private static $registered_admin_bar_items	= array();
	private static $can_show_admin_bar_item		= false;
	private static $target_dialog_added			= false;

	function __construct() {
		add_action( 'init',	array( $this, 'register_shortcode_transformer' ) );

		add_action( 'admin_init',		array( $this, 'register_shortcodes_admin_bar_items' ), 99 );
	    add_action( 'admin_bar_menu',	array( $this, 'display_shortcodes_admin_bar_items' ), 99 );
		add_action( 'admin_footer',		array( $this, 'display_shortcodes_target_dialog' ) );

		add_action( 'toolset_action_require_shortcodes_templates', array( $this, 'print_shortcodes_templates' ) );

		add_filter( 'toolset_filter_shortcode_script_i18n', array( $this, 'extend_script_i18n' ) );
	}

	public function register_shortcodes_admin_bar_items() {

		// Only register sections if the Admin Bar item is to be shown.
		$toolset_settings = Toolset_Settings::get_instance();
		$toolset_shortcodes_generator = ( isset( $toolset_settings['shortcodes_generator'] ) && in_array( $toolset_settings['shortcodes_generator'], array( 'unset', 'disable', 'editor', 'always' ) ) ) ? $toolset_settings['shortcodes_generator'] : 'unset';
		if ( $toolset_shortcodes_generator == 'unset' ) {
			$toolset_shortcodes_generator = apply_filters( 'toolset_filter_force_unset_shortcode_generator_option', $toolset_shortcodes_generator );
		}
		$register_section = false;
		switch ( $toolset_shortcodes_generator ) {
			case 'always':
				$register_section = true;
				break;
			case 'editor':
				$register_section = $this->is_admin_editor_page();
				break;
		}

		/**
		 * Filters whether to forcibly show or hide the Toolset Shortcodes menu on the admin bar.
		 *
		 * Returning true to this hook will forcibly show the Toolset Shortcodes menu on the admin bar, ignoring
		 * the value of the relevant Toolset setting. Returning false to this hook will forcibly hide the Toolset
		 * Shortcodes menu on the admin bar, ignoring the value of the relevant Toolset setting.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $register_section Whether the Toolset Shortcodes menu on the admin bar should be forcibly shown or hidden.
		 */
		$register_section = apply_filters( 'toolset_filter_force_shortcode_generator_display', $register_section );

		if ( ! $register_section ) {
			return;
		}

		// Now that we know that it will be shown, collect the registered items.
		$registered_items = self::$registered_admin_bar_items;
		$registered_items = apply_filters( 'toolset_shortcode_generator_register_item', $registered_items );
		self::$registered_admin_bar_items = $registered_items;

	}

	/**
	 * Add admin bar main item for shortcodes
	 */
	public function display_shortcodes_admin_bar_items( $wp_admin_bar ) {
		if ( ! is_admin() ) {
			return;
		}
		$registered_items = self::$registered_admin_bar_items;
		if ( empty( $registered_items ) ) {
			return;
		}
		self::$can_show_admin_bar_item = true;
	    $this->create_admin_bar_item( $wp_admin_bar, 'toolset-shortcodes', __( 'Toolset shortcodes', 'wpv-views' ), '#', false );
		foreach ( $registered_items as $item_key => $item_args ) {
			$this->create_admin_bar_item( $wp_admin_bar, $item_args['id'], $item_args['title'], $item_args['href'], $item_args['parent'], $item_args['meta'] );
		}
	}

	/**
	 * General function for creating admin bar menu items
	 */
	public static function create_admin_bar_item( $wp_admin_bar, $id, $name, $href, $parent, $classes = null ) {
	    $args = array(
			'id'		=> $id,
			'title'		=> $name,
			'href'		=> $href,
			'parent'	=> $parent,
			'meta' 		=> array( 'class' => $id . '-shortcode-menu ' . $classes )
	    );
	    $wp_admin_bar->add_node( $args );
	}

	/**
	 * Helper method to check whether we are on an admin editor page.
	 * This covers edit pages for posts, terms and users,
	 * as well as Toolset object edit pages.
	 *
	 * @since 2.3.0
	 */

	public function is_admin_editor_page() {
		if ( ! is_admin() ) {
			return false;
		}
		global $pagenow, $wp_version;
		$allowed_pagenow_array = array( 'post.php', 'post-new.php', 'term.php', 'user-new.php', 'user-edit.php', 'profile.php' );
		$allowed_page_array = array( 'views-editor', 'ct-editor', 'view-archives-editor', 'dd_layouts_edit' );
		// @todo maybe add a filter here for future Toolset admin pages...
		if (
			in_array( $pagenow, $allowed_pagenow_array )
			|| (
				$pagenow == 'admin.php'
				&& isset( $_GET['page'] )
				&& in_array( $_GET['page'], $allowed_page_array )
			)
			|| (
				// In WordPress < 4.5, the edit tag admin page is edit-tags.php?action=edit&taxonomy=category&tag_ID=X
				version_compare( $wp_version, '4.5', '<' )
				&& $pagenow == 'edit-tags.php'
				&& isset( $_GET['action'] )
				&& $_GET['action'] == 'edit'
			)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Helper method to check whether we are on an frontend editor page.
	 * This should cover as many frontend editors as possible.
	 *
	 * @since 2.3.0
	 */

	public function is_frontend_editor_page() {
		if ( is_admin() ) {
			return false;
		}
		if (
			// Layouts frontend editor
			isset( $_GET['toolset_editor'] )
			// Beaver Builder frontend editor
			|| isset( $_GET['fl_builder'] )
			// CRED frontend editor pages, when discoverable
		) {
			return true;
		}
		return false;
	}

	/*
	 * Dialog Template HTML code
	 */
	public function display_shortcodes_target_dialog() {
		if (
			self::$can_show_admin_bar_item
			&& self::$target_dialog_added === false
		) {
			?>
			<div class="toolset-dialog-container" style="display:none">
				<div id="js-toolset-shortcode-generator-target-dialog" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-generator-target-dialog">
					<p>
						<?php echo __( 'This is the generated shortcode, based on the settings that you have selected:', 'wpv-views' ); ?>
					</p>
					<textarea id="js-toolset-shortcode-generator-target" readonly="readonly" style="width:100%;resize:none;box-sizing:border-box;font-family:monospace;display:block;padding:5px;background-color:#ededed;border: 1px solid #ccc !important;box-shadow: none !important;"></textarea>
					<p>
						<?php echo __( 'You can now copy and paste this shortcode anywhere you want.', 'wpv-views' ); ?>
					</p>
				</div>
			</div>
			<?php
			self::$target_dialog_added = true;
		}

	}

	/**
	 * Generate the shared Toolset shortcode GUI templates.
	 *
	 * @since 2.5.4
	 * @todo Move this to a separated dedicated set of template files.
	 * @todo The post and user selector templates are used exclusively by CRED: move to CRED and rename
	 */
	public function print_shortcodes_templates() {

		if ( did_action( 'toolset_action_require_shortcodes_templates_done' ) ) {
			return;
		}

		?>
		<script type="text/html" id="tmpl-toolset-shortcode-gui">
			<input value="{{{data.shortcode}}}" class="toolset-shortcode-gui-shortcode-handle js-toolset-shortcode-gui-shortcode-handle" type="hidden" />
			<# if ( _.has( data, 'parameters' ) ) {
				_.each( data.parameters, function( parameterValue, parameterKey ) {
					#>
					<span class="toolset-shortcode-gui-attribute-wrapper js-toolset-shortcode-gui-attribute-wrapper js-toolset-shortcode-gui-attribute-wrapper-for-{{{parameterKey}}}" data-attribute="{{{parameterKey}}}" data-type="parameter">
						<input type="hidden" name="{{{parameterKey}}}" value="{{{parameterValue}}}" disabled="disabled" />
					</span>
					<#
				});
			} #>
			<div id="js-toolset-shortcode-gui-dialog-tabs" class="toolset-shortcode-gui-tabs js-toolset-shortcode-gui-tabs">
			<# if ( _.size( data.attributes ) > 1 ) { #>
				<ul class="js-toolset-shortcode-gui-tabs-list">
					<# _.each( data.attributes, function( attributesGroup, groupKey ) { #>
						<# if (
							_.has( attributesGroup, 'fields' )
							&& _.size( attributesGroup.fields ) > 0
						) { #>
						<li>
							<a href="#{{{data.shortcode}}}-{{{groupKey}}}">{{{attributesGroup.header}}}</a>
						</li>
						<# } #>
					<# }); #>
				</ul>
			<# } #>
				<# _.each( data.attributes, function( attributesGroup, groupKey ) { #>
					<# if (
						_.has( attributesGroup, 'fields' )
						&& _.size( attributesGroup.fields ) > 0
					) { #>
					<div id="{{{data.shortcode}}}-{{{groupKey}}}">
						<h2>{{{attributesGroup.header}}}</h2>
						<# _.each( attributesGroup.fields, function( attributeData, attributeKey ) {
							if ( _.has( data.templates, 'attributeWrapper' ) ) {
								attributeData = _.extend( { shortcode: data.shortcode, attribute: attributeKey, templates: data.templates }, attributeData );
								if ( 'group' == attributeData.type ) {
									print( data.templates.attributeGroupWrapper( attributeData ) );
								} else {
									print( data.templates.attributeWrapper( attributeData ) );
								}
							}
						}); #>
					</div>
					<# } #>
				<# }); #>
			</div>
			<div class="toolset-shortcode-gui-messages js-toolset-shortcode-gui-messages"></div>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-wrapper">
			<#
				data = _.defaults( data, { defaultValue: '', required: false, hidden: false, placeholder: '' } );
				data = _.defaults( data, { defaultForceValue: data.defaultValue } );
			#>
			<div class="toolset-shortcode-gui-attribute-wrapper js-toolset-shortcode-gui-attribute-wrapper js-toolset-shortcode-gui-attribute-wrapper-for-{{{data.attribute}}}" data-attribute="{{{data.attribute}}}" data-type="{{{data.type}}}" data-default="{{{data.defaultValue}}}"<# if ( data.hidden ) { #> style="display:none"<# } #>>
				<# if ( _.has( data, 'label' ) ) { #>
					<h3>{{{data.label}}}</h3>
				<# } #>
				<# if ( _.has( data, 'pseudolabel' ) ) { #>
					<strong>{{{data.pseudolabel}}}</strong>
				<# } #>
				<# if (
					_.has( data.templates, 'attributes' )
					&& _.has( data.templates.attributes, data.type )
				) {
					print( data.templates.attributes[ data.type ]( data ) );
				} #>
				<# if ( _.has( data, 'description' ) ) { #>
					<p class="description">{{{data.description}}}</p>
				<# } #>
			</div>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-group-wrapper">
			<div class="toolset-shortcode-gui-attribute-group js-toolset-shortcode-gui-attribute-group js-toolset-shortcode-gui-attribute-group-for-{{{data.attribute}}}" data-type="group" data-group="{{{data.attribute}}}"<# if ( data.hidden ) { #> style="display:none"<# } #>>
				<# if ( _.has( data, 'label' ) ) { #>
					<h3>{{{data.label}}}</h3>
				<# } #>
				<#
				var columns = _.size( data.fields ),
					columnsWidth = parseInt( 100 / columns );
				#>
				<ul class="toolset-shortcode-gui-dialog-item-group js-toolset-shortcode-gui-dialog-item-group">
					<# _.each( data.fields, function( fieldData, fieldAttribute ) { #>
						<li style="width:<# print( columnsWidth ); #>%;min-height:1px;float:left;">
							<#
							fieldData = _.defaults( fieldData, { shortcode: data.shortcode, templates: data.templates } );
							fieldData = _.defaults( fieldData, { defaultValue: '', required: false, hidden: false, placeholder: '' } );
							fieldData = _.defaults( fieldData, { defaultForceValue: fieldData.defaultValue } );
							fieldData.attribute = fieldAttribute;
							print( data.templates.attributeWrapper( fieldData ) );
							#>
						</li>
					<# }); #>
				</ul>
				<# if ( _.has( data, 'description' ) ) { #>
					<p class="description">{{{data.description}}}</p>
				<# } #>
			</div>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-information">
			<div id="{{{data.shortcode}}}-{{{data.attribute}}}" class="toolset-alert toolset-alert-info">
				{{{data.content}}}
			</div>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-text">
			<input id="{{{data.shortcode}}}-{{{data.attribute}}}" data-type="text" class="js-shortcode-gui-field large-text<# if ( data.required ) { #> js-toolset-shortcode-gui-required<# } #>" value="{{{data.defaultForceValue}}}" placeholder="{{{data.placeholder}}}" type="text">
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-radio">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<# _.each( data.options, function( optionLabel, optionKey ) { #>
					<li>
						<label>
							<input name="{{{data.shortcode}}}-{{{data.attribute}}}" value="{{{optionKey}}}" class="js-shortcode-gui-field" type="radio"<# if ( optionKey == data.defaultForceValue ) { #> checked="checked"<# } #>>
							{{{optionLabel}}}
						</label>
					</li>
				<# }); #>
			</ul>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-select">
			<select id="{{{data.shortcode}}}-{{{data.attribute}}}" class="js-shortcode-gui-field<# if ( data.required ) { #> js-toolset-shortcode-gui-required<# } #>">
				<# _.each( data.options, function( optionLabel, optionKey ) { #>
					<option value="{{{optionKey}}}"<# if ( optionKey == data.defaultForceValue ) { #> selected="selected"<# } #>>
						{{{optionLabel}}}
					</option>
				<# }); #>
			</select>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-select2">
			<select id="{{{data.shortcode}}}-{{{data.attribute}}}" class="js-shortcode-gui-field js-toolset-shortcode-gui-field-select2<# if ( data.required ) { #> js-toolset-shortcode-gui-required<# } #>">
				<#
				if ( _.has( data, 'options' ) ) {
					_.each( data.options, function( optionLabel, optionKey ) {
					#>
					<option value="{{{optionKey}}}"<# if ( optionKey == data.defaultForceValue ) { #> selected="selected"<# } #>>
						{{{optionLabel}}}
					</option>
					<#
					});
				}
				#>
			</select>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-ajaxSelect2">
			<select
				id="{{{data.shortcode}}}-{{{data.attribute}}}"
				class="js-shortcode-gui-field js-toolset-shortcode-gui-field-ajax-select2<# if ( data.required ) { #> js-toolset-shortcode-gui-required<# } #>"
				data-action="{{{data.action}}}"
				data-nonce="{{{data.nonce}}}"
				data-placeholder="{{{data.placeholder}}}"
				>
			</select>
		</script>

		<script type="text/html" id="tmpl-toolset-shortcode-content">
			<#
				data = _.defaults( data, { defaultValue: '', required: false, hidden: false, placeholder: '' } );
				data = _.defaults( data, { defaultForceValue: data.defaultValue } );
			#>
			<div class="toolset-shortcode-gui-attribute-wrapper js-toolset-shortcode-gui-content-wrapper" <# if ( data.hidden ) { #> style="display:none"<# } #>>
				<textarea id="toolset-shortcode-gui-content-{{{data.shortcode}}}" type="text" class="large-text js-toolset-shortcode-gui-content">{{{data.defaultValue}}}</textarea>
				<# if ( _.has( data, 'description' ) ) { #>
					<p class="description">{{{data.description}}}</p>
				<# } #>
			</div>
		</script>

		<?php $toolset_ajax = Toolset_Ajax::get_instance(); ?>

		<script type="text/html" id="tmpl-toolset-shortcode-attribute-postSelector">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-post-id-current">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-current" name="toolset_shortcode_gui_object_id" value="current" checked="checked" />
						<?php _e( 'The current post being displayed either directly or in a View loop', 'wpv-views' ); ?>
					</label>
				</li>

			<?php

			global $pagenow, $post;
			$current_post_type = null;
			if (
				in_array( $pagenow, array( 'post.php' ) )
				&& isset( $_GET["post"] )
			) {
				$current_post_id = (int) $_GET["post"];
				$current_post_type_slug = get_post_type( $current_post_id );
				$current_post_type = get_post_type_object( $current_post_type_slug );
			} elseif (
				isset( $post )
				&& ( $post instanceof WP_Post )
				&& ( ! in_array( $post->post_type, array( 'view', 'view-template', 'cred-form', 'cred-user-form', 'dd_layouts' ) ) )
			) {
				$current_post_type = get_post_type_object( $post->post_type );
			}
			
			// Current top page when displaying a View loop
			if (
				in_array( $pagenow, array( 'admin.php' ) )
				&& 'views-editor' === toolset_getget( 'page' )
			) {
				?>
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-post-id-current_page">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-current_page" name="toolset_shortcode_gui_object_id" value="$current_page" />
						<?php echo __( 'The page where this View is shown', 'wpv-views' ); ?>
					</label>
				</li>
				<?php
			}

			// Poost hierarchical relations
			if (
				is_null( $current_post_type )
				|| (
					is_object( $current_post_type )
					&& isset( $current_post_type->hierarchical )
					&& $current_post_type->hierarchical
				)
			) {
				?>
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-post-id-parent">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-parent" name="toolset_shortcode_gui_object_id" value="$parent" />
						<?php echo __( 'The parent of the current post in the same post type, set by WordPress hierarchical relationship', 'wpv-views' ); ?>
					</label>
				</li>
				<?php
			}


			// Types relations
			if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				// Legacy relationships
				$current_post_type_parents = $this->get_legacy_current_post_type_relationships( $current_post_type );
				$custom_post_types_relations = get_option( 'wpcf-custom-types', array() );

				if ( ! empty( $current_post_type_parents ) ) {
					?>
					<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
						<label for="toolset-shortcode-gui-item-selector-post-id-related">
							<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-related" name="toolset_shortcode_gui_object_id" value="related" />
							<?php echo __( 'The parent of the current post in another post type, set by a Types relationship', 'wpv-views' ); ?>
						</label>
						<div class="toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none">
							<ul class="toolset-advanced-setting tolset-mightlong-list" style="padding-top:15px;margin:5px 0 10px;">
							<?php
							$first = true;
							foreach ( $current_post_type_parents as $slug  ) {
								?>
								<li>
									<?php echo sprintf( '<label for="toolset-shortcode-gui-item-selector-post-relationship-id-%s">', $slug ); ?>
									<?php echo sprintf(
										'<input type="radio" name="related_object" id="toolset-shortcode-gui-item-selector-post-relationship-id-%s" value="$%s" %s />',
										esc_attr( $slug ),
										esc_attr( $slug ),
										checked( $first, true, false )
									); ?>
									<?php echo esc_html( $custom_post_types_relations[ $slug ]['labels']['singular_name'] ); ?>
									</label>
								</li>
								<?php
								$first = false;
							}
							?>
							</ul>
						</div>
					</li>
					<?php
				}

			} else {
				// m2m relationships
				// Make sure m2m classes are registered in the autoloader
				do_action( 'toolset_do_m2m_full_init' );
				$relationship_definitions_per_origin = $this->get_m2m_current_post_type_relationships_per_origin( $current_post_type );
				
				$relationship_section_title_per_cardinality = array(
					'one-to-one' => __( '%s (one-to-one relationship)', 'wpv-views' ),
					'one-to-many' => __( '%s (one-to-many relationship)', 'wpv-views' ),
					'many-to-many' => __( '%s (many-to-many relationship)', 'wpv-views' ),
				);

				if ( ! empty( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ] ) ) {
					?>
					<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
						<label for="toolset-shortcode-gui-item-selector-post-id-related">
							<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-related" name="toolset_shortcode_gui_object_id" value="related" />
							<?php echo __( 'A post related to the current post, set by a Types relationship', 'wpv-views' ); ?>
						</label>
						<div class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none">
							<?php
							foreach ( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ] as $relationship_definition  ) {
								$cardinality = $relationship_definition->get_cardinality()->get_type();
								$relationship_selectors_factory = new Toolset_Shortcode_Attr_Item_Gui_Factory(
									$relationship_definition, $current_post_type, 'related_object'
								);
								$relationship_selectors = $relationship_selectors_factory->get_options();
								?>
								<div style="margin:5px 0 0;">
								<h3><?php echo sprintf(
									$relationship_section_title_per_cardinality[ $cardinality ],
									$relationship_definition->get_display_name()
								); ?></h3>
								<ul>
								<?php
								foreach( $relationship_selectors as $relationship_selector_option ) {
									echo '<li style="display:inline-block;width:31%;vertical-align:top;margin-right:1%;>' . $relationship_selector_option . '</li>';
								}
								?>
								</ul>
								</div>
								<?php
							}
							?>
						</div>
					</li>
					<?php
				}

				if ( ! empty( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ] ) ) {
					?>
					<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
						<label for="toolset-shortcode-gui-item-selector-post-id-referenced">
							<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-referenced" name="toolset_shortcode_gui_object_id" value="referenced" />
							<?php echo __( 'A post related to the current post, set by a Types post reference field', 'wpv-views' ); ?>
						</label>
						<div class="toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none">
							<ul class="toolset-advanced-setting toolset-mightlong-list" style="padding-top:15px;margin:5px 0 10px;">
							<?php
							foreach ( $relationship_definitions_per_origin[ Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ] as $relationship_definition  ) {
								$relationship_selectors_factory = new Toolset_Shortcode_Attr_Item_Gui_Factory(
									$relationship_definition, $current_post_type, 'referenced_object'
								);
								$relationship_selectors = $relationship_selectors_factory->get_options();
								foreach( $relationship_selectors as $relationship_selector_option ) {
									echo '<li>' . $relationship_selector_option . '</li>';
								}
							}
							?>
							</ul>
						</div>
					</li>
					<?php
				}

			}

			?>
				<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
					<label for="toolset-shortcode-gui-item-selector-post-id">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id" name="toolset_shortcode_gui_object_id" value="object_id" />
						<?php _e( 'A specific post (search by title)', 'wpv-views' ); ?>
					</label>
					<div class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none;padding-top:10px;">
						<select id="toolset-shortcode-gui-item-selector-post-id-object_id"
							class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
							name="specific_object_id"
							data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ) ); ?>"
							data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ) ); ?>"
							data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ); ?>"
							data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ); ?>"
							data-placeholder="<?php echo esc_attr( __( 'Search for a post by title', 'wpv-views' ) ); ?>">
						</select>
					</div>
				</li>

                <li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
                    <label for="toolset-shortcode-gui-item-selector-post-id-raw">
                        <input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-post-id-raw" name="toolset_shortcode_gui_object_id" value="object_id_raw" />
						<?php _e( 'A specific post (set by post ID)', 'wpv-views' ); ?>
                    </label>
                    <div class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none;padding-top:10px;">
                        <input type="number" placeholder="<?php echo esc_attr( __( 'Please enter post ID', 'wpv-views' ) ); ?>" id="toolset-shortcode-gui-item-selector-post-id-object_id_raw" class="regular-text js-toolset-shortcode-gui-item-selector_object_id_raw" name="specific_object_id_raw">
                    </div>
                </li>

            </ul>
			<p class="description">
				<?php echo sprintf(
					__( 'Learn about displaying content from parent and other posts in the %sdocumentation page%s.', 'wpv-views' ),
					'<a href="https://toolset.com/documentation/user-guides/displaying-fields-of-parent-pages/" target="_blank">',
					'</a>'
				); ?>
			</p>
		</script>

		<script type="text/html" id="tmpl-toolset-shortcode-attribute-userSelector">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-user-id-current">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-user-id-current" name="toolset_shortcode_gui_object_id" value="current" checked="checked" />
						<?php _e( 'The current user or the one being displayed in a View loop', 'wpv-views' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
					<label for="toolset-shortcode-gui-item-selector-user-id">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector" id="toolset-shortcode-gui-item-selector-user-id" name="toolset_shortcode_gui_object_id" value="object_id" />
						<?php _e( 'A specific user', 'wpv-views' ); ?>
					</label>
					<div class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none;padding-top:10px;">
						<select id="toolset-shortcode-gui-item-selector-user-id-object_id"
							class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
							name="specific_object_id"
							data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) ); ?>"
							data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) ); ?>"
							data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ); ?>"
							data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ); ?>"
							data-placeholder="<?php echo esc_attr( __( 'Search for a user', 'wpv-views' ) ); ?>">
						</select>
					</div>
				</li>
			</ul>
		</script>

		<script type="text/html" id="tmpl-toolset-shortcode-attribute-post-selector">
			<ul id="{{data.shortcode}}}-{{data.attribute}}}">
				<li>
					<label>
						<input type="radio" name="{{{data.shortcode}}}-select-target-post" class="toolset-shortcode-gui-item-selector js-toolset-shortcode-gui-item-selector" value="current" checked="checked" />
						<?php
						if (
							isset( $_GET['page'] )
							&& in_array( $_GET['page'], array( 'views-editor', 'view-archives-editor' ) )
						) {
							_e( 'The current post in the loop', 'wp-cred' );
						} else {
							_e( 'The current post', 'wp-cred' );
						}
						?>
					</label>
				</li>
				<li class="js-toolset-shortcode-gui-item-selector-has-related">
					<label>
						<input type="radio" name="{{{data.shortcode}}}-select-target-post" class="toolset-shortcode-gui-item-selector js-toolset-shortcode-gui-item-selector" value="object_id" />
						<?php _e( 'Another post', 'wp-cred' ); ?>
						<div class="toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none">
							<select id="toolset-shortcode-gui-item-selector-post-id-object_id"
								class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
								name="specific_object_id"
								data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ) ); ?>"
								data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ) ); ?>"
								data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ); ?>"
								data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_POST_BY_ID ); ?>"
								data-placeholder="<?php echo esc_attr( __( 'Search for a post by title', 'wpv-views' ) ); ?>">
							</select>
						</div>
					</label>
				</li>
			</ul>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-user-selector">
			<ul id="{{data.shortcode}}}-{{data.attribute}}}">
				<li>
					<label>
						<input type="radio" name="{{{data.shortcode}}}-select-target-user" class="toolset-shortcode-gui-item-selector js-toolset-shortcode-gui-item-selector" value="current" checked="checked" />
						<?php _e( 'The current logged in user', 'wp-cred' ); ?>
					</label>
				</li>
				<li class="js-toolset-shortcode-gui-item-selector-has-related">
					<label>
						<input type="radio" name="{{{data.shortcode}}}-select-target-user" class="toolset-shortcode-gui-item-selector js-toolset-shortcode-gui-item-selector" value="object_id" />
						<?php _e( 'Another user', 'wp-cred' ); ?>
						<div class="toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related" style="display:none">
							<select id="toolset-shortcode-gui-item-selector-user-id-object_id"
								class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
								name="specific_object_id"
								data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) ); ?>"
								data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) ); ?>"
								data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ); ?>"
								data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ); ?>"
								data-placeholder="<?php echo esc_attr( __( 'Search for a user', 'wpv-views' ) ); ?>">
							</select>
						</div>
					</label>
				</li>
			</ul>
		</script>
		<?php

		do_action( 'toolset_action_require_shortcodes_templates_done' );

	}

	public function extend_script_i18n( $toolset_shortcode_i18n ) {
		$post_selector_attribute = 'id';
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$post_selector_attribute = 'item';
		}
		$toolset_shortcode_i18n['selectorGroups'] = array(
			'postSelector' => array(
				'header' => __( 'Post selection', 'wpv-views' ),
				'fields' => array(
					$post_selector_attribute => array(
						'label'        => __( 'Display data for:', 'wpv-views' ),
						'type'         => 'postSelector',
						'defaultValue' => 'current'
					)
				)
			),
			'termSelector' => array(
				'header' => __( 'Taxonomy term selection', 'wpv-views' ),
				'fields' => array(
					'item' => array(
						'label'        => __( 'Display data for:', 'wpv-views' ),
						'type'         => 'termSelector',
						'defaultValue' => 'current'
					)
				)
			),
			'userSelector' => array(
				'header' => __( 'User selection', 'wpv-views' ),
				'fields' => array(
					'item' => array(
						'label'        => __( 'Display data for:', 'wpv-views' ),
						'type'         => 'userSelector',
						'defaultValue' => 'current'
					)
				)
			)
		);

		return $toolset_shortcode_i18n;
	}

	/**
	 * Get Types legacy parent relationships for a given post type, or all the existing parents otherwise.
	 *
	 * @paran $current_post_type string|null
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function get_legacy_current_post_type_relationships( $current_post_type ) {
		$current_post_type_parents = array();

		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return $current_post_type_parents;
		}

		$current_post_type_parents = array();
		$custom_post_types_relations = get_option( 'wpcf-custom-types', array() );

		if ( is_null( $current_post_type ) ) {
			foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
				if ( isset( $cptr_data['post_relationship']['has'] ) ) {
					$current_post_type_parents[] = $cptr_key;
				}
				if (
					isset( $cptr_data['post_relationship']['belongs'] )
					&& is_array( $cptr_data['post_relationship']['belongs'] )
				) {
					$this_belongs = array_keys( $cptr_data['post_relationship']['belongs'] );
					foreach ( $this_belongs as $this_belongs_candidate ) {
						if ( isset( $custom_post_types_relations[ $this_belongs_candidate ] ) ) {
							$current_post_type_parents[] = $this_belongs_candidate;
						}
					}
				}
			}
		} else if (
			is_object( $current_post_type )
			&& isset( $current_post_type->slug )
		) {
			// Fix legacy problem, when child CPT has no parents itself, but parent CPT has children
			foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
				if (
					isset( $cptr_data['post_relationship']['has'] )
					&& in_array( $current_post_type->slug, array_keys( $cptr_data['post_relationship']['has'] ) )
				) {
					$current_post_type_parents[] = $cptr_key;
				}
			}
			if ( isset( $custom_post_types_relations[ $current_post_type->slug ] ) ) {
				$current_post_type_data = $custom_post_types_relations[ $current_post_type->slug ];
				if (
					isset( $current_post_type_data['post_relationship'] )
					&& ! empty( $current_post_type_data['post_relationship'] )
					&& isset( $current_post_type_data['post_relationship']['belongs'] )
				) {
					foreach ( array_keys( $current_post_type_data['post_relationship']['belongs'] ) as $cpt_in_relation ) {
						// Watch out! WE are not currently clearing the has and belongs entries of the relationships when deleting a post type
						// So make sure the post type does exist
						if ( isset( $custom_post_types_relations[ $cpt_in_relation ] ) ) {
							$current_post_type_parents[] = $cpt_in_relation;
						}
					}
				}
			}
		}

		$current_post_type_parents = array_values( $current_post_type_parents );
		$current_post_type_parents = array_unique( $current_post_type_parents );

		return $current_post_type_parents;
	}

	/**
	 * Get Types one-to-many and one-to-one relationships for a given post type, or all the existing otherwise.
	 *
	 * For post reference fields relationships, make sure we alwars offer to insert the parent post data,
	 * as they do support relationships between the same post type, hence the relationship ends get blurred.
	 *
	 * @param $current_post_type string|null
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function get_m2m_current_post_type_relationships_per_origin( $current_post_type ) {
		
		$relationship_definitions_per_origin = array(
			Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD => array(),
			Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD => array()
		);

		$query = new Toolset_Relationship_Query_V2();

		// Note that we can not use $query->do_if() because it actually runs both branches
		// and one of them expects $current_post_type->name to exist
		if ( $current_post_type instanceof WP_Post_Type ) {
			$relationship_definitions = $query
				->add(
					$query->do_and(
						$query->do_or(
							$query->has_domain_and_type( $current_post_type->name, Toolset_Element_Domain::POSTS ),
							$query->intermediary_type( $current_post_type->name )
						),
						$query->do_or(
							$query->origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ),
							$query->origin( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD )
						)
					)
				)
				->get_results();
		} else {
			$relationship_definitions = $query
				->add(
					$query->do_or(
						$query->origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ),
						$query->origin( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD )
					)
				)
				->get_results();
		}

		foreach( $relationship_definitions as $relationship_definition ) {
			$relationship_cardinality = $relationship_definition->get_cardinality();
			$origin = $relationship_definition->get_origin();
			
			$relationship_definitions_per_origin[ $origin->get_origin_keyword() ][] = $relationship_definition;
		}

		return $relationship_definitions_per_origin;
	}

	/**
     * Register the Toolset shortcode transformer that will transform shortcode from the new format to the old one
     * for proper rendering.
     *
     * @since 2.5.7
	 */
	public function register_shortcode_transformer() {
	    $shortcode_transformer = new Toolset_Shortcode_Transformer();
		$shortcode_transformer->init_hooks();
    }

}