<?php

/**
 * Carefully check whether there are any legacy post relationships defined on the site.
 *
 * @since 2.5.10
 */
class Toolset_Condition_Plugin_Types_Has_Legacy_Relationships implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		$legacy_relationships = toolset_ensarr( get_option( 'wpcf_post_relationship', array() ) );

		if( empty( $legacy_relationships ) ) {
			// Really nothing to stored here.
			return false;
		}

		// Now, we have to be careful. If the site did have relationships in the past, there may be some leftover
		// data in this option (parent post slug as an element key, but the value doesn't contain the child).
		foreach( $legacy_relationships as $parent_slug => $relationship ) {
			if( is_array( $relationship ) && ! empty( $relationship ) ) {
				// Found something.
				return true;
			}
		}

		// No actual relationship found.
		return false;
	}


}