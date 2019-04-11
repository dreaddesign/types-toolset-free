/**
 * Handles the creation and the behavior of the Toolset Content Template block.
 *
 * @since  2.6.0
 */

/**
 * Block dependencies
 */
import icon from './icon';
import Inspector from './inspector/inspector';
import classnames from 'classnames';
import CTSelect from './inspector/ct-select';
import CTPreview from './ct-preview';
import './styles/style.scss';
import './styles/editor.scss';

/**
 * Internal block libraries
 */
const {
	__,
} = wp.i18n;

const {
	registerBlockType,
} = wp.blocks;

const {
	Placeholder,
} = wp.components;

const {
	RawHTML,
} = wp.element;

const name = 'toolset/ct';

const settings = {
	title: __( 'Content Template' ),
	description: __( 'Add a Content Template to the editor.' ),
	category: 'widgets',
	icon: icon.blockIcon,
	keywords: [
		__( 'Toolset' ),
		__( 'Content Template' ),
		__( 'Shortcode' ),
	],

	attributes: {
		ct: {
			type: 'string',
			default: '',
		},
	},

	edit: props => {
		const onChangeCT = ( event ) => {
			props.setAttributes( { ct: event.target.value } );
		};

		return [
			!! (
				props.focus ||
				props.isSelected
			) && (
				<Inspector
					key="wpv-gutenberg-ct-block-render-inspector"
					className={ classnames( 'wp-block-toolset-ct-inspector' ) }
					attributes={
						{
							ct: props.attributes.ct,
						}
					}
					onChangeCT={ onChangeCT }
				/>
			),
			(
				'' === props.attributes.ct ?
					<Placeholder
						key="ct-block-placeholder"
						className={ classnames( 'wp-block-toolset-ct' ) }
					>
						<div className="wp-block-toolset-ct-placeholder">
							{ icon.blockPlaceholder }
							<p>
								<strong>{ __( 'Toolset Content Template' ) }</strong>
							</p>
						</div>
						<CTSelect
							attributes={
								{
									ct: props.attributes.ct,
								}
							}
							className={ classnames( 'blocks-select-control__input' ) }
							onChangeCT={ onChangeCT }
						/>
					</Placeholder> :
					<CTPreview
						key="toolset-ct-gutenberg-block-preview"
						className={ classnames( props.className, 'wp-block-toolset-ct-preview' ) }
						attributes={
							{
								ct: {
									post_name: props.attributes.ct,
								},
							}
						}
					/>

			),
		];
	},
	save: ( props ) => {
		let ct = props.attributes.ct || '';
		const shortcodeStart = '[wpv-post-body',
			shortcodeEnd = ']';

		if ( ! ct.length ) {
			return null;
		}

		ct = ' view_template="' + ct + '"';

		return <RawHTML>{ shortcodeStart + ct + shortcodeEnd }</RawHTML>;
	},
};

if ( 'undefined' !== typeof WPViews ) {
	registerBlockType( name, settings );
}
