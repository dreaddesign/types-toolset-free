<?php

namespace OTGS\Toolset\Common\Utility\Admin\Notices;

/**
 * Class Builder
 *
 * Could also be called Toolset_Admin_Notices_Manager V2 or "the non static version of Toolset_Admin_Notices_Manager".
 *
 * @package OTGS\Toolset\Common\Utility\Admin\Notices
 *
 * @since 3.0
 */
class Builder {
	/**
	 * Create a notice object
	 *
	 * @param $id
	 * @param string $type
	 *
	 * @return \Toolset_Admin_Notice_Abstract
	 */
	public function createNotice( $id, $type = 'success' ) {
		switch( $type ) {
			case 'success':
				return new \Toolset_Admin_Notice_Success( $id );
			case 'error':
				return new \Toolset_Admin_Notice_Error( $id );
			case 'warning':
				return new \Toolset_Admin_Notice_Warning( $id );
			case 'dismissible':
				return new \Toolset_Admin_Notice_Dismissible( $id );
			case 'undismissible':
				return new \Toolset_Admin_Notice_Undismissible( $id );
			case 'required-action':
				return new \Toolset_Admin_Notice_Dismissible( $id );
			case 'layouts-help':
				return new \Toolset_Admin_Notice_Layouts_Help( $id );
			default:
				return new \Toolset_Admin_Notice_Error( $id );
		}
	}

	/**
	 * Primary Button
	 *
	 * @param $title
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function tplButtonPrimary( $title, $attributes = array() ) {
		return $this->tplCreateButton( 'toolset-button toolset-button-primary', $title, $attributes );
	}

	/**
	 * Button
	 *
	 * @param $title
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function tplButton( $title, $attributes = array() ) {
		return $this->tplCreateButton( 'toolset-button', $title, $attributes );
	}

	/**
	 * @param \Toolset_Admin_Notice_Abstract $notice
	 * @param string $content
	 */
	public function addNotice( \Toolset_Admin_Notice_Abstract $notice, $content = '' ) {
		\Toolset_Admin_Notices_Manager::add_notice( $notice, $content );
	}

	/**
	 * Internal function to create a button
	 *
	 * @param $tpl_class
	 * @param $title
	 * @param $attributes
	 *
	 * @return string
	 */
	private function tplCreateButton( $tpl_class, $title, $attributes ) {
		$btnClasses = esc_attr( $tpl_class );
		$title      = esc_attr( $title );

		if( ! is_array( $attributes ) ) {
			throw new \InvalidArgumentException(
				'OTGS\Toolset\Common\Utility\Admin\Notices\Builder::tplButtonPrimary()' .
				' - Second parameter must be an array.' );
		}

		// define html element based on href
		$html_element = isset( $attributes['href'] )
			? 'a'
			: 'span';

		// attribute class
		$attributes['class'] = isset( $attributes['class'] )
			? $btnClasses . ' ' . esc_attr( $attributes['class'] )
			: $btnClasses;

		// stringify all attributes
		$attributes_string = '';
		foreach( $attributes as $key => $value ) {
			$attributes_string .= ' ' . esc_attr( $key ). '="' .  esc_attr( $value ) . '"';
		}

		// html link
		return '<' . $html_element . $attributes_string . '>' . $title . '</' . $html_element . '>';
	}
}