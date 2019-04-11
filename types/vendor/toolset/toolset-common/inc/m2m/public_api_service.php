<?php

namespace OTGS\Toolset\Common\M2M;

/**
 * Helper class for the Public Relationship API.
 *
 * @package OTGS\Toolset\Common\M2M
 * @since 2.6.4
 */
class PublicApiService {

	/**
	 * @param string|string[] $relationship Relationship slug or a pair of post type slugs identifying a legacy relationship.
	 *
	 * @return \IToolset_Relationship_Definition|null
	 */
	public function get_relationship_definition( $relationship ) {
		$definition_repository = \Toolset_Relationship_Definition_Repository::get_instance();

		if( is_array( $relationship ) && count( $relationship ) === 2 ) {
			$relationship_definition = $definition_repository->get_legacy_definition( $relationship[0], $relationship[1] );
		} elseif ( is_string( $relationship ) ) {
			$relationship_definition = $definition_repository->get_definition( $relationship );
		} else {
			throw new \InvalidArgumentException( 'The relationship must be a string with the relationship slug or an array with two post types.' );
		}

		return $relationship_definition;
	}


	/**
	 * Transform a relationship definition to an associative array that the API will offer to third-party
	 * software.
	 *
	 * @param \IToolset_Relationship_Definition $relationship_definition
	 *
	 * @return array|null Relationship information or null if the relationship definition doesn't exist.
	 */
	public function format_relationship_definition( \IToolset_Relationship_Definition $relationship_definition ) {
		if( null === $relationship_definition ) {
			return null;
		}

		$origin = $relationship_definition->get_origin()->get_origin_keyword();
		if( 'wizard' === $origin ) {
			$origin = 'standard';
		}

		$result = array(
			'slug' => $relationship_definition->get_slug(),
			'labels' => array(
				'plural' => $relationship_definition->get_display_name_plural(),
				'singular' => $relationship_definition->get_display_name_singular()
			),
			'roles' => array(
				'parent' => array(
					'domain' => $relationship_definition->get_parent_domain(),
					'types' => $relationship_definition->get_parent_type()->get_types()
				),
				'child' => array(
					'domain' => $relationship_definition->get_child_domain(),
					'types' => $relationship_definition->get_child_type()->get_types()
				)
			),
			'cardinality' => array(
				'limits' => $relationship_definition->get_cardinality()->to_array(),
				'type' => $relationship_definition->get_cardinality()->get_type()
			),
			'origin' => $origin
		);

		if( null !== $relationship_definition->get_intermediary_post_type() ) {
			$result['roles']['intermediary'] = array(
				'domain' => \Toolset_Element_Domain::POSTS,
				'types' => $relationship_definition->get_intermediary_post_type()
			);
		}

		return $result;
	}

}