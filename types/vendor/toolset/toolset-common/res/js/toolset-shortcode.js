/**
 * API and helper functions for the GUI on Toolset shortcodes.
 *
 * @since 2.5.4
 * @package Toolset
 */

var Toolset = Toolset || {};

/**
 * -------------------------------------
 * Shortcode GUI
 * -------------------------------------
 */

Toolset.shortcodeManager = function( $ ) {
	
	var self = this;
	
	/**
	 * Shortcodes GUI API version.
	 *
	 * Access to it using the API methods, from inside this object:
	 * - self.getShortcodeGuiApiVersion
	 * 
	 * Access to it using the API hooks, from the outside world:
	 * - toolset-filter-get-shortcode-gui-api-version
	 *
	 * @since 2.5.4
	 */
	self.apiVersion = 254000;
	
	/**
	 * Get the current shortcodes GUI API version.
	 *
	 * @see toolset-filter-get-shortcode-gui-api-version
	 *
	 * @since 2.5.4
	 */
	self.getShortcodeGuiApiVersion = function( version ) {
		return self.apiVersion;
	};
	
	/**
	 * Dialog rendering helpers, mainly size calculators.
	 *
	 * @since 2.5.4
	 */
	self.dialogMinWidth = 870;
	self.calculateDialogMaxWidth = function() {
		return ( $( window ).width() - 200 );
	};
	self.calculateDialogMaxHeight = function() {
		return ( $( window ).height() - 100 );
	};
	
	/**
	 * The current GUI API action to be performed. Can be 'insert', 'create', 'save', 'append', 'edit', 'skip'.
	 *
	 * Access to it using the API methods, from inside this object:
	 * - self.getShortcodeGuiAction
	 * - self.setShortcodeGuiAction
	 * 
	 * Access to it using the API hooks, from the outside world:
	 * - toolset-filter-get-shortcode-gui-action
	 * - toolset-action-set-shortcode-gui-action
	 *
	 * @since 2.5.4
	 */
	self.action			= 'insert';
	self.validActions	= [ 'insert', 'create', 'save', 'append', 'edit', 'skip' ];
	
	/**
	 * Get the current shortcodes GUI action.
	 *
	 * @see wpv-filter-wpv-shortcodes-gui-get-gui-action
	 *
	 * @since 2.5.4
	 */
	self.getShortcodeGuiAction = function( action ) {
		return self.action;
	};
	
	/**
	 * Set the current shortcodes GUI action.
	 *
	 * @see wpv-action-wpv-shortcodes-gui-set-gui-action
	 *
	 * @since 2.5.4
	 */
	self.setShortcodeGuiAction = function( action ) {
		if ( -1 !== $.inArray( action, self.validActions ) ) {
			self.action = action;
		}
	};
	
	/**
	 * Register the canonical Toolset hooks, both API filters and actions.
	 *
	 * @return self;
	 *
	 * @since 2.5.4
	 */
	self.initHooks = function() {
		
		/*
		 * ###############################
		 * API filters
		 * ###############################
		 */
		
		/**
		 * Return the current shortcodes GUI API version.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-api-version', self.getShortcodeGuiApiVersion );
		
		/**
		 * Return the current shortcode GUI action: 'insert', 'create', 'save', 'append', 'edit', 'skip'.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-action', self.getShortcodeGuiAction );
		
		/**
		 * Validate a shortcode attributes container.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-is-shortcode-attributes-container-valid', self.isShortcodeAttributesContainerValid, 10, 2 );
		
		/**
		 * Return the shortcode GUI templates.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-templates', self.getShortcodeTemplates );
		
		/**
		 * Return the postSelector/termSeletor/userSelector attributes data.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-postSelector-attributes', self.getPostSelectorAttributes );
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-termSelector-attributes', self.getTermSelectorAttributes );
		Toolset.hooks.addFilter( 'toolset-filter-get-shortcode-gui-userSelector-attributes', self.getUserSelectorAttributes );
		
		/**
		 * Return the current crafted shortcode with the current dialog GUI attrbutes.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-crafted-shortcode', self.getCraftedShortcode, 10, 2 );

		/**
		 * Filter the generated Types shortcode to support shortcodes with different format.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-get-crafted-shortcode', self.secureShortcodeFromSanitizationIfNeeded, 11 );
		
		/**
		 * Return the current crafted shortcode with the current dialog GUI attrbutes.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-computed-attribute-values', self.resolveToolsetComboValues, 1, 2 );

        /**
         * Filter the generated shortcode to support shortcodes with different format.
         *
         * @since 2.5.4
         */
        Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', self.secureShortcodeFromSanitizationIfNeeded );

        /**
         * Filter the generated shortcode to support shortcodes with different format.
         *
         * @since 2.5.4
         */
        Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-transform-format', self.secureShortcodeFromSanitizationIfNeeded );
		
		/*
		 * ###############################
		 * API actions
		 * ###############################
		 */
		
		/**
		 * Register a new template.
		 *
		 * @since m2m
		 */
		Toolset.hooks.addAction( 'toolset-filter-register-shortcode-gui-attribute-template', self.registerShortcodeAttributeTemplate, 1, 2 );
		
		/**
		 * Set the current shortcodes GUI action: 'insert', 'create', 'save', 'append', 'edit', 'skip'.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addAction( 'toolset-action-set-shortcode-gui-action', self.setShortcodeGuiAction );
		
		/**
		 * Act upon the generated shortcode according to the current shortcodes GUI action: 'insert', 'create', 'save', 'append', 'edit', 'skip'.
		 *
		 * @since 2.5.4
		 * @since m2m Add the callback for the "save" action.
		 */
		Toolset.hooks.addAction( 'toolset-action-do-shortcode-gui-action', self.doAction );
		Toolset.hooks.addAction( 'toolset-action-do-shortcode-gui-action-create', self.doActionCreate, 1, 1 );
		Toolset.hooks.addAction( 'toolset-action-do-shortcode-gui-action-insert', self.doActionInsert, 1, 1 );
		Toolset.hooks.addAction( 'toolset-action-do-shortcode-gui-action-save', self.doActionSave, 1, 1 );
		
		/**
		 * Init select2 instances on shortcode dialogs once they are completely opened.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addAction( 'toolset-action-shortcode-dialog-loaded', self.initSelect2 );
		
		/**
		 * Init the post selectors and reference field selectors once the shortcode dialog is completely opened.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addAction( 'toolset-action-shortcode-dialog-loaded', self.initPostSelector );
		
		/**
		 * Init a wizard dialog, set the options width, and set the right value as selected.
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.addAction( 'toolset-action-set-shortcode-wizard-gui', self.setShortcodeWizardGui );
		
		return self;
		
	};
	
	/**
	 * Init GUI templates.
	 *
	 * @uses wp.template
	 * @since 2.5.4
	 * @since m2m Add the attributeGroupWrapper template, and the content, postSelector and userSelector attributes templates.
	 */
	self.templates = {};
	self.initTemplates = function() {
		self.templates.dialog = wp.template( 'toolset-shortcode-gui' );
		self.templates.attributeWrapper = wp.template( 'toolset-shortcode-attribute-wrapper' );
		self.templates.attributeGroupWrapper = wp.template( 'toolset-shortcode-attribute-group-wrapper' );
		self.templates.attributes = {
			content: wp.template( 'toolset-shortcode-content' ),
			information: wp.template( 'toolset-shortcode-attribute-information' ),
			text: wp.template( 'toolset-shortcode-attribute-text' ),
			radio: wp.template( 'toolset-shortcode-attribute-radio' ),
			select: wp.template( 'toolset-shortcode-attribute-select' ),
			select2: wp.template( 'toolset-shortcode-attribute-select2' ),
			ajaxSelect2: wp.template( 'toolset-shortcode-attribute-ajaxSelect2' ),
			postSelector: wp.template( 'toolset-shortcode-attribute-postSelector' ),
			userSelector: wp.template( 'toolset-shortcode-attribute-userSelector' ),
			// CRED selectors templates
			post: wp.template( 'toolset-shortcode-attribute-post-selector' ),
			user: wp.template( 'toolset-shortcode-attribute-user-selector' )
		};
		return self;
	}
	
	/**
	 * Get all registered templates.
	 *
	 * @param templates object Initial dummy parameter si this can be used as a filter callback.
	 *
	 * @return object
	 *
	 * @since m2m
	 */
	self.getShortcodeTemplates = function( templates ) {
		return self.templates;
	};
	
	/**
	 * Register a wp.template for an attribute type and make it available globally.
	 *
	 * @param templateName string
	 * @param template     wp.template
	 *
	 * @since m2m
	 */
	self.registerShortcodeAttributeTemplate = function( templateName, template ) {
		if ( ! _.has( self.templates.attributes, templateName ) ) {
			self.templates.attributes[ templateName ] = template;
		}
	}
	
	/**
	 * Get the canonical post|term|user selectors attributes to append them before generating the shortcode dialog.
	 *
	 * @param attributes object Initial dummy parameter si this can be used as a filter callback.
	 *
	 * @return object
	 *
	 * @since 2.5.4
	 * @todo This is currently just used by CRED and probably should become CRED-only templates.
	 */
	self.getPostSelectorAttributes = function( $attributes ) {
		return { postSelector: toolset_shortcode_i18n.selectorGroups.postSelector };
	};
	self.getTermSelectorAttributes = function( $attributes ) {
		return { termSelector: toolset_shortcode_i18n.selectorGroups.termSelector };
	};
	self.getUserSelectorAttributes = function( $attributes ) {
		return { userSelector: toolset_shortcode_i18n.selectorGroups.userSelector };
	};
	
	/**
	 * Init GUI dialogs.
	 *
	 * @uses jQuery.dialog
	 * @since 2.5.4
	 */
	self.dialogs = {};
	self.dialogs.target = null;
	
	self.shortcodeDialogSpinnerContent = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="ajax-loader"></div>' +
		'<p>' + toolset_shortcode_i18n.action.loading + '</p>' +
		'</div>' +
		'</div>'
	);
	
	self.initDialogs = function() {
		
		/**
		 * Canonical dialog to generate Toolset shortcodes.
		 *
		 * @since 2.5.4
		 */
		if ( ! $( '#js-toolset-shortcode-generator-target-dialog' ).length ) {
			$( 'body' ).append( '<div id="js-toolset-shortcode-generator-target-dialog" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container"></div>' );
		}
		self.dialogs.target = $( '#js-toolset-shortcode-generator-target-dialog' ).dialog({
			dialogClass: 'toolset-ui-dialog',
			autoOpen:	false,
			modal:		true,
			width:		self.dialogMinWidth,
			title:		toolset_shortcode_i18n.title.generated,
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			create: function( event, ui ) {
				$( event.target ).parent().css( 'position', 'fixed' );
			},
			open: function( event, ui ) {
				$( '#js-toolset-shortcode-generator-target' )
					.html( $( this ).data( 'shortcode' ) )
					.focus();
				$('body').addClass('modal-open');
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.setShortcodeGuiAction( 'insert' );
				$( this ).dialog( 'close' );
			}
		});
		
		return self;
	};
	
	/**
	 * Control the item selector behavior for options that have further settings.
	 *
	 * @since 2.5.4
	 */
	$( document ).on( 'change', 'input.js-toolset-shortcode-gui-item-selector', function() {
		var checkedSelector = $( this ).val();
		$( '.js-toolset-shortcode-gui-item-selector-has-related' ).each( function() {
			var hasRelatedContainer = $( this );
			if ( $( 'input.js-toolset-shortcode-gui-item-selector:checked', hasRelatedContainer ).val() == checkedSelector ) {
				$( '.js-toolset-shortcode-gui-item-selector-is-related', hasRelatedContainer ).slideDown( 'fast' );
			} else {
				$( '.js-toolset-shortcode-gui-item-selector-is-related', hasRelatedContainer ).slideUp( 'fast' );
			}
		});
	});
	
	/**
	 * Init select2 attributes controls.
	 *
	 * @since 2.5.4
	 */
	self.initSelect2Attributes = function() {
		$( '.js-toolset-shortcode-gui-dialog-container .js-toolset-shortcode-gui-field-select2:not(.js-toolset-shortcode-gui-field-select2-inited)' ).each( function() {
			var selector = $( this ),
				selectorParent = selector.closest( '.js-toolset-shortcode-gui-dialog-container' );
			
			selector
				.addClass( 'js-toolset-shortcode-gui-field-select2-inited' )
				.css( { width: '100%' } )
				.toolset_select2(
					{ 
						width:				'resolve',
						dropdownAutoWidth:	true, 
						dropdownParent:		selectorParent,
						placeholder:		selector.data( 'placeholder' )
					}
				)
				.data( 'toolset_select2' )
					.$dropdown
						.addClass( 'toolset_select2-dropdown-in-dialog' );
		});
	};
	
	/**
	 * Init the ajaxSelect2 attributes action.
	 *
	 * @since 2.5.4
	 */
	self.initSelect2AjaxAction = function( selector ) {
		var selectorParent = selector.closest( '.js-toolset-shortcode-gui-dialog-container' );
		selector
				.addClass( 'js-toolset-shortcode-gui-field-select2-inited' )
				.css( { width: '100%' } )
				.toolset_select2(
					{ 
						width:				'resolve',
						dropdownAutoWidth:	true, 
						dropdownParent:		selectorParent,
						placeholder:		selector.data( 'placeholder' ),
						minimumInputLength:	2,
						ajax: {
							url: toolset_shortcode_i18n.ajaxurl,
							dataType: 'json',
							delay: 250,
							type: 'post',
							data: function( params ) {
								return {
									action:  selector.data( 'action' ),
									s:       params.term,
									page:    params.page,
									wpnonce: selector.data( 'nonce' )
								};
							},
							processResults: function( originalResponse, params ) {
								var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
								params.page = params.page || 1;
								if ( response.success ) {
									return {
										results: response.data,
									};
								}
								return {
									results: [],
								};
							},
							cache: false
						}
					}
				)
				.data( 'toolset_select2' )
					.$dropdown
						.addClass( 'toolset_select2-dropdown-in-dialog' );
	};
	
	/**
	 * Init ajaxSelect2 attributes controls.
	 * Get the prefill label for any existing value.
	 *
	 * @since 2.5.4
	 */
	self.initSelect2AjaxAttributes = function() {
		$( '.js-toolset-shortcode-gui-dialog-container .js-toolset-shortcode-gui-field-ajax-select2:not(.js-toolset-shortcode-gui-field-select2-inited)' ).each( function() {
			var selector = $( this );
			
			if ( 
				selector.val() 
				&& selector.data( 'prefill' )
			) {
				var prefillData = {
					action:  selector.data( 'prefill' ),
					wpnonce: selector.data( 'prefill-nonce' ),
					s:       selector.val()
				};
				$.ajax({
					url:     toolset_shortcode_i18n.ajaxurl,
					data:    prefillData,
					type:    "post",
					success: function( originalResponse ) {
						var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
						if ( response.success ) {
							selector
								.find( 'option:selected' )
									.html( response.data.label );
						} else {
							selector
								.find( 'option:selected' )
									.remove();
						}
						self.initSelect2AjaxAction( selector );
					},
					error: function ( ajaxContext ) {
						selector
							.find( 'option:selected' )
								.remove();
						self.initSelect2AjaxAction( selector );
					}
				});
			} else {
				self.initSelect2AjaxAction( selector );
			}
			
		});
	};
	
	/**
	 * Init select2 and ajaxSelect2 attributes controls.
	 *
	 * @since 2.5.4
	 */
	self.initSelect2 = function() {
		self.initSelect2Attributes();
		self.initSelect2AjaxAttributes();
	};
	
	/**
	 * Set the first post selector and post reference selector as checked, if any.
	 *
	 * @since m2m
	 */
	self.initPostSelector = function() {
		$( 'input[name="related_object"]:not(:disabled)', '.js-toolset-shortcode-gui-dialog-container' )
			.first()
				.prop( 'checked', true );
		
		$( 'input[name="referenced_object"]:not(:disabled)', '.js-toolset-shortcode-gui-dialog-container' )
			.first()
				.prop( 'checked', true );
	};
	
	/**
	 * Initialize the wizard GUI for a shortcode.
	 *
	 * @param string The value to set as selected
	 *
	 * @since m2m
	 */
	self.setShortcodeWizardGui = function( value ) {
		var $optionsContainer = $( '.js-toolset-shortcode-gui-wizard-options-container', '.js-toolset-shortcode-gui-wizard-container' ),
			$options = $( '.js-toolset-shortcode-gui-wizard-option', $optionsContainer ),
			optionsLength = $options.length;
		
		$( '.toolset-shortcode-gui-wizard-option-selected', $optionsContainer ).removeClass( 'toolset-shortcode-gui-wizard-option-selected' );
		$optionsContainer.find( 'input[value=' + value + ']' )
			.prop( 'checked', true )
			.trigger( 'change' )
				.closest( '.js-toolset-shortcode-gui-wizard-option' )
					.addClass( 'toolset-shortcode-gui-wizard-option-selected' );
	};
	
	$( document ).on( 'change', '.js-toolset-shortcode-gui-wizard-option input[type=radio]', function() {
		var $optionsContainer = $( '.js-toolset-shortcode-gui-wizard-options-container', '.js-toolset-shortcode-gui-wizard-container' );
		
		$( '.toolset-shortcode-gui-wizard-option-selected', $optionsContainer ).removeClass( 'toolset-shortcode-gui-wizard-option-selected' );
		
		$( 'input[type=radio].toolset-shortcode-gui-wizard-option-hidden:checked', $optionsContainer )
			.closest( '.js-toolset-shortcode-gui-wizard-option' )
				.addClass( 'toolset-shortcode-gui-wizard-option-selected' );
	});
	
	/**
	 * Clean validation errors on input change.
	 *
	 * @since 2.5.4
	 */
	$( document ).on( 'change keyup input cut paste', '.js-toolset-shortcode-gui-dialog-container input, .js-toolset-shortcode-gui-dialog-container select', function() {
		$( this ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
		if ( $( this ).hasClass( 'toolset_select2-hidden-accessible' ) ) {
			$( this )
				.toolset_select2()
					.data( 'toolset_select2' )
						.$selection
							.removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
		}
	});
	
	/**
	 * Control the toolsetCombo attribute value behavior for options that combine a set of valus plus free input.
	 *
	 * @since 2.5.4
	 */
	$( document ).on( 'change', 'input.js-shortcode-gui-field:radio', function() {
		var checkedValue = $( this ).val(),
			attribute = $( this ).closest( '.js-toolset-shortcode-gui-attribute-wrapper' ).data( 'attribute' ),
			comboAttributeWrapper = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:' + attribute );
		
		if ( comboAttributeWrapper.length == 0 ) {
			return;
		}
		
		if ( 'toolsetCombo' == checkedValue ) {
			comboAttributeWrapper.slideDown( 'fast' );
		} else {
			comboAttributeWrapper.slideUp( 'fast' );
		}
	});
	
	/**
	 * Validation patterns.
	 *
	 * @since 2.5.4
	 */
	self.validationPatterns = {
		number: /^[0-9]+$/,
		numberList: /^\d+(?:,\d+)*$/,
		numberExtended: /^(-1|[0-9]+)$/,
		url: /^(https?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
		
	};
	
	/**
	 * Check whether a container for attributes is valid, including required and validation tests.
	 *
	 * @param status    bool   Initial dummy parameter si this can be used as a filter callback.
	 * @param container object jQuery object matching the container to evaluate.
	 *
	 * @return bool
	 *
	 * @since 2.5.4
	 */
	self.isShortcodeAttributesContainerValid = function( status, container ) {
		return self.validateShortcodeAttributes( container );
	}
	
	/**
	 * Check required shortcode attributes while crafting the shortcode.
	 *
	 * @param evaluatedContainer object jQuery object matching the container to evaluate.
	 *
	 * @return bool
	 *
	 * @since 2.5.4
	 */
	self.requireShortcodeAttributes = function( evaluatedContainer ) {
		var valid = true;
		
		evaluatedContainer.find( '.js-shortcode-gui-field.js-toolset-shortcode-gui-required' ).each( function() {
			var requiredAttribute = $( this ),
				requiredAttributeIsValid = true;
	
			// Here we are checking for empty text inputs and selects with the default empty option selected.
			if ( 
				null === requiredAttribute.val() 
				|| '' == requiredAttribute.val() 
			) {
				requiredAttribute.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				requiredAttributeIsValid = false;
				
				if ( requiredAttribute.hasClass( 'toolset_select2-hidden-accessible' ) ) {
					requiredAttribute
						.toolset_select2()
							.data( 'toolset_select2' )
								.$selection
									.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				}
				
			}
			if ( ! requiredAttributeIsValid ) {
				valid = false;
				/*
				error_container
					.wpvToolsetMessage({
						text: wpv_shortcodes_gui_texts.attr_empty,
						type: 'error',
						inline: false,
						stay: true
					});
				// Hack to allow more than one error message per filter
				error_container
					.data( 'message-box', null )
					.data( 'has_message', false );
				*/
			}
		});
		
		evaluatedContainer.find( 'input.js-shortcode-gui-field:radio:checked' ).each( function() {
			var checkedValue = $( this ).val(),
				attribute = $( this ).closest( '.js-toolset-shortcode-gui-attribute-wrapper' ).data( 'attribute' ),
				comboAttributeWrapper = $( '.js-toolset-shortcode-gui-attribute-wrapper-for-toolsetCombo\\:' + attribute );
				
			if (
				'toolsetCombo' == checkedValue 
				&& comboAttributeWrapper.length > 0 
			) {
				var comboAttributeActualSelector = comboAttributeWrapper.find( '.js-shortcode-gui-field' );
				if (
					null == comboAttributeActualSelector.val() 
					|| '' == comboAttributeActualSelector.val()  
				) {
					comboAttributeActualSelector.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					if ( comboAttributeActualSelector.hasClass( 'toolset_select2-hidden-accessible' ) ) {
						comboAttributeActualSelector
							.toolset_select2()
								.data( 'toolset_select2' )
									.$selection
										.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					}
					valid = false;
				}
			}
		});
		
		return valid;
	};
	
	/**
	 * Validate shortcode attributes before crafting the final shortcode.
	 *
	 * @param evaluatedContainer object jQuery object matching the container to evaluate.
	 *
	 * @return bool
	 *
	 * @since 2.5.4
	 * @todo Implement actual validation
	 */
	self.validateShortcodeAttributes = function( evaluatedContainer ) {
		var valid = true;
		
		valid = self.requireShortcodeAttributes( evaluatedContainer );
		if ( ! valid ) {
			return false;
		}
		/*
		$evaluatedContainer.find( 'input:text' ).each( function() {
			var thiz = $( this ),
				thiz_val = thiz.val(),
				thiz_type = thiz.data( 'type' ),
				thiz_message = '',
				thiz_valid = true;
			if ( ! thiz.hasClass( 'js-toolset-shortcode-gui-invalid-attr' ) ) {
				switch ( thiz_type ) {
					case 'number':
						if (
							self.numeric_natural_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_number_invalid;
						}
						break;
					case 'numberextended':
						if (
							self.numeric_natural_extended_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_number_invalid;
						}
						break;
					case 'numberlist':
						if (
							self.numeric_natural_list_pattern.test( thiz_val.replace(/\s+/g, '') ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_numberlist_invalid;
						}
						break;
					case 'year':
						if (
							self.year_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_year_invalid;
						}
						break;
					case 'month':
						if (
							self.month_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_month_invalid;
						}
						break;
					case 'week':
						if (
							self.week_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_week_invalid;
						}
						break;
					case 'day':
						if (
							self.day_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_day_invalid;
						}
						break;
					case 'hour':
						if (
							self.hour_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_hour_invalid;
						}
						break;
					case 'minute':
						if (
							self.minute_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_minute_invalid;
						}
						break;
					case 'second':
						if (
							self.second_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_second_invalid;
						}
						break;
					case 'dayofyear':
						if (
							self.dayofyear_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_dayofyear_invalid;
						}
						break;
					case 'dayofweek':
						if (
							self.dayofweek_pattern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_dayofweek_invalid;
						}
						break;
					case 'url':
						if (
							self.url_patern.test( thiz_val ) == false
							&& thiz_val != ''
						) {
							thiz_valid = false;
							thiz.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
							thiz_message = wpv_shortcodes_gui_texts.attr_url_invalid;
						}
						break;
				}
				if ( ! thiz_valid ) {
					valid = false;
					error_container
						.wpvToolsetMessage({
							text: thiz_message,
							type: 'error',
							inline: false,
							stay: true
						});
					// Hack to allow more than one error message per filter
					error_container
						.data( 'message-box', null )
						.data( 'has_message', false );
				}
			}
		});
		*/
		// Special case: item selector tab
        var $itemSelector = $( '.js-toolset-shortcode-gui-item-selector:checked', evaluatedContainer );
        if (
            $itemSelector.length > 0
            && (
                'object_id' == $itemSelector.val() ||
                'object_id_raw' == $itemSelector.val()
            )
        ) {

            var itemSelection = ( 'object_id' == $itemSelector.val() )
                ? $( '[name="specific_object_id"]', evaluatedContainer )
                : $( '[name="specific_object_id_raw"]', evaluatedContainer );

            var	itemSelectionId = itemSelection.val(),
                itemSelectionValid = true;
				//$itemSelectionMessage = '';
			if ( '' == itemSelectionId ) {
				itemSelectionValid = false;
				itemSelection.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				if ( itemSelection.hasClass( 'toolset_select2-hidden-accessible' ) ) {
					itemSelection
						.toolset_select2()
							.data( 'toolset_select2' )
								.$selection
									.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				}
				//$itemSelectionMessage = cred_shortcode_i18n.validation.mandatory;
			} else if ( self.validationPatterns.number.test( itemSelectionId ) == false ) {
				itemSelectionValid = false;
				itemSelection.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				if ( itemSelection.hasClass( 'toolset_select2-hidden-accessible' ) ) {
					itemSelection
						.toolset_select2()
							.data( 'toolset_select2' )
								.$selection
									.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				}
				//$itemSelectionMessage = cred_shortcode_i18n.validation.number;
			}
			if ( ! itemSelectionValid ) {
				valid = false;
			}
		}
		return valid;
	};
	
	/**
	 * Get the shortcode crafted with the current dialog shortcode attributes.
	 *
	 * @param defaultValue string Initial dummy parameter si this can be used as a filter callback.
	 * @param $dialog      object The jQuery object that holds the dialog to craft the shortcode for.
	 *
	 * @return string
	 *
	 * @since m2m
	 */ 
	self.getCraftedShortcode = function( defaultValue, $dialog ) {
		if ( $dialog == null ) {
			// Backwards compatibility: before m2m we did not force a dialog to craft the shortcode from
			$dialog = $( '.js-toolset-shortcode-gui-dialog-container' );
		}
		return self.craftShortcode( $dialog );
	}
	
	/**
	 * Craft a shortcode given the attributes in the currently open dialog.
	 *
	 * @param $dialog object The jQuery object that holds the dialog to craft the shortcode for.
	 *
	 * @return string
	 *
	 * @since 2.5.4
	 * @since m2m Add support for postSelector, userSelector, typesViewsTermSelector, typesUserSelector and typesViewsUserSelector attribute types.
	 */
	self.craftShortcode = function( $dialog ) {
		var shortcodeName = $( '.js-toolset-shortcode-gui-shortcode-handle', $dialog ).val(),
			shortcodeAttributeString = '',
			shortcodeAttributeValues = {},
			shortcodeRawAttributeValues = {},
			shortcodeContent = '',
			shortcodeToInsert = '',
			shortcodeIsValid = self.validateShortcodeAttributes( $dialog );
		
		if ( ! shortcodeIsValid ) {
			return;
		}
		
		$( '.js-toolset-shortcode-gui-attribute-wrapper', $dialog ).each( function() {
			var attributeWrapper = $( this ),
				shortcodeAttributeKey = attributeWrapper.data( 'attribute' ),
				shortcodeAttributeValue = '',
				shortcodeAttributeDefaultValue = attributeWrapper.data( 'default' );
			switch ( attributeWrapper.data('type') ) {
				case 'post':
				case 'postSelector':
				case 'user':
				case 'userSelector':
				case 'typesViewsTermSelector':
				case 'typesUserSelector':
				case 'typesViewsUserSelector':
					shortcodeAttributeValue = $( '.js-toolset-shortcode-gui-item-selector:checked', attributeWrapper ).val();
					switch( shortcodeAttributeValue ) {
						case 'current':
							shortcodeAttributeValue = false;
							break;
						case 'related':
							shortcodeAttributeValue = $( '[name="related_object"]:checked', attributeWrapper ).val();
							break;
						case 'referenced':
							shortcodeAttributeValue = $( '[name="referenced_object"]:checked', attributeWrapper ).val();
							break;
                        case 'object_id_raw':
                            shortcodeAttributeValue = $( '.js-toolset-shortcode-gui-item-selector_object_id_raw', attributeWrapper ).val();
                            break;
                        case 'object_id':
							shortcodeAttributeValue = $( '.js-toolset-shortcode-gui-item-selector_object_id', attributeWrapper ).val();
							break;
						case 'parent': // The value is correct out of the box
						default:
							break;
					}
					break;
				case 'select':
				case 'select2':
				case 'ajaxSelect2':
					shortcodeAttributeValue = $('select', attributeWrapper ).val();
					break;
				case 'radio':
				case 'radiohtml':
					shortcodeAttributeValue = $('input:checked', attributeWrapper ).val();
					break;
				case 'checkbox':
					shortcodeAttributeValue = $('input:checked', attributeWrapper ).val();
					break;
				case 'information':
					shortcodeAttributeValue = false;
					break;
				default:
					shortcodeAttributeValue = $('input', attributeWrapper ).val();
			}

			// Fix true/false from data attribute for shortcodeAttributeDefaultValue
			if ( 'boolean' == typeof shortcodeAttributeDefaultValue ) {
				shortcodeAttributeDefaultValue = shortcodeAttributeDefaultValue ? 'true' :'false';
			}
			
			// Add to the shortcodeRawAttributeValues collection
			shortcodeRawAttributeValues[ shortcodeAttributeKey ] = shortcodeAttributeValue;
			
			/**
			 * Filter each shortcode attribute value separatedly, using two different filters:
			 * - toolset-filter-shortcode-gui-attribute-value
			 * - toolset-filter-shortcode-gui-{shortcodeName}-attribute-{shortcodeAttributeKey}-value
			 *
			 * @param shortcodeAttributeValue string
			 * @param object
			 *     { 
			 *         shortcode: shortcodeName, 
			 *         attribute: shortcodeAttributeKey 
			 *     }
			 *
			 * @since 2.5.4
			 */
			shortcodeAttributeValue = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-attribute-value', shortcodeAttributeValue, { shortcode: shortcodeName, attribute: shortcodeAttributeKey } );
			shortcodeAttributeValue = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-' + shortcodeName + '-attribute-' + shortcodeAttributeKey + '-value', shortcodeAttributeValue, { shortcode: shortcodeName, attribute: shortcodeAttributeKey } );
			
			// Add to the shortcodeAttributeValues collection
			if (
				shortcodeAttributeValue
				&& shortcodeAttributeValue != shortcodeAttributeDefaultValue
			) {
				shortcodeAttributeValues[ shortcodeAttributeKey ] = shortcodeAttributeValue;
			}
		});
		
		/**
		 * Filter all shortcode attribute values, using two different filters:
		 * - toolset-filter-shortcode-gui-computed-attribute-values
		 * - toolset-filter-shortcode-gui-{shortcodeName}-computed-attribute-values
		 *
		 * @param shortcodeAttributeValues object
		 * @param object
		 *     { 
		 *         shortcode:     shortcodeName, 
		 *         rawAttributes: shortcodeRawAttributeValues
		 *     }
		 *
		 * @since 2.5.4
		 */
		shortcodeAttributeValues = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-computed-attribute-values', shortcodeAttributeValues, { shortcode: shortcodeName, rawAttributes: shortcodeRawAttributeValues } );
		shortcodeAttributeValues = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-' + shortcodeName + '-computed-attribute-values', shortcodeAttributeValues, { shortcode: shortcodeName, rawAttributes: shortcodeRawAttributeValues } );
		
		// Compose the shortcodeAttributeString string
		_.each( shortcodeAttributeValues, function( value, key ) {
			if ( value ) {
				shortcodeAttributeString += " " + key + "='" + value + "'";
			}
		});
		
		// Compose the shortcodeToInsert string
		shortcodeToInsert = '[' + shortcodeName + shortcodeAttributeString + ']';
		
		// Shortcodes with content: add it plus the closing shortode tag
		if ( $( '.js-toolset-shortcode-gui-content', $dialog ).length > 0 ) {
			shortcodeContent = $( '.js-toolset-shortcode-gui-content', $dialog ).val();
			shortcodeToInsert += shortcodeContent;
			shortcodeToInsert += '[/' + shortcodeName + ']';
		}
		
		/**
		 * Filter the crafted shortcode string, using two different filters:
		 * - toolset-filter-shortcode-gui-crafted-shortcode
		 * - toolset-filter-shortcode-gui-{shortcodeName}-crafted-shortcode
		 *
		 * @param shortcodeToInsert string
		 * @param object
		 *     { 
		 *         shortcode:     shortcodeName, 
		 *         attributes:    shortcodeAttributeValues
		 *         rawAttributes: shortcodeRawAttributeValues
		 *     }
		 *
		 * @since m2m
		 */
		shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-crafted-shortcode', shortcodeToInsert, { shortcode: shortcodeName, attributs: shortcodeAttributeValues, rawAttributes: shortcodeRawAttributeValues } );
		shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-' + shortcodeName + '-crafted-shortcode', shortcodeToInsert, { shortcode: shortcodeName, attributes: shortcodeAttributeValues, rawAttributes: shortcodeRawAttributeValues } );
		
		return shortcodeToInsert;
		
	};
	
	/**
	 * Resolve toolsetCombo attribute values, getting the actual value from the combo attribute.
	 *
	 * @param shortcodeAttributeValues object
	 * @param data                     object
	 *
	 * @return object
	 *
	 * @since 2.5.4
	 */
	self.resolveToolsetComboValues = function( shortcodeAttributeValues, data ) {
		var resolvedAttributes = {};
		_.each( shortcodeAttributeValues, function( value, key ) {
			if ( 'toolsetCombo' == value ) {
				resolvedAttributes[ key ] = data.rawAttributes[ 'toolsetCombo:' + key ];
			} else if ( /^toolsetCombo/.test( key ) ) {
				resolvedAttributes[ key ] = false;
			} else {
				resolvedAttributes[ key ] = value;
			}
		});
		return resolvedAttributes;
	};
	
	/**
	 * Do the final action upon the crafted shortcode.
	 *
	 * Valid actions are 'skip', 'create', 'append', 'edit', 'save', 'insert'.
	 *
	 * @param shortcode string
	 *
	 * @since 2.5.4
	 */
	self.doAction = function( shortcode ) {
		
		var action = self.action;
		
		/**
		 * Custom action executed before performing the GUI action.
		 *
		 * @param string shortcode   The shortcode to action upon
		 * @param string self.action The action to execute
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.doAction( 'toolset-action-before-do-shortcode-gui-action', shortcode, action );
		
		/**
		 * Final filter over the shortcode string before executing the GUI action.
		 *
		 * @param shortcode string
		 * @param action    string
		 *
		 * @since 2.5.4
		 */
		shortcode = Toolset.hooks.applyFilters( 'toolset-filter-before-do-shortcode-gui-action', shortcode, action );
		
		switch ( action ) {
			case 'skip':
			case 'create':
			case 'append':
			case 'edit':
			case 'save':
				/**
				 * Do the GUI skip|create|append|edit|save action, if there is a callback for that.
				 *
				 * @param shortcode string
				 *
				 * @since 2.5.4
				 */
				Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action-' + action, shortcode );
				break;
			case 'insert':
			default:
				/**
				 * Do the GUI insert action.
				 *
				 * @param shortcode string
				 *
				 * @since 2.5.4
				 */
				Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action-insert', shortcode );
				break;
		}
		
		/**
		 * Custom action executed after performing the GUI action.
		 *
		 * @param string shortcode   The shortcode to action upon
		 * @param string self.action The action executed
		 *
		 * @since 2.5.4
		 */
		Toolset.hooks.doAction( 'toolset-action-after-do-shortcode-gui-action', shortcode, action );
		
		// Set the shortcodes GUI action to its default 'insert'
		self.setShortcodeGuiAction( 'insert' );
	};
	
	/**
	 * Do the GUI create action. Opens the target dialog and inserts the shortcode into it.
	 *
	 * @param shortcode string
	 *
	 * @since 2.5.4
	 */
	self.doActionCreate = function( shortcode ) {
		self.dialogs.target
			.data( 'shortcode', shortcode )
			.dialog( 'open' ).dialog({
				maxHeight:	self.calculateDialogMaxHeight(),
				maxWidth:	self.calculateDialogMaxWidth(),
				position:	{
					my:			"center top+50",
					at:			"center top",
					of:			window,
					collision:	"none"
				}
		});
	};
	
	/**
	 * Do the GUI insert action.
	 *
	 * @param shortcode string
	 *
	 * @uses icl_editor
	 *
	 * @since 2.5.4
	 */
	self.doActionInsert = function( shortcode ) {
		window.icl_editor.insert( shortcode );
	};
	
	/**
	 * Do the GUI save action. Base64-encode the shortcode and adds it as the value of an option in the Views loop wizard dialog.
	 *
	 * @param shortcode string
	 *
	 * @since m2m
	 */
	self.doActionSave = function( shortcode ) {
		$( '.js-wpv-loop-wizard-save-shortcode-ui-active' )
			.find( 'option:selected' )
				.val( Base64.encode( shortcode ) );
		$( '.js-wpv-loop-wizard-save-shortcode-ui-active' )
			.removeClass( 'js-wpv-loop-wizard-save-shortcode-ui-active' );
	};
	
	/**
	 * Shortcodes GUI pointer management.
	 *
	 * @since m2m
	 */
	$( document ).on( 'click', '.js-wp-toolset-shortcode-pointer-trigger', function() {
		var $tooltipTriggerer = $( this ),
			tooltipContent = $tooltipTriggerer.closest( 'li' ).find( '.js-wp-toolset-shortcode-pointer-content' ).html();
			edge = ( $( 'html[dir="rtl"]' ).length > 0 ) ? 'top' : 'top';

		// hide this pointer if other pointer is opened.
		$( '.wp-toolset-pointer' ).fadeOut( 100 );

		$tooltipTriggerer.pointer({
			pointerClass: 'wp-toolset-pointer wp-toolset-shortcode-pointer js-wp-toolset-shortcode-pointer',
			pointerWidth: 400,
			content: tooltipContent,
			position: {
				edge: edge,
				align: 'center',
				offset: '15 0'
			},
			buttons: function( event, t ) {
				var button_close = $( '<button class="button button-primary-toolset alignright">' + 'Close' + '</button>' );
				button_close.bind( 'click.pointer', function( e ) {
					e.preventDefault();
					t.element.pointer( 'close' );
				});
				return button_close;
			}
		}).pointer( 'open' );
		$( '.js-wp-toolset-shortcode-pointer:not(.js-wp-toolset-shortcode-pointer-indexed)' )
			.addClass( '.js-wp-toolset-shortcode-pointer-zindexed' )
			.css( 'z-index', '10000000' );
	});

    self.secureShortcodeFromSanitizationIfNeeded = function( shortcode_data ) {
        var shortcode_string;
        if ( typeof( shortcode_data ) === 'object' ) {
            shortcode_string = shortcode_data.shortcode;
        } else {
            shortcode_string = shortcode_data;
        }

        /*
         * In Views 2.5.0, we introduced support for shortcodes using placeholders instead of bracket.
         * The selected placeholder for the left bracket "[" was chosen to be the "{!{" and the selected
		 * placeholder for the right bracket "]" was chosen to be the "}!}". This was done to allow the use
         * of Toolset shortcodes inside the various page builder modules fields.
         * Here, we are offering the shortcodes created by the Toolset Shortcodes admin bar menu, in their
         * new format, with the brackets replaced with placeholders but only on the Content Template edit page.
         * where the Visual Composer builder is used.
         * For all the other needed pages (native post editor with each page builder enabled), this is handled
         * elsewhere.
         */
        if (
            (
                // In the Content Template edit page with WPBakery Page Builder (former Visual Composer) enabled.
                'toolset_page_ct-editor' === window.pagenow
                && 'undefined' !== typeof window.vc
            )
            || (
                $.inArray( window.adminpage, [ 'post-php', 'post-new-php' ] ) !== -1
                && (
                    (
                        // Divi builder is enabled.
                        'undefined' !== typeof window.et_builder
                        && $( '#et_pb_toggle_builder.et_pb_builder_is_used' ).length > 0
                    )
                    || (
                        // WPBakery Page Builder (former Visual Composer) is enabled.
                        'undefined' !== typeof window.vc
                        && $( '.composer-switch.vc_backend-status' ).length > 0
                    )
                    || (
                        // Frontend WPBakery Page Builder (former Visual Composer) is enabled.
                        'undefined' !== typeof window.vc
                        && (
                            $( '#vc_navbar.vc_navgar-frontend' ).length > 0
                            // Adding a second condition to catch the case that they will fix the typo in the class name.
                            || $( '#vc_navbar.vc_navbar-frontend' ).length > 0
                        )
                    )
                    || (
                        // Fusion Builder is enabled.
                        'undefined' !== typeof window.FusionPageBuilder
                        && $( '#fusion_toggle_builder.fusion_builder_is_active' ).length > 0
                    )
                )
            )
        ) {
            shortcode_string = shortcode_string.replace( /\[/g, '{!{' ).replace( /]/g, '}!}' ).replace( /"/g, '\'' );
        }

        if ( typeof( shortcode_data ) === 'object' ) {
            shortcode_data.shortcode = shortcode_string;
        } else {
            shortcode_data = shortcode_string;
        }

        return shortcode_data;
    }
	
	/**
	 * Init main method:
	 * - Init templates
	 * - Init dialogs.
	 * - Init API hooks.
	 *
	 * @since 2.5.4
	 */
	self.init = function() {
		
		self.initTemplates()
			.initDialogs()
			.initHooks()
		
	};

	self.init();
	
}

jQuery( document ).ready( function( $ ) {
	Toolset.shortcodeGUI = new Toolset.shortcodeManager( $ );
});