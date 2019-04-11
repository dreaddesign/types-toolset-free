<?php

/**
 * Represents an object that can restrict the association query in certain cases,
 * making it less complex and more performant.
 *
 * Note: No implementation yet, but ideas:
 * - disable the WPML version of element selector when it's not needed
 *     - the domain of all elements is known to be something non-translatable
 *     - the post types involved are all known and non-translatable
 * - if the above is true only for one role, make the element selector use the non-WPML way
 *     only for that role
 * - etc.
 *
 * @since 2.5.10
 */
interface IToolset_Association_Query_Restriction {


	/**
	 * Apply the restrictions.
	 *
	 * @return void
	 */
	public function apply();


	/**
	 * Clear the restrictions after the query has been run.
	 *
	 * @return void
	 */
	public function clear();

}