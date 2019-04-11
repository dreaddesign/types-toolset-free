<?php

/**
 * Enum class. Holds names of m2m tables and provides methods that return full table names
 * with correct $wpdb prefixes.
 *
 * NOT to be used outside the m2m API under any circumstances.
 *
 * @since m2m
 */
class Toolset_Relationship_Table_Name {


	/** @var wpdb */
	private $wpdb;


	/**
	 * Toolset_Relationship_Table_Name constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 */
	public function __construct( wpdb $wpdb_di = null ) {
		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}
	}


	private static $instance;

	/**
	 * This is only a temporary solution to avoid refactoring of all usages of static methods
	 * on this class.
	 *
	 * @return Toolset_Relationship_Table_Name
	 */
	private static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private function get_full_table_name( $table_name ) {
		return $this->wpdb->prefix . $table_name;
	}


	public function association_table() {
		return $this->get_full_table_name( 'toolset_associations' );
	}


	public function relationship_table() {
		return $this->get_full_table_name( 'toolset_relationships' );
	}


	public function type_set_table() {
		return $this->get_full_table_name( 'toolset_type_sets' );
	}


	/**
	 * @deprecated Instantiate the class before using it.
	 * @return string
	 */
	public static function associations() {
		return self::get_instance()->association_table();
	}


	// fixme check all usages and update to the new table structure
	public static function association_translations() {
		throw new RuntimeException( 'The translations table was removed.');
	}


	/**
	 * @deprecated Instantiate the class before using it.
	 * @return string
	 */
	public static function relationships() {
		return self::get_instance()->relationship_table();
	}

}