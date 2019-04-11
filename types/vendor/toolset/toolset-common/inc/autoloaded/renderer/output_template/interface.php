<?php

/**
 * Interface for output templates.
 *
 * See Toolset_Renderer for detailed usage instructions.
 *
 * @since 2.5.9
 */
interface IToolset_Output_Template {


	/**
	 * @return string Full path to the template file.
	 */
	public function get_full_path();


	/**
	 * @return string Name of the template.
	 */
	public function get_name();
}