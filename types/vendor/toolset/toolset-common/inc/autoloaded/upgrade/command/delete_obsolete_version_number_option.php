<?php

/**
 * Delete an options which were used in the previous upgrade mechanism implementation.
 *
 * @since 2.6.5
 */
class Toolset_Upgrade_Command_Delete_Obsolete_Upgrade_Options implements IToolset_Upgrade_Command {


	/**
	 * Run the command.
	 *
	 * @return Toolset_Result|Toolset_Result_Set
	 */
	public function run() {
		delete_option( 'toolset_database_version' );

		// This is still used but we need to reset it.
		/** @var Toolset_Upgrade_Executed_Commands $executed_commands */
		$executed_commands = Toolset_Singleton_Factory::get( 'Toolset_Upgrade_Executed_Commands' );
		$executed_commands->reset();

		return new Toolset_Result( true );
	}
}