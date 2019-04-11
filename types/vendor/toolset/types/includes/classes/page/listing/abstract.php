<?php

/**
 * Represents an abstract listing page.
 */
abstract class WPCF_Page_Listing_Abstract extends WPCF_Page_Abstract {


	/**
	 * @return string Page slug.
	 */
	protected abstract function get_page_name();


	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public abstract function page_handler();


}