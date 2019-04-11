<?php

if( class_exists( 'Toolset_Filesystem_File', false ) )
	return;

class Toolset_Filesystem_File {

	private $handle = false;
	private $path = false;

	/**
	 * Open file by path and stores it on success.
	 *
	 * @param $path
	 *
	 * @return bool
	 */
	public function open( $path ) {

		if( ! file_exists( $path ) || ! is_readable( $path ) ) {
			$this->path = false;
			return false;
		}

		$this->path = $path;
		return true;
	}

	/**
	 * @throws Toolset_Filesystem_Exception
	 */
	private function open_file() {
		if( ! $this->path )
			throw new Toolset_Filesystem_Exception( 'No file selected.' );

		$this->handle = fopen( $this->path, 'r' );
	}

	/**
	 * Close file
	 *
	 * @return bool
	 */
	private function close_file() {
		// already closed
		if( ! $this->handle )
			return true;

		fclose( $this->handle );
		$this->handle = false;
	}

	/**
	 * Search for string
	 *
	 * @param $search
	 * @param string $return
	 *
	 * @return bool
	 */
	public function search( $search, $return = 'bool' ) {
		$this->open_file();

		if( ! is_array( $search ) )
			$search = array( $search );

		while( ( $line = fgets( $this->handle ) ) !== false) {
			foreach( $search as $needle ) {
				if( strpos( $line , $needle ) !== false ) {
					switch( $return ) {
						case 'bool':
							$this->close_file();
							return true;
					}
				}
			}
		}

		$this->close_file();
		return false;
	}
}