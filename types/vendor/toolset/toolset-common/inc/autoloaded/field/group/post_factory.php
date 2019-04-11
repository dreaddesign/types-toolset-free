<?php

/**
 * Factory for the Toolset_Field_Group_Post class.
 *
 * @since 2.0
 */
class Toolset_Field_Group_Post_Factory extends Toolset_Field_Group_Factory {


	/**
	 * @return Toolset_Field_Group_Post_Factory
	 */
	public static function get_instance() {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
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
	 * @return null|Toolset_Field_Group_Post Field group or null if it can't be loaded.
	 */
	public static function load( $field_group ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Post_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();

		/** @noinspection PhpIncompatibleReturnTypeInspection Because this will always be a post field group. */
		return Toolset_Field_Group_Post_Factory::get_instance()->load_field_group( $field_group );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 * @param String $status Post status
	 * @param String $purpose Purpose.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '', $status = 'draft', $purpose = Toolset_Field_Group_Post::PURPOSE_GENERIC ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Toolset_Field_Group_Post_Factory::get_instance()->create_field_group( $name, $title, $status, $purpose );
	}


	public function get_post_type() {
		return Toolset_Field_Group_Post::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Toolset_Field_Group_Post';
	}


	private $post_type_assignment_cache = null;


	/**
	 * Get all field groups sorted by their association with post types.
	 *
	 * @return Toolset_Field_Group_Post[][] For each (registered) post type, there will be an array element, which is
	 *     an array of post field groups associated to it.
	 * @since m2m
	 */
	public function get_groups_by_post_types() {

		if( null == $this->post_type_assignment_cache ) {
			// We need also special-purpose groups; Everything will be filtered by $group->is_assigned_by_post_type.
			$groups = $this->query_groups( array( 'purpose' => '*' ) );

			$post_type_query = new Toolset_Post_Type_Query(
				array(
					Toolset_Post_Type_Query::HAS_SPECIAL_PURPOSE => null,
					Toolset_Post_Type_Query::RETURN_TYPE => 'slug'
				)
			);

			/** @var string[] $post_types */
			$post_types = $post_type_query->get_results();

			$this->post_type_assignment_cache = array();
			foreach( $post_types as $post_type_slug ) {
				$groups_for_post_type = array();

				foreach( $groups as $group ) {
					if( $group instanceof Toolset_Field_Group_Post
						&& $group->is_active()
						&& $group->is_assigned_to_type( $post_type_slug )
					) {
						$groups_for_post_type[] = $group;
					}
				}

				$this->post_type_assignment_cache[ $post_type_slug ] = $groups_for_post_type;
			}

		}

		return $this->post_type_assignment_cache;
	}


	/**
	 * Get array of groups that are associated with given post type.
	 *
	 * @param string $post_type_slug Slug of the post type.
	 *
	 * @return Toolset_Field_Group_Post[] Associated post field groups.
	 */
	public function get_groups_by_post_type( $post_type_slug ) {
		$groups_by_post_types = $this->get_groups_by_post_types();
		return toolset_ensarr( toolset_getarr( $groups_by_post_types, $post_type_slug ) );
	}


	/**
	 * This needs to be executed whenever a post field group is updated.
	 *
	 * Hooked into the wpcf_group_updated action.
	 * Erases cache for the get_groups_by_post_types() method.
	 *
	 * @param int $group_id Ignored
	 * @param Toolset_Field_Group $group Field group that has been just updated.
	 */
	public function on_group_updated( /** @noinspection PhpUnusedParameterInspection */ $group_id = null, $group = null ) {
		$this->post_type_assignment_cache = null;
	}
}
