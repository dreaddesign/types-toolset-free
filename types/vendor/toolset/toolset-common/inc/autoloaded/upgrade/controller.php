<?php

/**
 * Toolset Common Library upgrade mechanism.
 *
 * Compares a number of the library version in database with the current one. If the current version is lower,
 * it executes all the commands defined in get_upgrade_commands() and updates the database.
 *
 * Performance cost on every server request:
 *     - one add_action hook
 *     - one autoloaded option
 *
 * @since 2.5.1
 */
class Toolset_Upgrade_Controller {


	/** Name of the option used to store version number. */
	const DATABASE_VERSION_OPTION_NUMBER = 'toolset_data_structure_version';


	/** @var Toolset_Upgrade_Controller */
	private static $instance;


	/** @var bool */
	private $is_initialized = false;


	/** @var Toolset_Constants */
	private $constants;


	/** @var Toolset_Upgrade_Command_Definition_Repository|null */
	private $_command_definition_repository;


	/** @var Toolset_Upgrade_Executed_Commands|null */
	private $_executed_commands;


	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function initialize() {
		if( $this->is_initialized ) {
			return;
		}

		// When everything is loaded and possibly hooked in the upgrade action, we can proceed.
		add_action( 'toolset_common_loaded', array( $this, 'check_upgrade' ) );

		$this->is_initialized = true;
	}


	public function __construct(
		Toolset_Constants $constants_di = null,
		Toolset_Upgrade_Command_Definition_Repository $command_definition_repository_di = null,
		Toolset_Upgrade_Executed_Commands $executed_commands_di = null
	) {
		$this->constants = ( null === $constants_di ? new Toolset_Constants() : $constants_di );
		$this->_command_definition_repository = $command_definition_repository_di;
		$this->_executed_commands = $executed_commands_di;
	}


	/**
	 * Check if an upgrade is needed.
	 *
	 * Do not call this manually, there's no need to.
	 *
	 * @since m2m
	 */
	public function check_upgrade() {

		$database_version = $this->get_database_version();
		$library_version = $this->get_library_version();
		$is_upgrade_needed = ( 0 !== $library_version && $database_version < $library_version );

		if( $is_upgrade_needed ) {

			// Safety measure - Abort if the library isn't fully loaded.
			if( false === apply_filters( 'toolset_is_toolset_common_available', false ) ) {
				return;
			}

			// This is required by the tcl-status plugin.
			// if( apply_filters( 'toolset_disable_upgrade_routine', false ) ) {
			//	return;
			// }

			$this->do_upgrade( $database_version, $library_version );
		}
	}


	/**
	 * Get current version number.
	 *
	 * @return int
	 * @since m2m
	 */
	private function get_library_version() {
		$version = (
			$this->constants->defined( 'TOOLSET_DATA_STRUCTURE_VERSION' )
				? (int) $this->constants->constant( 'TOOLSET_DATA_STRUCTURE_VERSION' )
				: 0
		);

		return $version;
	}


	/**
	 * Get number of the version stored in the database.
	 *
	 * @return int
	 * @since m2m
	 */
	private function get_database_version() {
		$version = (int) get_option( self::DATABASE_VERSION_OPTION_NUMBER, 0 );

		return $version;
	}


	/**
	 * Update the version number stored in the database.
	 *
	 * @param int $version_number
	 * @since m2m
	 */
	private function update_database_version( $version_number ) {
		if( is_numeric( $version_number ) ) {
			update_option( self::DATABASE_VERSION_OPTION_NUMBER, (int) $version_number, true );
		}
	}


	/**
	 * Perform the actual upgrade.
	 *
	 * @param int $from_version
	 * @param int $to_version
	 */
	private function do_upgrade( $from_version, $to_version ) {

		$command_definitions = $this->get_upgrade_commands();
		$final_result = new Toolset_Result_Set();

		foreach ( $command_definitions as $command_definition ) {

			if ( ! $command_definition->should_run( $from_version, $to_version ) ) {
				continue;
			}

			if( $this->get_executed_commands()->was_executed( $command_definition->get_command_name() ) ) {
				continue;
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

				$command = $command_definition->get_command();
				$result = $command->run();

			} else {
				// Ignore errors as we don't have a proper way to display any output from this yet.
				try {
					$command = $command_definition->get_command();
					$result = $command->run();
				} catch ( Throwable $e ) {
					// PHP 7
					$result = new Toolset_Result( $e );
				} /** @noinspection PhpRedundantCatchClauseInspection */
				/** @noinspection PhpWrongCatchClausesOrderInspection */
				catch ( Exception $e ) {
					// PHP 5
					$result = new Toolset_Result( $e );
				}
			}

			$is_success = (
				( $result instanceof Toolset_Result && $result->is_success() )
				|| ( $result instanceof Toolset_Result_Set && $result->is_complete_success() )
			);

			if( $is_success ) {
				$this->get_executed_commands()->add_executed_command( $command_definition->get_command_name() );
			}

			$final_result->add( $result );
		}

		// Only consider the database updated when everything has succeeded.
		if( ! $final_result->has_results() || $final_result->is_complete_success() ) {
			$this->update_database_version( $to_version );
		} else {
			$this->show_error_notice( $final_result );
		}
	}


	/**
	 * Show an undismissible temporary error message with upgrade results.
	 *
	 * @param Toolset_Result_Set $results
	 * @since 2.6.4
	 */
	private function show_error_notice( Toolset_Result_Set $results ) {
		$notice = new Toolset_Admin_Notice_Error(
			'toolset-database-upgrade-error',
			'<p>'
				. __( 'Oops! There\'s been a problem when upgrading Toolset data structures. Please make sure your current configuration allows WordPress to alter database tables.', 'wpcf' )
				. sprintf(
					__( 'If the problem persists, please don\'t hesitate to contact %sour support%s with this technical information:', 'wpcf' ),
					'<a href="https://toolset.com/forums/forum/professional-support/" target="_blank">',
					' <i class="fa fa-external-link"></i></a>'
				)
			. '</p>'
			. '<p><code>' . $results->concat_messages( "\n" ) . '</code></p>'
		);

		$notice->set_is_dismissible_permanent( false );
		$notice->set_is_dismissible_globally( false );
		$notice->set_is_only_for_administrators( true );

		Toolset_Admin_Notices_Manager::add_notice( $notice );
	}


	/**
	 * Get the upgrade commands to consider.
	 *
	 * The commands will be executed in the order in which they are returned.
	 *
	 * @return Toolset_Upgrade_Command_Definition[]
	 * @since 2.5.4
	 */
	private function get_upgrade_commands() {
		if( null === $this->_command_definition_repository ) {
			$this->_command_definition_repository = new Toolset_Upgrade_Command_Definition_Repository();
		}

		return $this->_command_definition_repository->get_commands();
	}


	/**
	 * @return Toolset_Upgrade_Executed_Commands
	 * @since 2.5.7
	 */
	public function get_executed_commands() {
		if( null === $this->_executed_commands ) {
			$this->_executed_commands = Toolset_Singleton_Factory::get( 'Toolset_Upgrade_Executed_Commands' );
		}

		return $this->_executed_commands;
	}


}
