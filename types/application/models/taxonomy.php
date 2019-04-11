<?php

/**
 * Class Types_Taxonomy
 *
 * FIXME please document this!
 */
class Types_Taxonomy {

	protected $wp_taxonomy;

	protected $name;

	public function __construct( $taxonomy ) {
		if( is_object( $taxonomy ) && isset( $taxonomy->name ) ) {
			$this->wp_taxonomy = $taxonomy;
			$this->name        = $taxonomy->name;
		} else {
			$this->name = $taxonomy;
			$registered = get_post_type_object( $taxonomy );

			if( $registered )
				$this->wp_taxonomy = $registered;
		}
	}

	public function __isset( $property ) {
		if( $this->wp_taxonomy === null )
			return false;

		if( ! property_exists( $this->wp_taxonomy, 'labels' ) )
			return false;

		if( ! property_exists( $this->wp_taxonomy->labels, $property ) )
			return false;

		return true;
	}

	public function __get( $property ) {
		if( ! $this->__isset( $property ) )
			return false;

		return $this->wp_taxonomy->labels->$property;
	}

	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . 'admin.php?page=wpcf-edit-tax&wpcf-tax=' . $this->get_name();
	}
}