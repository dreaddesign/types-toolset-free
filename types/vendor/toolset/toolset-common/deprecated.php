<?php

/**
* #############################
* Shared code that is not or should not be used anymore
* Note that using it produces _doing_it_wrong() debug notices
* #############################
*/

/**
* Dismiss message.
* 
* @param type $message_id
* @param string $message
* @param type $class 
*/
 
if ( ! function_exists( 'wpv_add_dismiss_message' ) ) {
	function wpv_add_dismiss_message( $message_id, $message, $clear_dismissed = false, $class = 'updated' ) {
		$doing_it_wrong_message = __( 'wpv_add_dismiss_message is deprecated and should not be used. you will need to implement your own method to manage admin messages.', 'wpv-views' );
		_doing_it_wrong( 'wpv_add_dismiss_message', $message, '1.9' );
		$dismissed_messages = get_option( 'wpv-dismissed-messages', array() );
		if ( $clear_dismissed ) {
			if ( isset( $dismissed_messages[$message_id] ) ) {
				unset( $dismissed_messages[$message_id] );
				update_option( 'wpv-dismissed-messages', $dismissed_messages );
			}
		}
		if ( !array_key_exists( $message_id, $dismissed_messages ) ) {
			$message = $message . '<div style="float:right; margin:-15px 0 0 15px;"><a onclick="jQuery(this).parent().parent().fadeOut();jQuery.get(\''
					. admin_url( 'admin-ajax.php?action=wpv_dismiss_message&amp;message_id='
							. $message_id . '&amp;_wpnonce='
							. wp_create_nonce( 'dismiss_message' ) ) . '\');return false;"'
					. 'class="button-secondary" href="javascript:void(0);">'
					. __( "Don't show this message again", 'wpv-views' ) . '</a></div>';
			wpv_admin_message_store( $message_id, $message, false );
		}
	}
}

if ( ! function_exists( 'wpv_dismiss_message_ajax' ) ) {
	add_action( 'wp_ajax_wpv_dismiss_message', 'wpv_dismiss_message_ajax' );
	function wpv_dismiss_message_ajax() {
		// Note that this is used on the Views legacy theme import
		$doing_it_wrong_message = __( 'wpv_dismiss_message_ajax is deprecated and should not be used. you will need to implement your own method to manage admin messages.', 'wpv-views' );
		_doing_it_wrong( 'wpv_add_dismiss_message', $message, '1.9' );
		if ( 
			isset( $_GET['message_id'] ) 
			&& isset( $_GET['_wpnonce'] )
			&& wp_verify_nonce( $_GET['_wpnonce'], 'dismiss_message' ) 
		) {
			$dismissed_messages = get_option( 'wpv-dismissed-messages', array() );
			$dismissed_image_val = isset( $_GET['timestamp'] ) ? sanitize_text_field( $_GET['timestamp'] ) : 1;
			$dismissed_messages[strval( $_GET['message_id'] )] = $dismissed_image_val;
			update_option( 'wpv-dismissed-messages', $dismissed_messages );
		}
		die( 'ajax' );
	}
}