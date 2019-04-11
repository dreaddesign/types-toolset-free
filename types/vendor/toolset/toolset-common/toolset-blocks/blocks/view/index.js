/**
 * Handles the creation and the behavior of the Toolset View block.
 *
 * @since  2.6.0
 */

/**
 * Block dependencies
 */
import icon from './icon';
import Inspector from './inspector/inspector';
import ViewSelect from './inspector/view-select';
import ViewPreview from './view-preview';
import classnames from 'classnames';
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

const name = 'toolset/view';

const settings = {
	title: __( 'View' ),
	description: __( 'Add a Post, User, or Taxonomy View to the editor.' ),
	category: 'widgets',
	icon: icon.blockIcon,
	keywords: [
		__( 'Toolset' ),
		__( 'View' ),
		__( 'Shortcode' ),
	],

	attributes: {
		view: {
			type: 'string',
			default: '',
		},
		hasCustomSearch: {
			type: 'boolean',
			default: false,
		},
		hasSubmit: {
			type: 'boolean',
			default: false,
		},
		hasExtraAttributes: {
			type: 'array',
			default: [],
		},
		formDisplay: {
			type: 'string',
			default: 'full',
		},
		formOnlyDisplay: {
			type: 'string',
			default: 'samePage',
		},
		otherPageID: {
			type: 'string',
			default: '',
		},
		limit: {
			type: 'string',
			default: '-1',
		},
		offset: {
			type: 'string',
			default: '0',
		},
		orderby: {
			type: 'string',
			default: '',
		},
		order: {
			type: 'string',
			default: '',
		},
		secondaryOrderby: {
			type: 'string',
			default: '',
		},
		secondaryOrder: {
			type: 'string',
			default: '',
		},
		queryFilters: {
			type: 'object',
			default: {},
		},
	},

	edit: props => {
		const onChangeLimit = ( value ) => {
			props.setAttributes( { limit: value } );
		};

		const onChangeOffset = value => {
			props.setAttributes( { offset: value } );
		};

		const onChangeOrderby = value => {
			props.setAttributes( { orderby: value } );
		};

		const onChangeOrder = value => {
			props.setAttributes( { order: value } );
		};

		const onChangeSecondaryOrderby = value => {
			props.setAttributes( { secondaryOrderby: value } );
			if ( '' === value ) {
				onChangeSecondaryOrder( '' );
			}
		};

		const onChangeSecondaryOrder = value => {
			props.setAttributes( { secondaryOrder: value } );
		};

		const onChangeView = ( event ) => {
			props.setAttributes( { view: event.target.value } );
		};

		const onChangeFormDisplay = ( value ) => {
			props.setAttributes( { formDisplay: value } );
		};

		const onChangeFormOnlyDisplay = ( value ) => {
			props.setAttributes( { formOnlyDisplay: value } );
		};

		const onChangeOtherPageID = value => {
			props.setAttributes( { otherPageID: value } );
		};

		const onChangeQueryFilters = ( value, filterType ) => {
			const newQueryFilters = Object.assign( {}, props.attributes.queryFilters );
			newQueryFilters[ filterType ] = value;
			props.setAttributes( { queryFilters: newQueryFilters } );
		};

		const onPreviewStateUpdate = ( state ) => {
			props.setAttributes( { hasCustomSearch: state.hasCustomSearch } );
			props.setAttributes( { hasSubmit: state.hasSubmit } );
			if ( JSON.stringify( props.attributes.hasExtraAttributes ) !== JSON.stringify( state.hasExtraAttributes ) ) {
				props.setAttributes( { hasExtraAttributes: state.hasExtraAttributes } );
				if (
					'undefined' !== typeof state.hasExtraAttributes &&
					state.hasExtraAttributes.length <= 0 ) {
					props.setAttributes( { queryFilters: {} } );
				}
			}
		};

		const {
			posts,
			taxonomy,
			users,
		} = window.toolset_view_block_strings.published_views;

		return [
			!! (
				props.focus ||
				props.isSelected
			) && (
				<Inspector
					key="wpv-gutenberg-view-block-render-inspector"
					className={ classnames( 'wp-block-toolset-view-inspector' ) }
					attributes={
						{
							view: props.attributes.view,
							hasCustomSearch: props.attributes.hasCustomSearch,
							hasSubmit: props.attributes.hasSubmit,
							hasExtraAttributes: props.attributes.hasExtraAttributes,
							formDisplay: props.attributes.formDisplay,
							formOnlyDisplay: props.attributes.formOnlyDisplay,
							otherPageID: props.attributes.otherPageID,
							limit: props.attributes.limit,
							offset: props.attributes.offset,
							orderby: props.attributes.orderby,
							order: props.attributes.order,
							secondaryOrderby: props.attributes.secondaryOrderby,
							secondaryOrder: props.attributes.secondaryOrder,
							queryFilters: props.attributes.queryFilters,
						}
					}
					onChangeView={ onChangeView }
					onChangeFormDisplay={ onChangeFormDisplay }
					onChangeFormOnlyDisplay={ onChangeFormOnlyDisplay }
					onChangeLimit={ onChangeLimit }
					onChangeOffset={ onChangeOffset }
					onChangeOrderby={ onChangeOrderby }
					onChangeOrder={ onChangeOrder }
					onChangeSecondaryOrderby={ onChangeSecondaryOrderby }
					onChangeSecondaryOrder={ onChangeSecondaryOrder }
					onChangeOtherPageID={ onChangeOtherPageID }
					onChangeQueryFilters={ onChangeQueryFilters }
				/>
			),
			( '' === props.attributes.view ?
				<Placeholder
					key="view-block-placeholder"
					className={ classnames( 'wp-block-toolset-view' ) }
				>
					<div className="wp-block-toolset-view-placeholder">
						{ icon.blockPlaceholder }
						<p>
							<strong>{ __( 'Toolset View' ) }</strong>
						</p>
					</div>
					<ViewSelect
						attributes={
							{
								posts: posts,
								taxonomy: taxonomy,
								users: users,
								view: props.attributes.view,
							}
						}
						className={ classnames( 'blocks-select-control__input' ) }
						onChangeView={ onChangeView }
					/>
				</Placeholder> :
				<ViewPreview
					key="toolset-view-gutenberg-block-preview"
					className={ classnames( props.className, 'wp-block-toolset-view-preview' ) }
					attributes={
						{
							view: {
								ID: props.attributes.view,
							},
							hasCustomSearch: props.attributes.hasCustomSearch,
							formDisplay: props.attributes.formDisplay,
							limit: props.attributes.limit,
							offset: props.attributes.offset,
							orderby: props.attributes.orderby,
							order: props.attributes.order,
							secondaryOrderby: props.attributes.secondaryOrderby,
							secondaryOrder: props.attributes.secondaryOrder,
						}
					}
					onPreviewStateUpdate={ onPreviewStateUpdate }
				/>
			),
		];
	},
	save: ( props ) => {
		let view = props.attributes.view || '',
			shortcodeStart = '[wpv-view',
			limit = '',
			offset = '',
			orderby = '',
			order = '',
			secondaryOrderby = '',
			secondaryOrder = '',
			target = '',
			queryFilters = '',
			viewDisplay = '';

		const shortcodeEnd = ']';

		// If there's no URL, don't save any inline HTML.
		if ( '' === view ) {
			return null;
		}

		view = ' id="' + view + '"';

		if ( -1 < parseInt( props.attributes.limit ) ) {
			limit = ' limit="' + props.attributes.limit + '"';
		}

		if ( 0 < parseInt( props.attributes.offset ) ) {
			offset = ' offset="' + props.attributes.offset + '"';
		}

		if ( '' !== props.attributes.orderby ) {
			orderby = ' orderby="' + props.attributes.orderby + '"';
		}

		if ( '' !== props.attributes.order ) {
			order = ' order="' + props.attributes.order + '"';
		}

		if ( '' !== props.attributes.secondaryOrderby ) {
			secondaryOrderby = ' orderby_second="' + props.attributes.secondaryOrderby + '"';
		}

		if ( '' !== props.attributes.secondaryOrder ) {
			secondaryOrder = ' order_second="' + props.attributes.secondaryOrder + '"';
		}

		if (
			props.attributes.hasCustomSearch &&
			'form' === props.attributes.formDisplay
		) {
			shortcodeStart = '[wpv-form-view';
			if ( 'samePage' === props.attributes.formOnlyDisplay ) {
				target = ' target_id="self"';
			} else if (
				'otherPage' === props.attributes.formOnlyDisplay &&
				props.attributes.hasSubmit &&
				'' !== props.attributes.otherPageID
			) {
				target = ' target_id="' + props.attributes.otherPageID + '"';
			}
		}

		if (
			props.attributes.hasCustomSearch &&
			'results' === props.attributes.formDisplay
		) {
			target = '';
			viewDisplay = ' view_display="layout"';
		}

		props.attributes.hasExtraAttributes.forEach(
			function( item ) {
				if ( 0 < Object.keys( props.attributes.queryFilters ).length ) {
					queryFilters += ' ' + item.attribute + '="' + props.attributes.queryFilters[ item[ 'filter_type' ] ] + '"';
				}
			}
		);

		return <RawHTML>{ shortcodeStart + view + limit + offset + orderby + order + secondaryOrderby + secondaryOrder + target + viewDisplay + queryFilters + shortcodeEnd }</RawHTML>;
	},
};

if ( 'undefined' !== typeof WPViews ) {
	registerBlockType( name, settings );
}
