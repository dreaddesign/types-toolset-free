<?php

/**
 * Wrapper for a mockable access to file functions.
 *
 * The principle is similar to Toolset_Constants.
 *
 * Note: Use this *only* if you need it in unit tests!
 *
 * @since 2.5.7
 */
class Toolset_Files {

	/**
	 * is_file()
	 *
	 * @link http://php.net/manual/en/function.is-file.php
	 * @param string $path
	 * @return bool
	 */
	public function is_file( $path ) {
		return is_file( $path );
	}


	/**
	 * Extract provided context variable, include the file and return the output.
	 *
	 * @param string $filename
	 * @param array $context_vars
	 *
	 * @return string
	 */
	public function get_include_file_output( $filename, $context_vars = array() ) {
		ob_start();
		extract( $context_vars );
		include $filename;
		$output = ob_get_clean();
		return $output;
	}


	/**
	 * file_get_contents()
	 *
	 * @link http://php.net/manual/en/function.file-get-contents.php
	 * @param string $filename
	 * @return bool|string
	 */
	public function file_get_contents( $filename ) {
		return file_get_contents( $filename );
	}

}