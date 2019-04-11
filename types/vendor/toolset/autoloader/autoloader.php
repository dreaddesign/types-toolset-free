<?php

/**
 * Autoloader for Types classes.
 *
 * See:
 * @link https://git.onthegosystems.com/toolset/layouts/wikis/layouts-theme-integration#wpddl_theme_integration_autoloader
 *
 * @since 2.0
 */
final class Toolset_Autoloader {

	private static $instance;

	/**
	 * @var array Multidimensional associative array:
	 *
	 * array(
	 *     class prefix => array(
	 *         path => array( ... options ... ),
	 *         ...
	 *     ),
	 *     ...
	 * )
	 *
	 */
	private $paths_by_prefixes = array();


	/**
	 * For production version of Types we use a class map
	 * you can disable using the classmap by simply deleting /autoload_classmap.php
	 *
	 * Note: Not using the classmap will lower the performance significant
	 *
	 * @var bool|array
	 * @since 2.1
	 */
	private $classmap = false;


	private function __construct() {
		spl_autoload_register( array( $this, 'autoload' ) );

		if( file_exists( TYPES_ABSPATH . '/autoload_classmap.php' ) ) {
			$this->classmap = include( TYPES_ABSPATH . '/autoload_classmap.php' );
		}
	}

	public static function get_instance() {
		if( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Add base path to search for class files.
	 *
	 * @param string $prefix Class prefix
	 * @param string $path Path for given class prefix
	 * @param array [$options] Additional options. Currently supported are:
	 *     - file_prefix: Value that will be prepended before each file name that will be checked.
	 * @return bool True if path was added
	 *
	 * @since 2.0
	 */
	public function add_path( $prefix, $path, $options = array() ) {

		if( ! array_key_exists( $prefix, $this->paths_by_prefixes ) ) {
			$this->paths_by_prefixes[ $prefix ] = array();

			// We assume that most specific (longest) prefixes will have higher probability of a match.
			// This is useful when one prefix is substring of another.
			krsort( $this->paths_by_prefixes );
		}

		// Skip if already set
		if( in_array( $path, array_keys( $this->paths_by_prefixes[ $prefix ] ) ) ) {
			return true;
		}

		// Abort if the path is not readable
		if( ! is_readable( $path ) ) {
			return false;
		}

		if( !is_array( $options ) ) {
			$options = array();
		}

		$this->paths_by_prefixes[ $prefix ][ $path ] = $options;
		return true;
	}


	/**
	 * Add multiple base paths.
	 *
	 * @param $prefix
	 *
	 * @param array $paths
	 * @param array $options
	 *
	 * @since 2.0
	 */
	public function add_paths( $prefix, $paths, $options = array() ) {
		// run this->addPath for each value
		foreach( $paths as $path ) {
			$this->add_path( $prefix, $path, $options );
		}
	}
	

	public function autoload( $class ) {
		// use class map if defined
		if( $this->classmap ) {
			if( isset( $this->classmap[$class] ) && file_exists( $this->classmap[$class] ) ) {
				require_once( $this->classmap[$class] );
				return true;
			}

			return false;
		}

		// no class map
		foreach( $this->paths_by_prefixes as $prefix => $paths ) {

			// Will be equal to $class if no replacement happens.
			$class_without_prefix = preg_replace( '#^'.$prefix.'_#', '', $class );

			if( $class != $class_without_prefix ) {

				$result = $this->try_autoload_without_prefix( $class, $class_without_prefix, $paths );

				// false means we should try with other prefixes
				if( false !== $result ) {
					return $result;
				}
			}
		}

		return false;
	}


	/**
	 * Try to load class after matching it's prefix.
	 *
	 * @param string $full_class_name Full name of the class.
	 * @param string $class_name_without_prefix Name of the class without the registered prefix.
	 * @param array $paths Path definitions for current prefix.
	 *
	 * @return bool|mixed include_once() result or false if the file was not found.
	 *
	 * @since 2.0
	 */
	private function try_autoload_without_prefix( $full_class_name, $class_name_without_prefix, $paths ) {

		// explode class by _
		$explode_class = explode( '_' , $class_name_without_prefix );

		// get class filename
		$class_filename = array_pop( $explode_class );
		$class_filename = strtolower( $class_filename ) . '.php';

		// get class path
		$class_path = '';
		foreach( $explode_class as $path ) {
			$class_path .= strtolower( $path ) . '/';
		}

		// check for file in path
		foreach( $paths as $path => $options ) {

			$file_prefix = wpcf_getarr( $options, 'file_prefix', '' );
			$next_filename = $class_path . $file_prefix . $class_filename;

			while( true ) {
				$candidate_filename = $next_filename;

				$candidate_path = $path . '/' . $candidate_filename;

				$result = $this->try_load_file( $candidate_path, $full_class_name );
				if( false !== $result ) {
					return $result;
				}

				// Replace the last slash by underscore.
				// This allows to use underscores in class filename instead of subfolders
				$next_filename = preg_replace( '/(\/(?!.*\/))/', '_', $candidate_filename );

				// If there was no change, we have tried all possibilities for this filename.
				if( $next_filename == $candidate_filename ) {
					break;
				}

			}

		}

		return false;
	}


	/**
	 * Try loading a file and check if given class exists afterwards.
	 *
	 * @param string $full_path Absolute path to the file.
	 * @param string $full_class_name Full class name to be checked.
	 *
	 * @return bool|mixed include_once() result or false if the class couldn't be loaded. It may give false negatives.
	 */
	private function try_load_file( $full_path, $full_class_name ) {
		if( is_file( $full_path ) ) {
			/** @noinspection PhpIncludeInspection */
			$result = include_once( $full_path ) ;

			// Do not stop trying if we load the file but it doesn't contain the requested class.
			if( class_exists( $full_class_name ) ) {
				return $result;
			}
		}

		return false;
	}
}