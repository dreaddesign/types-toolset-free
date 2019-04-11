<?php

/**
 * Defines an upgrade command and determines whether it should be executed or not.
 *
 * @since 2.5.3
 */
class Toolset_Upgrade_Command_Definition {

	/** @var string */
	private $command_class_name;

	/** @var int */
	private $upgrade_version;

	/** @var Toolset_Upgrade_Command_Factory */
	private $_command_factory;


	/**
	 * Toolset_Upgrade_Command_Definition constructor.
	 *
	 * @param string $command_class_name
	 * @param int $upgrade_version If we have Toolset Common at this version or higher and the command hasn't run yet,
	 *     it should be executed.
	 * @param Toolset_Upgrade_Command_Factory|null $command_factory_di
	 * @throws InvalidArgumentException On invalid input.
	 */
	public function __construct(
		$command_class_name, $upgrade_version,
		Toolset_Upgrade_Command_Factory $command_factory_di = null
	) {
		if(
			empty( $command_class_name ) || ! is_string( $command_class_name )
			|| ! is_int( $upgrade_version ) || 0 > $upgrade_version
		) {
			throw new InvalidArgumentException();
		}

		$this->command_class_name = $command_class_name;
		$this->upgrade_version = $upgrade_version;
		$this->_command_factory = $command_factory_di;
	}


	/**
	 * Determine whether the command should be executed.
	 *
	 * @param int $from_version Current (database) version.
	 * @param int $to_version Version we're upgrading to.
	 *
	 * @return bool
	 */
	public function should_run( $from_version, /** @noinspection PhpUnusedParameterInspection */ $to_version ) {
		return ( $from_version < $this->upgrade_version && $this->upgrade_version <= $to_version );
	}


	/**
	 * Create the command instance.
	 *
	 * @return IToolset_Upgrade_Command
	 */
	public function get_command() {
		return $this->get_command_factory()->create( $this->command_class_name );
	}


	/**
	 * @return string Unique command name.
	 * @since 2.5.7
	 */
	public function get_command_name() {
		return $this->command_class_name;
	}


	/**
	 * @return Toolset_Upgrade_Command_Factory
	 */
	private function get_command_factory() {
		if( null === $this->_command_factory ) {
			$this->_command_factory = new Toolset_Upgrade_Command_Factory();
		}

		return $this->_command_factory;
	}

}