<?php

if ( ! class_exists( 'Toolset_DialogBoxes', false ) ) {

	/**
	 * Class Toolset_DialogBoxes
	 *
	 * Note: Consider using Toolset_Twig_Dialog_Box if you're using the new Toolset GUI base.
	 */
	class Toolset_DialogBoxes
	{

		const SCRIPT_DIALOG_BOXES = 'ddl-dialog-boxes';

		private $screens;

		public function __construct( $screens = array() ) {
			$this->screens = apply_filters( 'toolset_dialog-boxes_screen_ids', $screens );
			add_filter( 'toolset_add_registered_styles', array( &$this, 'register_styles' ), 10, 1 );
			add_filter( 'toolset_add_registered_script', array( &$this, 'register_scripts' ), 10, 1 );
		}

		function init_screen_render() {

			if ( empty( $this->screens ) ) {
				return;
			}

			$screen = get_current_screen();

			if ( ! in_array( $screen->id, $this->screens ) ) {
				return;
			}

			add_action( 'admin_print_scripts',	array( &$this, 'enqueue_scripts'), 999 );
			add_action( 'admin_footer',			array( &$this,'template'));
		}

		function register_scripts( $scripts ) {
			$scripts['ddl-abstract-dialog']	= new Toolset_Script( 'ddl-abstract-dialog', TOOLSET_COMMON_URL . '/utility/dialogs/js/views/abstract/ddl-abstract-dialog.js', array( 'jquery', 'wpdialogs', Toolset_Assets_Manager::SCRIPT_UTILS ), '0.1', false );
			$scripts[ self::SCRIPT_DIALOG_BOXES ] = new Toolset_Script(
				self::SCRIPT_DIALOG_BOXES,
				TOOLSET_COMMON_URL . '/utility/dialogs/js/views/abstract/dialog-view.js',
				array('jquery', 'ddl-abstract-dialog', 'underscore', 'backbone'),
				'0.1',
				false
			);

			return $scripts;
		}

		function register_styles( $styles ){
			return $styles;
		}

		public function enqueue_scripts() {
			do_action( 'toolset_enqueue_styles',
				array(
					'ddl-dialogs-css',
					'ddl-dialogs-general-css',
					'ddl-dialogs-forms-css',
					Toolset_Assets_Manager::STYLE_FONT_AWESOME,
				)
			);

			do_action( 'toolset_enqueue_scripts', apply_filters( 'ddl-dialog-boxes_enqueue_scripts', array( self::SCRIPT_DIALOG_BOXES ) ) );
		}

		public function template() {
			ob_start();?>
				<script type="text/html" id="ddl-cell-dialog-tpl">
					<div id="js-dialog-dialog-container">
					<div class="ddl-dialog-content" id="js-dialog-content-dialog">
						<?php printf(__('This is %s cell.', 'ddl-layouts'), '{{{ cell_type }}}'); ?>
					</div>

					<div class="ddl-dialog-footer" id="js-dialog-footer-dialog">
						<?php printf(__('This is %s cell.', 'ddl-layouts'), '{{{ cell_type }}}'); ?>
					</div>
					</div>
				</script>
			<?php
			echo ob_get_clean();
		}
	}
}

/*** COMMON CASES ***/
if( ! class_exists( 'Toolset_PopUpBlockerAlert', false ) ) {

	class Toolset_PopUpBlockerAlert extends Toolset_DialogBoxes {

		const POPUP_MESSAGE_OPTION = 'toolset_popup_blocked_dismiss';

		public function template() {
			ob_start(); ?>

			<script type="text/html" id="ddl-generic-dialog-tpl">
				<div id="js-dialog-dialog-container">
					<div class="ddl-dialog-content" id="js-dialog-content-dialog">
						<?php printf(
							__( '%sTo see the preview, you need to allow this page to show popups.%sHow to enable popups in your browser%s', 'ddl-layouts' ),
							'<p>',
							'<br><a href="https://toolset.com/documentation/user-guides/enable-pop-ups-browser/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=enable-pop-ups-browser&utm_term=help-link" title="enable popups" target="_blank">',
							'</a></p>'
						);
						?>
						<p>
							<label for="disable-popup-message"><input type="checkbox"
							                                          name="<?php echo self::POPUP_MESSAGE_OPTION; ?>"
							                                          value="true"
							                                          id="disable-popup-message"> <?php _e( 'Don\'t show this message again', 'ddl-layouts' ); ?>
							</label>
						</p>
					</div>
				</div>
			</script>
			<?php
			echo ob_get_clean();
		}
	}

}
