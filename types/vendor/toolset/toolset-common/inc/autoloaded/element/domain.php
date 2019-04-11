<?php

/**
 * Enum class that holds the names of element domains.
 *
 * @since 2.5.4
 */
class Toolset_Element_Domain {

	const POSTS = 'posts';
	const USERS = 'users';
	const TERMS = 'terms';


	public static function all() {
		return array( self::POSTS, self::USERS, self::TERMS );
	}

}