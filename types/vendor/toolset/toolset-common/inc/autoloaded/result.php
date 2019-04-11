<?php

/**
 * Represents a result of a single operation.
 *
 * This is a wrapper for easy handling of results of different types.
 * It can encapsulate a boolean, WP_Error, boolean + message, or an exception.
 *
 * It is supposed to work well with Toolset_Result_Set.
 *
 * @since 2.3
 */
class Toolset_Result {

	/** @var bool */
	protected $is_error;

	/** @var bool|WP_Error|Exception What was passed as a result value. */
	protected $inner_result;

	/** @var string|null Display message, if one was provided. */
	protected $display_message;


	/**
	 * Toolset_Result constructor.
	 *
	 * @param bool|WP_Error|Exception $value Result value. For boolean, true determines a success, false
	 *     determines a failure. WP_Error and Exception are interpreted as failures.
	 * @param string|null $display_message Optional display message that will be used if a boolean result is
	 *     provided. If an exception is provided, it will be used as a prefix of the message from the exception.
	 * @throws InvalidArgumentException
	 * @since 2.3
	 */
	public function __construct( $value, $display_message = null ) {

		$this->inner_result = $value;

		if( is_bool( $value ) ) {
			$this->is_error = ! $value;
			$this->display_message = ( is_string( $display_message ) ? $display_message : null );
		} else if( $value instanceof WP_Error ) {
			$this->is_error = true;
			$this->display_message = $value->get_error_message();
		} else if( $value instanceof Exception ) {
			$this->is_error = true;
			$this->display_message = (
				( is_string( $display_message ) ? $display_message . ': ' : '' )
				. $value->getMessage()
			);
		} else {
			throw new InvalidArgumentException( 'Unrecognized result value.' );
		}

	}


	public function is_error() { return $this->is_error; }


	public function is_success() { return ! $this->is_error; }


	public function has_message() { return ( null != $this->display_message ); }


	public function get_message() { return $this->display_message; }


	/**
	 * Returns the result as an associative array in a standard form.
	 * 
	 * That means, it will allways have the boolean element 'success' and
	 * a string 'message', if a display message is set.
	 * 
	 * @return array
	 * @since 2.3
	 */
	public function to_array() {
		$result = array( 'success' => $this->is_success() );
		if( $this->has_message() ) {
			$result['message'] = $this->get_message();
		}
		return $result;
	}




}