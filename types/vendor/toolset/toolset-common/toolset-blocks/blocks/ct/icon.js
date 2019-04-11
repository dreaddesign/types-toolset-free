/**
 * Create the icon for the Toolset Content Template Gutenberg block.
 *
 * This file basically creates a span element containing all the right class for the Toolset Content Template Gutenberg block
 * icon to be displayed.
 *
 * @since  2.6.0
 */

import classnames from 'classnames';

const blockIcon = <span className={ classnames( 'toolset-gutenberg-block-image', 'toolset-ct-gutenberg-block', 'dashicon', ) }></span>;

const blockPlaceholder = <span className={ classnames( 'toolset-gutenberg-block-placeholder', 'toolset-ct-gutenberg-block', 'dashicon', ) }></span>;

const icon = {
	blockIcon: blockIcon,
	blockPlaceholder: blockPlaceholder,
};

export default icon;
