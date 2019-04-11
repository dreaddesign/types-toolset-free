<?php

class Toolset_Filesystem_Directory {

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

		if( ! is_dir( $path ) || ! is_readable( $path ) )
			return false;

		$this->path = $path;

		return true;
	}

	/**
	 * @throws Toolset_Filesystem_Exception
	 */
	private function open_dir() {
		if( ! $this->path )
			throw new Toolset_Filesystem_Exception( 'No directory is selected.' );

		$this->handle = opendir( $this->path );
	}

	/**
	 * Close file
	 *
	 * @return bool
	 */
	private function close_dir() {
		// already closed
		if( ! $this->handle )
			return true;

		closedir( $this->handle );
		$this->handle = false;
	}

	/**
	 * @param $filename
	 * @param bool $recursive
	 *
	 * @return array|bool|DirectoryIterator
	 * @throws Toolset_Filesystem_Exception
	 */
	public function find( $filename, $recursive = false ) {

		// skip if path is not set
		if( ! $this->path )
			throw new Toolset_Filesystem_Exception( 'No directory is selected.' );

		// if using recursive flag
		if( $recursive )
			return $this->find_recursive( $filename );

		// search in directory
		foreach( new DirectoryIterator( $this->path ) as $file ) {
			// check current is not a directory and match with filename
			if( ! is_dir( $file->getRealPath() ) && $file->getFilename() == $filename )
				return $file;
		}

		// file not found in root directory
		return false;
	}

	/**
	 * @param $filename
	 *
	 * @return array|bool
	 * @throws Toolset_Filesystem_Exception
	 */
	public function find_recursive( $filename ) {

		// skip if path is not set
		if( ! $this->path )
			throw new Toolset_Filesystem_Exception( 'No directory is selected.' );

		// use $this->path as directory
		$directory = new RecursiveDirectoryIterator( $this->path );

		$files_found = array();

		// search recursive in directory
		foreach ( new RecursiveIteratorIterator( $directory ) as $file) {
			if( ! is_dir( $file->getRealPath() ) && $file->getFilename() == $filename )
				$files_found[] = $file;
		}

		if( empty( $files_found ) )
			return false;

		return $files_found;
	}
}