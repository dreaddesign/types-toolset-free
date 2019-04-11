<?php

/**
 * Generates unique alias values for a given table name.
 *
 * Works under the assumption that there are no tables with similar names different only by a numeric suffix "_$n". :)
 * The generated values are unique within one class instance.
 *
 * @since 2.5.4
 */
class Toolset_Relationship_Database_Unique_Table_Alias {

	/**
	 * @var string[] Maps a table name to last used numeric suffix.
	 */
	private $last_ids = array();


	/**
	 * Generate a new unique value
	 * @param string $table_name
	 * @param bool $always_suffix Add a suffix even if using the table alias for the first time.
	 *
	 * @return string
	 */
	public function generate( $table_name, $always_suffix = false ) {

		if( ! array_key_exists( $table_name, $this->last_ids ) ) {
			$this->last_ids[ $table_name ] = 1;
		}

		$last_id = $this->last_ids[ $table_name ];
		$this->last_ids[ $table_name ]++;

		if( $last_id === 0 && ! $always_suffix ) {
			return $table_name;
		}

		return "{$table_name}_{$last_id}";
	}

}