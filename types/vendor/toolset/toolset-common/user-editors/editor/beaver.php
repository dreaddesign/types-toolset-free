<?php

/**
 * Editor class for the Beaver Builder.
 *
 * Handles all the functionality needed to allow the Beaver Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Beaver
	extends Toolset_User_Editors_Editor_Abstract {

	protected $id = 'beaver';
	protected $name = '';
	protected $option_name = '_toolset_user_editors_beaver_template';

	protected $logo_image_svg = 'bb.svg';

	/**
	 * Toolset_User_Editors_Editor_Beaver constructor.
	 *
	 * @param Toolset_User_Editors_Medium_Interface $medium
	 */
	public function __construct( Toolset_User_Editors_Medium_Interface $medium ) {
		parent::__construct( $medium );

		$this->set_name( defined( 'FL_BUILDER_VERSION' ) ? FLBuilderModel::get_branding() : $this->get_name() );
	}

	public function required_plugin_active() {

		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if ( defined( 'FL_BUILDER_VERSION' ) ) {
			return true;
		}

		return false;
	}

	public function run() {
		// register medium slug
		add_filter( 'fl_builder_post_types', array( $this, 'support_medium' ) );
	}

	/**
	 * We need to register the slug of our Medium in Beaver
	 *
	 * @wp-filter fl_builder_post_types
	 * @param $allowed_types
	 * @return array
	 */
	public function support_medium( $allowed_types ) {
		if( ! is_array( $allowed_types ) )
			return array( $this->medium->get_slug() );

		$allowed_types[] = $this->medium->get_slug();
		return $allowed_types;
	}
	
}
