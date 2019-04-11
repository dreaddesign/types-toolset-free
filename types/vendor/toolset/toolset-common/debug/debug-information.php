<?php

/**
* Generator of debug unformation for the Toolset family.
*
* Load this on demand on your debug page, using any of the following methods:
*
* $toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
* $toolset_common_sections = array(
* 	'toolset_debug'
* );
* $toolset_common_bootstrap->load_sections( $toolset_common_sections );
*
* $toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
* $toolset_common_bootstrap->register_debug();
*
 * @since unknown
 * @deprecated Please refer to the Toolset Troubleshooting page, which includes this information and where you can add
 *     your plugin's troubleshooting elements. This file is deprecated and should not be included anywhere.   
 * 
*/

include_once dirname(__FILE__) . '/functions_debug_information.php';
$debug_information = new ICL_Debug_Information();
$debug_data = $debug_information->get_debug_info();
?>
<div class="wrap">
	<h1><?php _e('Toolset Debug Information', 'wpv-views');?></h1>
	<div class="inside">
		<p><?php _e( 'The information below allows our support team to see the versions of WordPress, plugins and themes installed in your site.', 'wpv-views' ) ?></p>
		<p><?php _e( 'Please, provide this information if requested in our support forum.', 'wpv-views' ) ?></p>
		<p><?php _e( 'No passwords or any other confidential information is included.', 'wpv-views' ) ?></p>
		<textarea style="font-size:10px;width:100%;height:250px;" rows="26" readonly="readonly"><?php echo esc_html( $debug_information->do_json_encode( $debug_data ) );?></textarea>
	</div>
</div>
