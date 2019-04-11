<?php

/**
 * Interface Toolset_Admin_Notice_Interface
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Interface
 *            All containing properties and methods without since tag are part of the initial release
 */
interface Toolset_Admin_Notice_Interface {
	/**
	 * @return string
	 */
	public function get_id();

	/**
	 * @param string $title
	 */
	public function set_title( $title );

	/**
	 * @return string
	 */
	public function get_title();

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	public function set_content( $content );

	/**
	 * $this->message
	 */
	public function get_content();


	/**
	 * Renders the whole notice
	 */
	public function render();

	/**
	 * Adds a condition
	 *
	 * @param Toolset_Condition_Interface $condition
	 */
	public function add_condition( Toolset_Condition_Interface $condition );

	/**
	 * True or false
	 * @param bool $bool
	 */
	public function set_is_dismissible_permanent( $bool );

	/**
	 * @return bool
	 */
	public function is_dismissible_permanent();


	/**
	 * True or false
	 * @param bool $bool
	 */
	public function set_is_dismissible_globally( $bool );

	/**
	 * @return bool
	 */
	public function is_dimissibile_globally();

	/**
	 * Sets priority of the message
	 *
	 * @param int $priority
	 */
	public function set_priority( $priority );

	/**
	 * @return int
	 */
	public function get_priority();

	/**
	 * Temporary message or not
	 * @return bool
	 */
	public function is_temporary();

	/**
	 * @return mixed
	 */
	public function dismiss();

	/**
	 * @return bool
	 */
	public function get_is_only_for_administrators();

	/**
	 * @param bool $bool
	 */
	public function set_is_only_for_administrators( $bool );

	/**
	 * @param string $key
	 */
	public function set_similar_notices_key( $key );

	/**
	 * @return string
	 */
	public function get_similar_notices_key();


	/**
	 * @return string
	 */
	public function render_content();


	/**
	 * Add a callback that will be invoked just before rendering the notice.
	 *
	 * It can be used for enqueuing assets, etc.
	 *
	 * @param callable $callback
	 * @return void
	 * @since 2.8
	 */
	public function add_dependency_callback( $callback );


	/**
	 * Set a context variable that will be accessible when rendering the notice template.
	 *
	 * @param array $context
	 *
	 * @return void
	 * @since 2.á
	 */
	public function set_template_context( $context );


	/**
	 * Get the context variable.
	 *
	 * @return array
	 */
	public function get_template_context();
}