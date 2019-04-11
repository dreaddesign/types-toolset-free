<?php

/**
 * Don't order associations by anything.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Orderby_Nothing implements IToolset_Association_Query_Orderby {

	public function get_orderby_clause() {
		return '';
	}

	public function set_order( $order ) {
		// Nothing to do here.
	}

	public function register_joins() {
		// Nothing to do here.
	}
}