<?php

/**
 * Base for custom query classes.
 *
 * Contains shared methods, mainly related to query argument processing.
 *
 * @since m2m
 * @deprecated Needed only for old association and relationship query classes. Remove when those are removed.
 */
abstract class Toolset_Relationship_Query_Base {


	/** @var array Query arguments. */
	protected $query;


	/** @var array Parsed and sanitized query vars (QUERY_* keys). */
	protected $query_vars;


	/** @var bool Use cached results if they're available?  */
	protected $use_cached_results;


	/** @var bool Update the cache with query results? */
	protected $cache_results;


	/**
	 * If SELECT FOUND_ROWS() must be run or not
	 *
	 * @var boolean
	 * @since m2m
	 */
	protected $dont_count_found_rows;


	/**
	 * Rows found
	 *
	 * @var int
	 * @since m2m
	 */
	protected $rows_found;


	/**
	 * Toolset_Relationship_Query constructor.
	 *
	 * @param array $query Query arguments.
	 */
	public function __construct( $query ) {
		$this->query = $query;
		$this->parse_query( $query );
	}


	protected abstract function parse_query( $query );


	/**
	 * Parse a single query argument.
	 *
	 * @param array $query Query arguments.
	 * @param string $var_name Name of the selected argument.
	 * @param null|callable $sanitize If a callable is provided, it will be used as a sanitizing function. It needs to
	 *     accept one parameter and return the sanitized value.
	 * @param null|mixed $default_value Value to be used if the argument is not set at all.
	 * @param null|array $allowed_list List of allowed values of the argument or null for no limitation. If $default_value
	 *     is used, this parameter is ignored.
	 */
	protected function parse_query_arg( $query, $var_name, $sanitize = null, $default_value = null, $allowed_list = null ) {

		if( ! isset( $query[ $var_name ] ) && null == $default_value ) {
			// Skip the query variable, it's not set at all.
			return;
		}

		$raw_value = toolset_getarr( $query, $var_name, $default_value, $allowed_list );

		$this->query_vars[ $var_name ] = ( is_callable( $sanitize ) ? $sanitize( $raw_value ) : $raw_value );
	}


	/**
	 * Check if a query variable is set.
	 *
	 * @param string $var_name
	 * @return bool
	 */
	protected function has_query_var( $var_name ) {
		return ( isset( $this->query_vars[ $var_name ] ) && null !== $this->query_vars[ $var_name ] );
	}


	/**
	 * Get a query variable.
	 *
	 * @param string $var_name
	 * @return mixed|null Variable value or null if it's not set.
	 */
	protected function get_query_var( $var_name ) {
		if( ! $this->has_query_var( $var_name ) ) {
			return null;
		}
		return $this->query_vars[ $var_name ];
	}


	protected abstract function get_subject_name_for_cache();


	/**
	 * Perform the query and get results.
	 *
	 * Depending on query arguments, the results may be cached.
	 *
	 * @return int[]|Toolset_Element[]|Toolset_Association_Base[] Array of results, depending on query arguments.
	 */
	public function get_results() {

		if( $this->use_cached_results ) {
			$cache = Toolset_Relationship_Query_Cache::get_instance();
			$value = $cache->get( $this->query, $this->get_subject_name_for_cache() );
			if( false !== $value ) {
				return $value;
			}
		}

		$sql_statement = $this->build_sql_statement();

		global $wpdb;
		$results = $wpdb->get_results( $sql_statement, $this->get_results_type() );

		if ( ! $this->dont_count_found_rows ) {
			$this->rows_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}

		$results = $this->postprocess_results( $results );

		if( $this->cache_results ) {
			$cache = Toolset_Relationship_Query_Cache::get_instance();
			$cache->set( $this->query, $this->get_subject_name_for_cache(), $results );
		}

		return $results;
	}


	/**
	 * Second argument for $wpdb->get_results().
	 *
	 * @return string
	 */
	protected function get_results_type() {
		return OBJECT;
	}


	/**
	 * Build the MySQL statement for querying the data, depending on query variables.
	 *
	 * @return string MySQL query statement.
	 * @since m2m
	 */
	protected abstract function build_sql_statement();


	/**
	 * Process raw output from $wpdb.
	 *
	 * @param $rows
	 * @return array
	 */
	protected abstract function postprocess_results( $rows );


	/**
	 * Gets the number of rows found
	 *
	 * @return int
	 * @since m2m
	 */
	public function get_rows_found() {
		return $this->rows_found;
	}


}
