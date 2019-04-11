/**
 * Block dependencies
 //  */
import classnames from 'classnames';
import './styles/editor.scss';

/**
 * Internal block libraries
 */
const {
	__,
} = wp.i18n;

const {
	addFilter,
} = wp.hooks;

const {
	BlockControls,
} = wp.blocks;

const {
	createElement,
} = wp.element;

addFilter(
	'blocks.BlockEdit',
	'toolset/extend-html',
	( BlockEdit ) => {
		const modifyCustomHTMLBlock = ( props ) => {
			const clonedProps = Object.assign( {}, props, { key: 'toolset-extended-html-block' } );

			let element = createElement( BlockEdit, clonedProps );
			if (
				(
					props.focus ||
					props.isSelected
				) &&
				'core/html' === props.name
			) {
				const updateContentAttributeAfterShortcodeInsertion = () => {
					if (
						'undefined' !== typeof window.currentBlockProps &&
						null !== window.currentBlockProps
					) {
						window.currentBlockProps.setAttributes( { content: document.getElementById( 'toolset-extended-html-' + window.currentBlockProps.id ).value } );
						window.currentBlockProps = null;
					}
				};

				const fieldsAndViewsButton = <BlockControls key="toolset-controls">
					<div className={ classnames( 'components-toolbar' ) }>
						<button
							className={ classnames( 'components-button wpv-block-button' ) }
							onClick={ ( e ) => {
								window.currentBlockProps = props;
								window.wpcfActiveEditor = 'toolset-extended-html-' + props.id;
								// Add an id to the Custom HTML text area to use it when inserting the Fields and Views shortcode.
								e.target.closest( '.editor-block-contextual-toolbar' ).nextSibling.querySelector( 'textarea' ).id = window.wpcfActiveEditor;
								// Open the Fields and Views dialog
								window.WPViews.shortcodes_gui.open_fields_and_views_dialog();
							} }>
							<i className={ classnames( 'icon-views-logo', 'fa', 'fa-wpv-custom', 'ont-icon-18', 'ont-color-gray' ) }></i>
							<span> { __( 'Fields and Views' ) }</span>
						</button>
					</div>
				</BlockControls>;

				window.Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-do-action', updateContentAttributeAfterShortcodeInsertion );

				element = [ element, fieldsAndViewsButton ];
			}
			return element;
		};

		return modifyCustomHTMLBlock;
	}
);
