<?php

/**
 * Handles database migration from legacy post relationships to m2m relationships in multiple steps.
 * 
 * @since m2m
 */
class Toolset_Ajax_Handler_Migrate_To_M2M extends Toolset_Ajax_Handler_Abstract {

	// Phases of the migration process
	const PHASE_DBDELTA = 0;

	const PHASE_DEFINITION_MIGRATION = 1;

	const PHASE_ASSOCIATION_MIGRATION = 2;

	const PHASE_FINISH = 3;

	// Fixed step numbers in the first phase.
	// These need to be consecutive numbers.
	const STEP_MAINTENANCE_ON = 0;
	const STEP_MAYBE_DROP_TABLES = 1;
	const STEP_CREATE_TABLES = 2;


	/** @var Toolset_Relationship_Controller */
	private $relationship_controller;

	/** @var null|Toolset_Relationship_Migration_Controller */
	private $migration_controller;


	/**
	 * Toolset_Ajax_Handler_Migrate_To_M2M constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Toolset_Relationship_Controller|null $di_relationship_controller
	 * @param Toolset_Relationship_Migration_Controller|null $di_migration_controller
	 */
	public function __construct(
		$ajax_manager,
		Toolset_Relationship_Controller $di_relationship_controller = null,
		Toolset_Relationship_Migration_Controller $di_migration_controller = null
	) {
		parent::__construct( $ajax_manager );

		$this->relationship_controller = (
		null === $di_relationship_controller
			? Toolset_Relationship_Controller::get_instance()
			: $di_relationship_controller
		);

		$this->migration_controller = $di_migration_controller;
	}


	private function get_migration_controller() {
		if( null === $this->migration_controller ) {
			$this->migration_controller = new Toolset_Relationship_Migration_Controller();
		}
		return $this->migration_controller;
	}


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_MIGRATE_TO_M2M
			)
		);

		$this->relationship_controller->initialize_full();
		$this->relationship_controller->force_autoloader_initialization();
		$migration_controller = $this->get_migration_controller();

		$step_number = (int) toolset_getarr( $_POST, 'step', 0 );
		$options = toolset_ensarr( toolset_getarr( $_POST, 'options' ) );

		// If this is set to false, the migration process halts (there will not be another AJAX call)
		$continue = true;

		$results = new Toolset_Result_Set();

		/**
		 * This allows to override the number of items per migration step.
		 *
		 * It is very importent that if the filter is used, the same value is returned for every step of the
		 * migration process. Otherwise things will break.
		 *
		 * @since m2m
		 */
		$items_per_step = apply_filters( 'toolset_m2m_migration_items_per_step', 500 );

		$current_phase = toolset_getarr( $_POST, 'phase', self::PHASE_DBDELTA );

		// Phase for the next AJAX call
		$next_phase = $current_phase;

		// Used for calculating offsets in the last two phases.
		$steps_before_association_migration = (int) toolset_getarr( $_POST, 'first_phase_step', 0 );

		$has_maintenance_mode = (bool) toolset_getarr( $options, 'use_maintenance_mode', false );

		$is_fatal_error = false;
		$keep_maintenance_mode = false;

		switch ( $current_phase ) {

			case self::PHASE_DBDELTA: {
				// Putting this here because adding another phase would require more changes to the
				// migration dialog.
				if( $step_number === self::STEP_MAINTENANCE_ON ) {
					if( $has_maintenance_mode ) {
						$results->add( $this->enable_maintenance_mode() );
						$continue = $results->is_complete_success();
					} else {
						$results->add( true, __( 'Not using maintenance mode for the migration.', 'wpcf' ) );
						$continue = true;
					}
					$keep_maintenance_mode = true;
					$is_fatal_error = ! $continue;
				} else {
					$continue = $this->phase_dbdelta( $step_number, $migration_controller, $results, $next_phase );
				}
				break;
			}

			case self::PHASE_DEFINITION_MIGRATION: {
				// Second step - (re)create relationship definitions.

				$adjust_translation_mode = (bool) toolset_getarr( $options, 'adjust_translation_mode' );
				$definition_migration_result = $migration_controller->migrate_relationship_definitions( $adjust_translation_mode );
				if ( $definition_migration_result->is_complete_success() ) {
					$results->add( $definition_migration_result );
					$results->add( true, __( 'Relationship definitions migrated.', 'wpcf' ) );
				} else {
					$results->add( $definition_migration_result );
				}

				// Stop if there has been a failure
				$continue = $results->is_complete_success();

				$next_phase = self::PHASE_ASSOCIATION_MIGRATION;
				$steps_before_association_migration = $step_number + 1;

				break;
			}

			// Migrate associations in batches.
			case self::PHASE_ASSOCIATION_MIGRATION: {

				$migration_step = $step_number - $steps_before_association_migration;
				$offset = $items_per_step * $migration_step;
				$create_default_language_if_missing = ( 'create' === toolset_getarr( $options, 'posts_without_default_translation' ) );
				$copy_post_content_when_creating = (bool) toolset_getarr( $options, 'copy_content_when_creating_posts' );

				$data_migration_result = $migration_controller->migrate_associations(
					$offset, $items_per_step, $create_default_language_if_missing, $copy_post_content_when_creating
				);

				// Always add the original result, as it may contain additional information, even in case of success.
				$results->add( $data_migration_result );

				// Decide if we have to continue.
				if ( $data_migration_result instanceof Toolset_Result_Updated
					&& $data_migration_result->is_success()
				) {
					if ( $data_migration_result->has_items_updated() ) {
						$results->add(
							true,
							sprintf(
								__( '(%d) %d items processed.', 'wpcf' ),
								$migration_step + 1,
								$data_migration_result->get_updated_item_count()
							)
						);
					} else {
						$results->add( true, __( 'Associations processed.', 'wpcf' ) );
						$next_phase = self::PHASE_FINISH;
					}

				}

				break;
			}

			case self::PHASE_FINISH: {

				$migration_controller->finish();
				$results->add( true, __( 'The migration process is complete.', 'wpcf' ) );
				$continue = false;

				break;
			}

		}

		// Never leave the maintenance mode behind unless specifically instructed to do so
		// (when there is an error because the maintenance mode is already active).
		if( $has_maintenance_mode && ! $continue && ! $keep_maintenance_mode ) {
			$results->add( $this->disable_maintenance_mode() );
		}

		$result_status = 'success';
		if( ! $results->is_complete_success() ) {
			// Something went wrong but we can still continue.
			$result_status = 'warning';
		}
		if( $is_fatal_error ) {
			// Something went wrong and the process cannot continue anymore,
			$result_status = 'error';
		}

		$this->ajax_finish(
			array(
				'message' => $results->concat_messages( "\n> " ),
				'continue' => $continue,
				'previous_phase' => $current_phase,
				'status' => $result_status,

				// keeping this for backward compatibility - replaced by "status":
				'is_complete_success' => $results->is_complete_success(),

				'ajax_arguments' => array(
					'step' => $step_number + 1,
					'phase' => $next_phase,
					'first_phase_step' => $steps_before_association_migration,
					// pass the unchanged options so that they're available for all migration steps
					'options' => $options
				)
			),
			true
		);
	}


	/**
	 * Handle the first migration phase.
	 *
	 * This involves changing database structure.
	 *
	 * @param int $step_number Current step of this phase.
	 * @param Toolset_Relationship_Migration_Controller $migration_controller
	 * @param Toolset_Result_Set $results
	 * @param int $next_phase The ID of the phase that should follow after this step. Must be set to current phase initially.
	 *
	 * @return bool True if the migration should be continued, false otherwise.
	 * @since m2m
	 */
	private function phase_dbdelta( $step_number, $migration_controller, &$results, &$next_phase ) {

		switch ( $step_number ) {
			case self::STEP_MAYBE_DROP_TABLES:

				// We may be required to drop all m2m tables (especially for debugging purposes)
				$results->add( $migration_controller->maybe_drop_m2m_tables() );

				// Stop if there has been a failure
				return $results->is_complete_success();

			case self::STEP_CREATE_TABLES: {

				// First step - create the m2m datbase tables.

				$migration_controller->do_native_dbdelta();

				$results->add( true, __( 'The toolset_associations, toolset_relationships and toolset_post_type_sets tables have been created.', 'wpcf' ) );

				$next_phase = self::PHASE_DEFINITION_MIGRATION;

				// Stop if there has been a failure
				return $results->is_complete_success();
			}
		}

		return false;
	}


	private function get_maintenance_mode_controller() {
		$maintenance = new \OTGS\Toolset\Common\MaintenanceMode\Controller();
		return $maintenance;
	}


	private function enable_maintenance_mode() {
		return $this->get_maintenance_mode_controller()->enable( true, true, true );
	}


	private function disable_maintenance_mode() {
		return $this->get_maintenance_mode_controller()->disable();
	}

}