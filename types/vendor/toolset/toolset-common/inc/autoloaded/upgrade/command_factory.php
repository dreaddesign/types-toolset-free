<?php

/**
 * Factory for the IToolset_Upgrade_Command classes.
 *
 * @since 2.5.3
 */
class Toolset_Upgrade_Command_Factory {

	/**
	 * @param string $command_class_name
	 *
	 * @return IToolset_Upgrade_Command
	 * @throws InvalidArgumentException If the class doesn't exist
	 *     or doesn't implement the IToolset_Upgrade_Command interface.
	 */
	public function create( $command_class_name ) {
		if( ! class_exists( $command_class_name, true ) ) {
			throw new InvalidArgumentException( 'Upgrade command not found.' );
		}

		$command_class = new $command_class_name();

		if( ! $command_class instanceof IToolset_Upgrade_Command ) {
			throw new InvalidArgumentException( 'Invalid upgrade command.' );
		}

		return $command_class;
	}

}