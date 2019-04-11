<?php

if ( ! defined( 'WPT_WPLOGGER' ) ) {
    define( 'WPT_WPLOGGER', true );
}

/**
* Modified standalone version of the WPLogger class for internal purposes.
* 
* Wordpress Logger 0.3
* http://www.turingtarpit.com/2009/05/wordpress-logger-a-plugin-to-display-php-log-messages-in-safari-and-firefox/
* Displays log messages in the browser console in Safari, Firefox and Opera.
* Useful for plugin and theme developers to debug PHP code.
* 
* Chandima Cumaranatunge
* http://www.turingtarpit.com
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
* 
* Code to force the plugin to load before others adapted from the
* WordPress FirePHP plugin developed by Ivan Weiller.
* http://inchoo.net/wordpress/wordpress-firephp-plugin/
* 
* Requirements:
*	* PHP 5+
*	* Wordpress 2.5+
*	* JQuery 1.2.6
*	* Firefox browser with firePHP plugin activated OR
*	  Safari browser with Error Console turned on
* 
* Usage:
* 	toolset_wplog( mixed php_expression [, const message_type] )
*	message_type can be: error, warn, info, debug.
*	
* Output ( from the browser console ):
*	[Information: from line xxx in file somefile.php] array (
*	  0 => 'wplogger/wplogger.php',
*	  1 => '12seconds-widget/12seconds-widget.php',
*	  2 => 'get-the-image/get-the-image.php',
*	)
*/

if ( ! function_exists( 'toolset_wplog' ) ) {

	function toolset_wplog( $message = '', $msgType = null, $file = '', $function = '', $line = '' ) {
		$toolset_wplog = Toolset_WPLogger::get_instance();
		$toolset_wplog->log( $message, $msgType, $file, $function, $line );
	}

}

if ( ! class_exists( 'Toolset_WPLogger', false ) ) {
	
	class Toolset_WPLogger {

		private static $instance;
		/**
		 * String holding the buffered output.
		 */
		var $_buffer = array();
		
		/**
		 * The default priority to use when logging an event.
		 */
		var $_defaultMsgType = 'info';
		
		/**
		 * Long descriptions of debug message types
		 */
		var $_msgTypeLong = array(
			'error'	=> 'error',
			'warn'	=> 'warn',
			'info'	=> 'info',
			'debug'	=> 'debug'
		);

		var $_msgStatusPriority = array(
			'error'	=> '50',
			'warn'	=> '40',
			'info'	=> '30',
			'debug'	=> '20'
		);
		
		public function __construct() {
			add_action( 'wp_footer',	array( $this, 'flushLogMessages' ) );
			add_action('admin_footer',	array( $this, 'flushLogMessages' ) );
		}
		
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new Toolset_WPLogger();
			}

			return self::$instance;
		}
		
		/**
		 * Writes JavaScript to flush all pending ("buffered") data to
		 * the Firefox or Safari console.
		 *
		 * @notes  requires JQuery 1.2.6 for browser detection.
		 *		   browser detection is deprecated in JQuery 1.3
		 * @see    http://docs.jquery.com/Utilities/jQuery.browser
		 */
		function flushLogMessages() {
			if ( count( $this->_buffer ) ) {           
				print '<script type="text/javascript">'."\n";
				print 'var $j=jQuery.noConflict();'."\n";
				print 'if (($j.browser.safari || $j.browser.webkit) && window.console) {'."\n";
				foreach ( $this->_buffer as $line ) {
					printf( 'window.console.%s("%s");', $line[0], $line[1] );
					print "\n";
				}
				print '} else if ($j.browser.mozilla && (\'console\' in window)) {'."\n";
				foreach ( $this->_buffer as $line ) {
					printf( 'console.%s("%s");', $line[0], $line[1] );
					print "\n";
				}
				print '} else if ($j.browser.opera && window.opera && opera.postError) {'."\n";
				foreach ( $this->_buffer as $line ) {
					printf( 'opera.postError("%s");', $line[1] );
					print "\n";
				}
				print "}\n";
				print "</script>\n";
			}
			$this->_buffer = array();
		}
		
		/**
		 * Buffers $message to be flushed to the Firebug or Safari console.
		 * 
		 * Adapted from the PEAR_Log library
		 * 
		 * @return boolean true
		 * @param mixed  $message String or object containing the message to log.
		 * @param const $msgType[optional] type of message. Valid values are:
		 * 					WPLOG_ERR. WPLOG_WARNING, WPLOG_INFO, WPLOG_DEBUG
		 */
		function log( $message, $msgType = null, $file = '', $function = '', $line = '' ) {
		
			/* If a log message type hasn't been specified, use the default value. */
			if ( $msgType === null ) {
				$msgType = $this->_defaultMsgType;
			}
			
			// verify the status type and output only priority messages (based on wp-config setup)
			if ( ! $this->isMsgVisible( $msgType ) ) {
				return false;
			}
			
			/* Extract the string representation of the message. */
			$message = $this->_extractMessage( $message );
			
			/* normalize line breaks */
			$message = str_replace( "\r\n", "\n", $message );
			
			/* escape line breaks */
			$message = str_replace( "\n", "\\n\\\n", $message );
			
			/* escape quotes */
			$message = str_replace( '"', '\\"', $message );
			
			/* Build the string containing the complete log line. */
			$line = sprintf(
				'[%1$s %2$s:%3$s, %4$s] %5$s', 
				$this->_msgTypeLong[ $msgType ],
				addslashes( $file ),
				$line,
				$function,
				$message 
			);
			
			// buffer method and line
			$this->_buffer[] = array( $msgType, $line );
			
			return true;
		}
		
		/**
		 * Returns the string representation of the message data (from the PEAR_Log library).
		 *
		 * If $message is an object, _extractMessage() will attempt to extract
		 * the message text using a known method (such as a PEAR_Error object's
		 * getMessage() method).  If a known method, cannot be found, the
		 * serialized representation of the object will be returned.
		 *
		 * If the message data is already a string, it will be returned unchanged.
		 * 
		 * Adapted from the PEAR_Log library
		 *
		 * @param  mixed $message   The original message data.  This may be a
		 *                          string or any object.
		 *
		 * @return string           The string representation of the message.
		 * 
		 */
		function _extractMessage( $message ) {
			/*
			 * If we've been given an object, attempt to extract the message using
			 * a known method.  If we can't find such a method, default to the
			 * "human-readable" version of the object.
			 *
			 * We also use the human-readable format for arrays.
			 */
			if ( is_object( $message ) ) {
				if ( method_exists( $message, 'getmessage' ) ) {
					$message = $message->getMessage();
				} else if ( method_exists( $message, 'tostring' ) ) {
					$message = $message->toString();
				} else if ( method_exists( $message, '__tostring' ) ) {
					if ( version_compare( PHP_VERSION, '5.0.0', 'ge' ) ) {
						$message = (string) $message;
					} else {
						$message = $message->__toString();
					}
				} else {
					$message = var_export( $message, true );
				}
			} else if ( is_array( $message ) ) {
				if ( isset( $message['message'] ) ) {
					if ( is_scalar( $message['message'] ) ) {
						$message = $message['message'];
					} else {
						$message = var_export( $message['message'], true );
					}
				} else {
					$message = var_export( $message, true );
				}
			} else if ( is_bool( $message ) || $message === NULL ) {
				$message = var_export( $message, true );
			}
			
			/* Otherwise, we assume the message is a string. */
			return $message;
		}
		
		/**
		 * 
		 * Is the message for the logger visible, i.e. is the status approved for output in the config
		 * 
		 * @param status_type $msg_status the status level
		 */
		function isMsgVisible( $msg_status ) {
			// verify that status for logging is set
			if ( ! defined( 'TOOLSET_LOGGING_STATUS' ) ) {
				return false;
			}
			// use default off status if status not in the list
			if ( 
				! in_array( TOOLSET_LOGGING_STATUS, $this->_msgTypeLong )
				|| ! in_array( $msg_status, $this->_msgTypeLong ) 
			) {
				return false;
			}
			// verify priorities
			if ( $this->_msgStatusPriority[$msg_status] >=  $this->_msgStatusPriority[ TOOLSET_LOGGING_STATUS ] ) {
				return true;
			}
			return false;
		}
	}

}

?>
