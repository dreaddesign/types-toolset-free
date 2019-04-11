<?php

/**
 * Class Toolset_Admin_Notice_Abstract
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Abstract
 *            All containing properties and methods without since tag are part of the initial release
 */
abstract class Toolset_Admin_Notice_Abstract implements Toolset_Admin_Notice_Interface {

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var int
	 */
	protected $priority = 0;

	/**
	 * @var Toolset_Condition_Interface[]
	 */
	protected $conditions;

	/**
	 * Temporary message
	 * @var bool
	 */
	protected $is_temporary = false;

	/**
	 * By default every of our messages is permanent dismissible
	 *
	 * @var bool
	 */
	protected $is_dismissible_permanent = true;

	/**
	 * $is_dismissible_globally if the message is per user or per installation
	 * e.g. our toolset installer should be per installation (makes no sense to let every user install the site)
	 *
	 * @var bool
	 */
	protected $is_dismissible_globally = false;

	/**
	 * template file
	 */
	protected $template_file;


	/**
	 * @var Toolset_Constants
	 */
	protected $constants;


	/**
	 * Notice is only for administrators
	 *
	 * This is an EXCEPTION of condition being placed directly into notice class
	 * Reason for Exception: We need it for all common notices (no rule without exception) and it's too easy
	 * missing to add the "Toolset_Condition_User_Role_Admin" condition to every new future notice.
	 *
	 * For all other conditions use the ->add_condition() concept.
	 *
	 * @var bool
	 */
	protected $is_only_for_administrators = true;


	/**
	 * @var string
	 */
	private $similar_notices_key;


	/**
	 * @var callable[]
	 */
	private $dependency_callbacks = array();


	/**
	 * @var array|null
	 */
	private $template_context;


	/**
	 * Toolset_Admin_Notice constructor.
	 *
	 * @param string $id
	 * @param string $message
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( $id, $message = '', Toolset_Constants $constants = null ) {

		if ( null === $constants ) {
			$constants = new Toolset_Constants();
		}
		$this->constants = $constants;

		if( ! function_exists( 'sanitize_title' ) ) {
			// abort, called to early
			throw new InvalidArgumentException(
				'Toolset_Admin_Notice_Abstract Error: "sanitize_title()" does not exists. '
				. 'Toolset_Admin_Notice_Abstract::create_notice() was called too early.'
			);
		}

		if( ! is_string( $id ) ) {
			// no string given
			throw new InvalidArgumentException( 'Toolset_Admin_Notice_Abstract Error: $id must be a string.' );
		}

		if( ! empty( $message ) ) {
			$this->set_content( $message );
		}

		$this->id = sanitize_title( $id );

		// set default template file
		$this->set_default_template_file();
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * @param string $key
	 */
	public function set_similar_notices_key( $key ) {
		$this->similar_notices_key = $key;
	}

	/**
	 * @return string
	 */
	public function get_similar_notices_key() {
		return $this->similar_notices_key;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	public function set_content( $content ) {
		if( ! is_string( $content ) ) {
			return false;
		}

		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * Output of string
	 */
	public function render_content() {
		if( is_file( $this->content ) ) {
			include( $this->content );
			return;
		}

		echo $this->content;
	}

	/**
	 * Adds a condition
	 *
	 * @param Toolset_Condition_Interface $condition
	 */
	public function add_condition( Toolset_Condition_Interface $condition ) {
		$this->conditions[] = $condition;
	}

	/**
	 * Sets priority of the message
	 *
	 * @param int $priority
	 */
	public function set_priority( $priority ) {
		if( is_numeric( $priority ) ) {
			$this->priority = $priority;
		}
	}

	/**
	 * @return int
	 */
	public function get_priority( ) {
		return $this->priority;
	}

	/**
	 * True or false
	 * @param bool $bool
	 */
	public function set_is_dismissible_permanent( $bool ) {
		$this->is_dismissible_permanent = $bool === false
			? false
			: true;
	}

	/**
	 * @return bool
	 */
	public function is_dismissible_permanent() {
		return $this->is_dismissible_permanent;
	}

	/**
	 * True or false
	 * @param bool $bool
	 */
	public function set_is_dismissible_globally( $bool ) {
		$this->is_dismissible_globally = $bool === false
			? false
			: true;

		if( $this->is_dismissible_globally ) {
			$this->is_dismissible_permanent = true;
		}
	}

	/**
	 * @return bool
	 */
	public function is_dimissibile_globally() {
		return $this->is_dismissible_globally;
	}

	/**
	 * Getter of is_temporary
	 * @return bool
	 */
	public function is_temporary(){
		return $this->is_temporary;
	}

	/**
	 * Print Notice
	 */
	public function render() {
		if( ! file_exists( $this->template_file ) ) {
			error_log( 'Toolset_Admin_Notice_Abstract Error: Template "'. $this->template_file . '" could not be found.' );
			return;
		}

		$this->run_dependency_callbacks();

		include( $this->template_file );
	}

	abstract protected function set_default_template_file();

	public function conditions_met() {
		if( $this->get_is_only_for_administrators() && ! current_user_can( 'manage_options' ) ) {
			// this notice is only for administrators
			return false;
		}

		if( empty( $this->conditions ) ) {
			// this notice has no conditions
			return true;
		}

		foreach( $this->conditions as $condition ) {
			if( ! $condition->is_met() ) {
				return false;
			}
		}

		// all conditions met
		return true;
	}

	/**
	 * Dismiss notice
	 */
	public function dismiss() {
		if( ! $this->is_dismissible_permanent() ) {
			error_log( 'Notice with id "' . $this->get_id() . '" is not dismissible.' );
			return;
		}

		Toolset_Admin_Notices_Manager::dismiss_notice_by_id( $this->get_id(), $this->is_dimissibile_globally() );
	}

	/**
	 * @return bool
	 */
	public function get_is_only_for_administrators() {
		return $this->is_only_for_administrators;
	}

	/**
	 * @param bool $bool
	 */
	public function set_is_only_for_administrators( $bool ) {
		$this->is_only_for_administrators = $bool === false
			? false
			: true;
	}

	public function set_template_path( $template_path ) {
		if( file_exists( $template_path ) ) {
			$this->template_file = $template_path;
		}
	}

	/**
	 * Template path for a notice with the Toolset Robot
	 */
	public function set_template_toolset_robot() {
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/toolset-robot.phtml';
	}


	/**
	 * @inheritdoc
	 * @param callable $callback
	 * @since 2.8
	 */
	public function add_dependency_callback( $callback ) {
		if( ! is_callable( $callback ) ) {
			throw new InvalidArgumentException();
		}

		$this->dependency_callbacks[] = $callback;
	}


	/**
	 * Run all callbacks previously added via add_dependency_callback().
	 *
	 * @since 2.8
	 */
	public function run_dependency_callbacks() {
		foreach( $this->dependency_callbacks as $callback ) {
			$callback();
		}
	}


	/**
	 * Set a context variable that will be accessible when rendering the notice template.
	 *
	 * @param array $context
	 *
	 * @return void
	 * @since 2.á
	 */
	public function set_template_context( $context ) {
		$this->template_context = toolset_ensarr( $context );
	}


	/**
	 * Get the context variable.
	 *
	 * @return array
	 */
	public function get_template_context() {
		return toolset_ensarr( $this->template_context );
	}

}