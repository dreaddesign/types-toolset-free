<?php


class Types_Information_Container {
	protected $id;
	protected $messages = array();
	protected $messages_filtered = array();
	protected $output_container = false;

	/**
	 * We need this option to show messages for hooks followed by a page reload.
	 * @var bool
	 */
	protected $cached = false;

	public function __construct( $id ) {
		$this->id = $id;
	}

	// get id
	public function get_id() {
		return $this->id;
	}

	// add message
	public function add_message( Types_Information_Message $message ) {
		$this->messages[] = $message;
	}

	public function get_messages() {
		return $this->messages;
	}

	/**
	 * @return array|bool
	 */
	public function get_descriptions() {
		if( empty( $this->messages ) )
			return false;

		// check if descriptions were already filtered
		if( isset( $this->messages_filtered['descriptions'] ) )
			return $this->messages_filtered['descriptions'];

		$descriptions = array();

		foreach( $this->messages as $message ){
			$descriptions[] = $message->get_description();
		}

		Types_Helper_Placeholder::replace( $descriptions );
		$this->messages_filtered['descriptions'] = $descriptions;
		return $descriptions;
	}


	/**
	 *
	 * @return bool
	 */
	public function get_examples() {
		if( empty( $this->messages ) )
			return false;

		// check if descriptions were already filtered
		if( isset( $this->messages_filtered['view_example'] ) )
			return $this->messages_filtered['view_example'];

		$examples = array();

		foreach( $this->messages as $message ) {
			if( !empty( $examples ) ) {
				$current_example = $message->get_example();

				// check if the view example is already applied
				foreach( $examples as $view ) {
					// skip to next message
					if( $view['label'] == $current_example['label'] )
						continue 2;
				}

				// no duplication, add view example
				$examples[] = $current_example;
				continue;
			}

			// view example has no value yet
			$examples[] = $message->get_example();
		}

		Types_Helper_Placeholder::replace( $examples );
		$this->messages_filtered['view_example'] = $examples;
		return $examples;
	}

	/**
	 * @param bool|string $output e.g., a rendered Twig template
	 *
	 * @return bool|string
	 */
	public function render( $output = false ) {

		$output = $output ? $output : '';

		if( $this->cached && !$this->is_cached() )
			return false;

		// use message render function if no output is set
		if( empty( $output ) && ! empty( $this->messages ) ) {
			foreach( $this->messages as $message ) {
				$output .= $message->render();
			}
		}

		// send output to container (e.g., meta-box) and return full container output
		if( $this->output_container ) {
			$this->output_container->set_content( $output );
			return $this->output_container->output();
		}

		return $output;
	}

	// set output container
	public function set_output_container( $output ) {
		// @todo type hinting Types_Helper_Output_Meta_Box
		$this->output_container = $output;
	}


	// cache on hook
	public function cache_on_hook( $hook ) {
		$this->cached = true;
		add_action( $hook, array( $this, 'cache' ) );
	}

	public function cache() {
		$this->cached = true;

		if( ! $this->id )
			return false;

		$db_messages = get_option( 'types_messages', array() );
		$db_messages[$this->id] = 1;

		update_option( 'types_messages', $db_messages );
	}

	/**
	 * Check if cached
	 *
	 * @return bool
	 */
	private function is_cached() {
		$db_messages = get_option( 'types_messages', array() );

		if( ! isset( $db_messages[$this->id] ) || empty( $db_messages[$this->id] ) )
			return false;

		// disable message again
		$db_messages[$this->id] = 0;
		update_option( 'types_messages', $db_messages );

		return true;
	}

}