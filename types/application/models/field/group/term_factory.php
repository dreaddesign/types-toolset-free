<?php

/**
 * Factory for the Types_Field_Group_Term class.
 *
 * @since 1.9
 */
class Types_Field_Group_Term_Factory extends Types_Field_Group_Factory {


	/**
	 * @return Types_Field_Group_Term_Factory
	 */
	public static function get_instance() {
		return parent::get_instance();
	}

	protected function __construct() {
		parent::__construct();

		add_action( 'wpcf_group_updated', array( $this, 'on_group_updated' ), 10, 2 );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @return null|Types_Field_Group_Term Field group or null if it can't be loaded.
	 */
	public static function load( $field_group ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Types_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Types_Field_Group_Term_Factory::get_instance()->load_field_group( $field_group );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 *
	 * @return null|Types_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '' ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Types_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Types_Field_Group_Term_Factory::get_instance()->create_field_group( $name, $title );
	}


	public function get_post_type() {
		return Types_Field_Group_Term::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Types_Field_Group_Term';
	}


	/**
	 * @var null|Types_Field_Group_Term[][] Cache for the get_groups_by_taxonomies() method.
	 */
	private $taxonomy_assignment_cache = null;


	/**
	 * Clears the cache for taxonomy assignemnts.
	 * It is only used for testing purposes.
	 *
	 * @since 2.2
	 * @deprecated deprecated since version 2.2
	 */
	public function clear_taxonomy_assignment_cache() {
		$this->taxonomy_assignment_cache = null;
	}


	/**
	 * Produce a list of all taxonomies with groups that belong to them.
	 *
	 * @return Types_Field_Group_Term[][] Associative array where keys are taxonomy slugs and values are arrays of field
	 *     groups that are associated with those taxonomies.
	 */
	public function get_groups_by_taxonomies() {
		if( null == $this->taxonomy_assignment_cache ) {
			$groups = $this->query_groups();
			$taxonomies = get_taxonomies();

			$this->taxonomy_assignment_cache = array();
			foreach( $taxonomies as $taxonomy ) {
				$taxonomy_slug = $taxonomy;
				$groups_for_taxonomy = array();

				foreach( $groups as $group ) {
					if( $group instanceof Types_Field_Group_Term
						&& $group->is_active()
						&& $group->has_associated_taxonomy( $taxonomy_slug )
					) {
						$groups_for_taxonomy[] = $group;
					}
				}

				$this->taxonomy_assignment_cache[ $taxonomy_slug ] = $groups_for_taxonomy;
			}
		}

		return $this->taxonomy_assignment_cache;
	}


	/**
	 * Get array of groups that are associated with given taxonomy.
	 *
	 * @param string $taxonomy_slug Slug of the taxonomy
	 *
	 * @return Types_Field_Group_Term[] Associated term field groups.
	 */
	public function get_groups_by_taxonomy( $taxonomy_slug ) {
		$groups_by_taxonomies = $this->get_groups_by_taxonomies();
		return wpcf_ensarr( wpcf_getarr( $groups_by_taxonomies, $taxonomy_slug ) );
	}


	/**
	 * This needs to be executed whenever a term group is updated.
	 *
	 * Hooked into the wpcf_group_updated action.
	 * Erases cache for the get_groups_by_taxonomies() method.
	 *
	 * @param int $group_id Ignored
	 * @param Types_Field_Group $group Field group that has been just updated.
	 */
	public function on_group_updated( /** @noinspection PhpUnusedParameterInspection */ $group_id, $group ) {
		if( $group instanceof Types_Field_Group_Term ) {
			$this->taxonomy_assignment_cache = null;
		}
	}



}
