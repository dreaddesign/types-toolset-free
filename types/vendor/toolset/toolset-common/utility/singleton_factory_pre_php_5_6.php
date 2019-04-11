<?php
/*
 * !! THIS FILE IS ONLY LOADED WHEN PHP VERSION IS BELOW 5.6 !!
 * Otherwise see: ./singleton_factory.php
 */

/**
 *
 * Class Toolset_Singleton_Factory
 *
 * The best would be not having Singletons at all, but sometimes it's necessary, especially in WP.
 * Having get_instance() methods all over the place, is not only ugly, it also makes testing awful.
 * Instead of making a class a forced singleton for every use, you can simply make it singleton when you really need to.
 *
 * INSTEAD OF DOING THIS:
 * -------
 *
 * class My_Fantastic_Class {
 *    private function __construct() {}
 *
 *    public function get_instance( $dependency_1, $dependency_2 ) {
 *
 *    }
 * }
 *
 * $singleton = My_Fantastic_Class::get_instance( new My_Dependency_1(), new My_Dependency_2() );
 *
 * -------
 * YOU SHOULD DO THIS:
 * -------
 *
 * class My_Fantastic_Class {
 *    public function __construct( $dependency_1, $dependency_2 ){
 *
 *    }
 * }
 *
 * /* @var My_Fantastic_Class $singleton *\/
 * $singleton = Toolset_Singleton_Factory::get( 'My_Fantastic_Class', new My_Dependency_1(), new My_Dependency_2() );
 *
 * -------
 *
 * I added "/* @var My_Fantastic_Class $singleton *\/" above to let the IDE know that $singleton
 * is a object of My_Fantastic_Class object. This way IDE's autocomplete still works.
 *
 *
 * @since 2.6.3
 */
class Toolset_Singleton_Factory {

	/**
	 * @var array
	 */
	public static $instances = array();

	/**
	 * @param $class
	 *
	 * @return mixed|false Object of $class or false if $class not exists
	 */
	public static function get( $class ) {
		if ( isset( self::$instances[ $class ] ) ) {
			// singleton already exists
			return self::$instances[ $class ];
		}

		if ( ! class_exists( $class ) ) {
			// class does not exist
			return false;
		}

		// get all arguments
		$arguments = func_get_args();

		// drop $class argument
		array_shift( $arguments );

		if ( ! empty( $arguments ) ) {
			// use ReflectionClass to create instance of $class with $arguments
			$reflect                   = new ReflectionClass( $class );
			self::$instances[ $class ] = $reflect->newInstanceArgs( $arguments );
		} else {
			// no arguments, no extra magic needed
			self::$instances[ $class ] = new $class();
		}


		return self::$instances[ $class ];
	}
}