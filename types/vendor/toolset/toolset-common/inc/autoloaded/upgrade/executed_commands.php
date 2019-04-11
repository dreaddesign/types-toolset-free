<?php

/**
 * Manages the list of commands that have already been executed on the site.
 *
 * @since 2.5.7
 */
class Toolset_Upgrade_Executed_Commands {


	/** The option is used to store an array of command names. */
	const OPTION_NAME = 'toolset_executed_upgrade_commands';


	/** @var string[] Cache for the array of executed command names. */
	private $executed_commands;


	/**
	 * Check whether a particular command was executed.
	 *
	 * @param string $command_name
	 *
	 * @return bool
	 */
	public function was_executed( $command_name ) {
		$executed_commands = $this->get_option();
		return in_array( $command_name, $executed_commands, true );
	}


	/**
	 * Store the information that a particular command has been executed.
	 *
	 * @param string $command_name
	 */
	public function add_executed_command( $command_name ) {
		if( $this->was_executed( $command_name ) ) {
			// Already added.
			return;
		}

		$executed_commands = $this->get_option();
		$executed_commands[] = $command_name;
		$this->executed_commands[] = $command_name;
		update_option( self::OPTION_NAME, $executed_commands, false );
	}


	private function get_option() {
		if( null === $this->executed_commands ) {
			$this->executed_commands = toolset_ensarr( get_option( self::OPTION_NAME ) );
		}

		return $this->executed_commands;
	}


	public function reset() {
		$this->executed_commands = null;
		delete_option( self::OPTION_NAME );
	}

}