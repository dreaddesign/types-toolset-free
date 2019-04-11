<?php

/**
 * Factory for Twig dialog boxes
 *
 * @since 2.3
 */
class Toolset_Twig_Dialog_Box_Factory {

	/**
	 * Gets a Twig dialog box instance
	 *
	 * @param string $dialog_id Unique ID (at least within the page) used to reference the dialog in JS.
	 * @param Twig_Environment $twig Prepared Twig environment.
	 * @param array $context Twig context for the dialog template.
	 * @param string $template_name Twig template name that will be recognized by the provided environment.
	 * @param bool $late_register_assets Whether to run late_register_assets() or not.
	 *
	 * @return Toolset_Twig_Dialog_Box
	 * @deprecated Use $this->create() instead.
	 */
	public function get_twig_dialog_box( $dialog_id, $twig, $context, $template_name, $late_register_assets = true ) {
		return new Toolset_Twig_Dialog_Box( $dialog_id, $twig, $context, $template_name, $late_register_assets );
	}

	/**
	 * Gets a Twig dialog box instance
	 *
	 * @param string $dialog_id Unique ID (at least within the page) used to reference the dialog in JS.
	 * @param Twig_Environment $twig_environment Prepared Twig environment.
	 * @param array $context Twig context for the dialog template.
	 * @param string $template_name Twig template name that will be recognized by the provided environment.
	 * @param bool $late_register_assets Whether to run late_register_assets() or not.
	 *
	 * @return Toolset_Twig_Dialog_Box
	 * @since 2.3
	 */
	public function create( $dialog_id, Twig_Environment $twig_environment, $context, $template_name, $late_register_assets = true ) {
		return new Toolset_Twig_Dialog_Box( $dialog_id, $twig_environment, $context, $template_name, $late_register_assets );
	}

}
