<?php

namespace OTGS\Toolset\Common\Interop\Handler;

use OTGS\Toolset\Common\Condition\Installer as installerCondition;
use OTGS\Toolset\Common\Interop\HandlerInterface;

/**
 * Display the Installer's Compatibility Reporting setting wherever necessary.
 *
 * Specifically, we show it:
 * - as a section in Toolset Settings (General tab)
 * - as a first step if the clicks on the Register button on the repository listing page (Commercial tab)
 * - as a orange Toolset notice if the user didn't make any choice yet
 *
 * @package OTGS\Toolset\Common\Interop\Handler
 * @since 2.8
 */
class InstallerCompatibilityReporting implements HandlerInterface {


	/** @var string Slug of the Toolset Settings section */
	const SECTION_SLUG = 'toolset-installer-component-settings';

	/** @var string ID of the Installer repository where we need to show the settings. */
	const REPOSITORY_ID = 'toolset';

	/** @var string ID of the admin notice. */
	const NOTICE_ID = 'toolset-requires-installer-compatibility-reporting-setup';

	// Different contexts used for rendering slightly different output.
	const CONTEXT_TOOLSET_SETTINGS = 'toolset_settings';

	const CONTEXT_REPOSITORY_LISTING = 'repository_listing';

	const CONTEXT_ADMIN_NOTICE = 'admin_notice';


	/** @var installerCondition\IsAvailable */
	private $is_installer_available_condition;


	/** @var installerCondition\IsToolsetSubscriptionValid */
	private $is_toolset_subscription_valid_condition;


	/**
	 * InstallerCompatibilityReporting constructor.
	 *
	 * @param installerCondition\IsAvailable|null $is_installer_available_di
	 * @param installerCondition\IsToolsetSubscriptionValid|null $is_toolset_subscription_valid_di
	 */
	public function __construct(
		installerCondition\IsAvailable $is_installer_available_di = null,
		installerCondition\IsToolsetSubscriptionValid $is_toolset_subscription_valid_di = null
	) {
		$this->is_installer_available_condition = $is_installer_available_di ?: new installerCondition\IsAvailable();
		$this->is_toolset_subscription_valid_condition = $is_toolset_subscription_valid_di ?: new installerCondition\IsToolsetSubscriptionValid();
	}


	/**
	 * Hook into proper actions.
	 *
	 * This expects to be run during Toolset Common initialization.
	 */
	public function initialize() {

		// Do nothing if there is no installer.
		if( ! $this->is_installer_available_condition->is_met() ) {
			return;
		}

		$that = $this;
		$is_subscription_valid_condition = $this->is_toolset_subscription_valid_condition;

		// Add the options for sending compatibility reports to the Toolset repository on the Installer's Commercial
		// Plugins page.
		add_filter( 'otgs_installer_repository_registration_steps', function ( $registration_steps, $repository_id ) use ( $that ) {
			if ( self::REPOSITORY_ID === $repository_id ) {
				$content = __( 'Choose if Toolset plugins should send reports about the active theme and plugins to toolset.com.', 'wpv-views' )
					. $that->build_content( self::CONTEXT_REPOSITORY_LISTING )
					. '* ' . __( 'These reports, if you decide to send them, will allow Toolset team to give you faster support. We will also use this information to send you alerts about potential compatibility issues with the theme and plugins that you use.', 'wpv-views' );

				array_unshift( $registration_steps, $content );
			}

			return $registration_steps;
		}, 10, 2 );

		// Add a custom settings section.
		// Note the priority. 9 means it will come before other usually present sections.
		add_filter(
			'toolset_filter_toolset_register_settings_general_section',
			function ( $sections ) use ( $that, $is_subscription_valid_condition ) {
				if ( $is_subscription_valid_condition->is_met() ) {
					$sections = $that->build_section( $sections );
				}
				return $sections;
			},
			9
		);

		// Show the admin notice if the setting is still missing and the site has a valid Toolset subscription.
		// If the subscription is not valid, the user will need to go through the registration steps will
		// most probably save the setting there.
		add_action( 'admin_init', function () use ( $that, $is_subscription_valid_condition ) {
			if ( ! $that->has_setting() && $is_subscription_valid_condition->is_met() ) {
				$that->show_notice();
			}
		} );
	}


	/**
	 * Build the content of the setting section, coming from Installer.
	 *
	 * Note that this may also cause Installer to enqueue some assets.
	 *
	 * @param string $context One of the CONTEXT_ constants.
	 *
	 * @return string HTML markup.
	 */
	public function build_content( $context ) {
		ob_start();

		do_action(
			'otgs_installer_render_local_components_setting',
			array(
				'plugin_name' => 'Toolset',
				'use_styles' => true, //Styles not defined yet
				'use_radio' => true,
				'privacy_policy_url' => $this->get_privacy_policy_url( $context ),
				'plugin_uri' => 'https://toolset.com',
				'plugin_repository' => self::REPOSITORY_ID,
			)
		);

		return ob_get_clean();

	}


	/**
	 * @param string $context One of the CONTEXT_ constants.
	 *
	 * @return string URL
	 */
	private function get_privacy_policy_url( $context ) {
		switch ( $context ) {
			case self::CONTEXT_ADMIN_NOTICE:
				$utm_medium = 'compatibility-message';
				break;
			case self::CONTEXT_REPOSITORY_LISTING:
				$utm_medium = 'commercial-tab';
				break;
			case self::CONTEXT_TOOLSET_SETTINGS:
			default:
				$utm_medium = 'compatibility-settings';
				break;
		}

		return sprintf(
			'https://toolset.com/privacy-policy-and-gdpr-compliance/?utm_source=typesplugin&utm_campaign=compatibility-reporting&utm_medium=%s&utm_term=privacy-policy-and-gdpr-compliance',
			$utm_medium
		);
	}


	/**
	 * Add a new section to the General tab of Toolset Settings.
	 *
	 * See toolset_filter_toolset_register_settings_general_section for details.
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function build_section( $sections ) {
		$sections[ self::SECTION_SLUG ] = array(
			'slug' => self::SECTION_SLUG,
			'title' => __( 'Fast support and compatibility alerts', 'wpv-views' ),
			'content' => $this->build_content( self::CONTEXT_TOOLSET_SETTINGS ),
		);

		return $sections;
	}


	/**
	 * Check whether the user had aleady made a choice about the compatibility reporting.
	 *
	 * @return bool
	 */
	public function has_setting() {
		return (bool) apply_filters( 'otgs_installer_has_local_components_setting', null, 'toolset' );
	}


	/**
	 * Build and register the admin notice that alerts about the missing setting of compatibility reporting.
	 */
	public function show_notice() {
		$builder = new \OTGS\Toolset\Common\Utility\Admin\Notices\Builder();

		$notice = $builder->createNotice( self::NOTICE_ID, 'undismissible' );
		$notice->set_is_only_for_administrators( true );
		$notice->set_template_path( TOOLSET_COMMON_PATH . '/templates/admin/notice/installer/compatibility-reporting-setting-needed.phtml' );
		$notice->set_template_context( array(
			'policy_url' => $this->get_privacy_policy_url( self::CONTEXT_ADMIN_NOTICE ),
		) );

		$that = $this;

		// Enqueue assets only if the notice is actually about to be rendered.
		$notice->add_dependency_callback( function () use ( $that ) {
			$asset_manager = \Toolset_Assets_Manager::get_instance();
			$asset_manager->enqueue_styles( \Toolset_Assets_Manager::STYLE_FONT_AWESOME );
			$asset_manager->enqueue_scripts( \Toolset_Assets_Manager::SCRIPT_UTILS );
			add_action( 'admin_print_footer_scripts', array( $that, 'print_notice_script' ) );
		} );

		$builder->addNotice( $notice );
	}


	/**
	 * Print additional script that controls the admin notice behaviour.
	 *
	 * - Allow to save the setting via yes/no buttons
	 * - Hide the notice when the setting is saved, via AJAX or elsewhere on the page
	 * - Reload the page if the setting was saved from the notice but there are other instances
	 *   of the setting elsewhere on the page.
	 *
	 * Note: Deliberately not putting this into a template, to keep all the functionality in a single class.
	 * Basically, it handles a special case and this way we don't have to think about this case outside
	 * of the class.
	 */
	public function print_notice_script() {

		// Fallback in case changes have been made in Installer. In the worst case, the action will not work.
		$action_name = 'otgs_save_setting_share_local_components';
		if ( defined( '\OTGS_Installer_WP_Components_Setting_Ajax::AJAX_ACTION' ) ) {
			$action_name = \OTGS_Installer_WP_Components_Setting_Ajax::AJAX_ACTION;
		}

		$nonce = wp_create_nonce( $action_name );

		?>
		<script type="text/javascript">
            jQuery(document).ready(function () {

                var hideNotice = function () {
                    jQuery('div.toolset-notice-installer-compatibility-setting').fadeOut(500);
                };

                var applyChoice = function (choice) {
                    var $spinner = jQuery('.toolset-notice-installer-compatibility-setting__spinner');
                    WPV_Toolset.Utils.Spinner.show($spinner);

                    var finishProcess = function () {
                        var hasAnotherSettingOnPage = (0 < jQuery('.otgs-installer-component-setting input[type="radio"]').length);
                        // Reload the page if it contains the same setting in a different GUI. That way, that GUI will get in sync
                        // with the updated option. Not the most fancy solution, but this will happen very very rarely.
                        if (hasAnotherSettingOnPage) {
                            location.reload();
                        } else {
                            WPV_Toolset.Utils.Spinner.hide($spinner);
                        }
                    };

                    var failCallback = function () {
                        jQuery('.toolset-notice-installer-compatibility-setting__error').text('<?php _e( 'An error happened when saving the setting.', 'wpv-views' ); ?>');
                        finishProcess();
                    };

                    // Store the user's choice. Using directly an AJAX call in Installer.
                    jQuery.post({
                        async: true,
                        url: ajaxurl,
                        data: {
                            action: '<?php echo $action_name ?>',
                            nonce: '<?php echo $nonce ?>',
                            agree: (choice ? 1 : 0),
                            repo: '<?php echo self::REPOSITORY_ID ?>'
                        },
                        success: function () {
                            // Unfortunately, the AJAX call from Installer doesn't return any result. We have to hope.
                            hideNotice();
                            finishProcess();
                        },
                        error: failCallback
                    })
                };

                jQuery(document).on('click', '.toolset-notice-installer-compatibility-setting__accept-btn', function (e) {
                    e.preventDefault();
                    applyChoice(true);
                });

                jQuery(document).on('click', '.toolset-notice-installer-compatibility-setting__decline-btn', function (e) {
                    e.preventDefault();
                    applyChoice(false);
                });

                // If the user makes the choice in another place, e.g. the Commercial tab or Toolset Settings, we just hide the notice.
                jQuery(document).on('click', '.otgs-installer-component-setting input[type="radio"]', function () {
                    hideNotice();
                });
            });
		</script>
		<?php
	}

}