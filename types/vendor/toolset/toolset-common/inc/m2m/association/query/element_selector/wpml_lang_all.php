<?php

/**
 * Element selector that translates post elements and chooses the best element ID
 * when the current language is "all" (to display all content disregarding their language).
 *
 * This selector uses a specific provided language instead, or uses the default language.
 *
 * The Toolset_Association_Query_V2 is responsible for determining the correct language code.
 *
 * @since 2.6.8
 */
class Toolset_Association_Query_Element_Selector_Wpml_Lang_All
	extends Toolset_Association_Query_Element_Selector_Wpml
{

	/** @var string|null Language code (except 'all') or null for default language */
	private $translation_language;


	/**
	 * Toolset_Association_Query_Element_Selector_Wpml_Lang_All constructor.
	 *
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param string|null $translation_language Language code (except 'all') or null for default language.
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_WPML_Compatibility|null $wpml_compatibility_di
	 */
	public function __construct(
		Toolset_Relationship_Database_Unique_Table_Alias $table_alias,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		$translation_language,
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_WPML_Compatibility $wpml_compatibility_di = null
	) {
		parent::__construct( $table_alias, $join_manager, $wpdb_di, $database_operations_di, $wpml_compatibility_di );

		$this->translation_language = $translation_language;
	}


	/**
	 * Get the language that will be used for the query results (besides the default language).
	 *
	 * @return string
	 * @since 2.6.8
	 */
	protected function get_translation_language() {
		if( null === $this->translation_language ) {
			return $this->wpml_service->get_default_language();
		}

		return $this->translation_language;
	}

}