<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autoloads Twig classes.
 *
 * This is a modified version of Twig_Autoloader that survives without producing a fatal error even if someone else
 * includes Twig_Autoloader recklessly, without checking if !class_exists(). When the register() method is being
 * called, it checks all registered autoloaders. If the native Twig_Autoloader is already there, this class resigns
 * and doesn't complete it's own registration.
 *
 * This will, however, work only if it happens late enough. In Types we assume that it is ok to do this during 'init'.
 * The one known issue is with older WPML versions that register Twig right when the plugin is loaded.
 *
 * Note: Twig_Autoloader is marked as deprecated, however we can't easily use the proposed composer autoloader, since
 * that breaks the PHP 5.2 compatibility.
 *
 * The original author of this class:
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @since 2.0
 */
class Types_Twig_Autoloader
{

	/**
	 * Registers Types_Twig_Autoloader as an SPL autoloader if Twig_Autoloader isn't already registered.
	 *
	 * @param bool $prepend Whether to prepend the autoloader or not.
	 */
	public static function register($prepend = false)
	{
		$autoloaders = spl_autoload_functions();
		foreach( $autoloaders as $autoloader ) {

			// Resign if we detect Twig_Autoloader
			if( is_array( $autoloader )
				&& 2 == count( $autoloader )
				&& is_string( $autoloader[0] )
				&& 'Twig_Autoloader' == $autoloader[0] 
				&& is_string( $autoloader[1] )
				&& 'autoload' == $autoloader[1]
			) {
				return;
			}
		}

		if (PHP_VERSION_ID < 50300) {
			spl_autoload_register(array(__CLASS__, 'autoload'));
		} else {
			spl_autoload_register(array(__CLASS__, 'autoload'), true, $prepend);
		}
		
	}

	
	/**
	 * Handles autoloading of classes.
	 *
	 * @param string $class A class name.
	 */
	public static function autoload($class)
	{
		if (0 !== strpos($class, 'Twig')) {
			return;
		}

		// Modified path to Twig in Types.
		$file = TYPES_ABSPATH . '/vendor/twig/twig/lib/' . str_replace( array( '_', "\0" ), array( '/', '' ), $class .'.php' );

		if( is_file( $file ) ) {
			/** @noinspection PhpIncludeInspection */
			require $file;
		}
	}
}
