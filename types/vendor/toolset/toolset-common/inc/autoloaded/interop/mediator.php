<?php

namespace OTGS\Toolset\Common\Interop;

use OTGS\Toolset\Common\Interop\Handler as handler;

/**
 * Interoperability mediator.
 *
 * Handle any interop tasks with non-Toolset software - including Installer, but excluding WPML (WPML plugins are
 * handled in the Toolset_WPML_Compatibility class).
 *
 * @package OTGS\Toolset\Common\Interop
 * @since 2.8
 */
class Mediator {

	public function initialize() {
		// If the class gets more complex, split it into subclasses, using the same design as in Types_Interop_Mediator.
		$installer_compatibility_reporting = new handler\InstallerCompatibilityReporting();
		$installer_compatibility_reporting->initialize();
	}

}