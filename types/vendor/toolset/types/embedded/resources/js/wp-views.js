/*
 * Toolset Views plugin.
 *
 * Loaded on Views or Views Template edit screens.
 */
var typesWPViews = (function(window, $){

    function openFrameForWizard( fieldID, metaType, postID, shortcode ) {
        var colorboxWidth = 750 + 'px';

        if ( !( jQuery.browser.msie && parseInt(jQuery.browser.version) < 9 ) ) {
            var documentWidth = jQuery(document).width();
            if ( documentWidth < 750 ) {
                colorboxWidth = 600 + 'px';
            }
        }

        var url = ajaxurl+'?action=wpcf_ajax&wpcf_action=editor_callback'
        + '&_typesnonce=' + types.wpnonce
        + '&callback=views_wizard'
        + '&field_id=' + fieldID
        + '&field_type=' + metaType
        + '&post_id=' + postID
        + '&shortcode=' + shortcode;

        jQuery.colorbox({
            href: url,
            iframe: true,
            inline : false,
            width: colorboxWidth,
            opacity: 0.7,
            closeButton: false
        });
    }
	
	/**
	 * Open the Types colorbox instance to insert a field, with a specific callback name.
	 *
	 * @param string fieldID
	 * @param string metaType
	 * @param int postID
	 * @param string callback
	 *
	 * @since 2.2.9
	 */
	
	function openFrameForCloseAndTrigger( fieldID, metaType, postID, callback ) {
        var colorboxWidth = 750 + 'px';

        if ( !( jQuery.browser.msie && parseInt(jQuery.browser.version) < 9 ) ) {
            var documentWidth = jQuery(document).width();
            if ( documentWidth < 750 ) {
                colorboxWidth = 600 + 'px';
            }
        }

        var url = ajaxurl+'?action=wpcf_ajax&wpcf_action=editor_callback'
        + '&_typesnonce=' + types.wpnonce
        + '&callback=' + callback
        + '&field_id=' + fieldID
        + '&field_type=' + metaType
        + '&post_id=' + postID;

        jQuery.colorbox({
            href: url,
            iframe: true,
            inline : false,
            width: colorboxWidth,
            opacity: 0.7,
            closeButton: false
        });
    }
	
	/**
	 * Close the colorbox dialog to insert a Types field shortcode, and trigger a custom event.
	 *
	 * This is used by Views on the admin bar shortcodes generator, and also on the generic inputs shortcodes appender.
	 *
	 * @param string shortcode
	 *
	 * @since 2.2.9
	 */
	
	function closeFrameAndTrigger( shortcode ) {
		window.parent.jQuery.colorbox.close();
		$( document ).trigger( 'js_types_shortcode_created', shortcode );
	}

    return {
        wizardEditShortcode: function( fieldID, metaType, postID, shortcode ) {
            openFrameForWizard( fieldID, metaType, postID, shortcode );
        },
        wizardSendShortcode: function( shortcode ) {
            window.wpv_restore_wizard_popup(shortcode);
        },
		interceptEditShortcode: function( fieldID, metaType, postID, callback ) {
			openFrameForCloseAndTrigger( fieldID, metaType, postID, callback );
		},
		interceptCreateShortcode: function( shortcode ) {
			closeFrameAndTrigger( shortcode );
		},
        wizardCancel: function() {
            window.wpv_cancel_wizard_popup();
        }
    };
})(window, jQuery, undefined);