/**
 * The View block preview component.
 *
 * A "ViewPreview" component is created that is used inside the Toolset View block to handle the previewing of the
 * selected View.
 *
 * @since  2.6.0
 */

import classnames from 'classnames';

const {
	__,
	sprintf,
} = wp.i18n;

const {
	Component,
} = wp.element;

const {
	Spinner,
} = wp.components;

export default class ViewPreview extends Component {
	// constructor( props ) {
	constructor() {
		super( ...arguments );
		// this.resetStateObject = {
		// 	items: [ { ID: 0, post_title: __( 'Loading view' ), className: 'loading' } ],
		// };
		// this.state = this.resetStateObject;
		this.state = {
			fetching: false,
			error: false,
			errorMessage: '',
		};
	}

	render() {
		const {
			fetching,
			error,
			errorMessage,
			//ctPostContent,
		} = this.state;

		if ( fetching ) {
			return <div key="fetching" className={ classnames( this.props.className ) } >
				<div key="loading" className={ classnames( 'wp-block-embed is-loading' ) }>
					<Spinner />
					<p>{ __( 'Loading the View Preview…' ) }</p>
				</div>
			</div>;
		}

		if ( error ) {
			return <div key="error" className={ classnames( this.props.className ) } >
				<div className={ classnames( 'wpv-view-info-warning' ) }>
					{ errorMessage }
				</div>

			</div>;
		}

		if ( ! this.viewExists() ) {
			return <div className={ this.props.className } >
				{ this.returnViewDeleted() }
			</div>;
		}

		return (
			<div className={ this.props.className } >
				{
					this.state.items.length > 0 ? [
						<div key="view-information" className="view-information" >
							<div className={ classnames( 'view-title' ) }>
								<span>{ __( 'View' ) + ': ' + this.state.viewTitle }</span>
							</div>
							<div key="view-purpose" className={ classnames( 'view-purpose' ) }>
								<span>{ this.state.viewPurpose }</span>
							</div>
							{
								'' !== this.state.style ?
									<div key="view-style" className={ classnames( 'view-style' ) }>
										<span>{ this.styles[ this.state.style ] }</span>
									</div> :
									null
							}
						</div>,
						'bootstrap-grid' !== this.state.style ?
							this.returnViewItemsList() :
							this.returnViewItemsBootstrapGrid(),
					] :
						this.returnEmptyPreview()
				}
			</div>
		);
	}

	viewExists() {
		const viewID = this.props.attributes.view.ID;
		const foundInPosts = window.toolset_view_block_strings.published_views.posts.find( function( view ) {
			return view.ID === viewID;
		} );

		const foundInTaxonomy = window.toolset_view_block_strings.published_views.taxonomy.find( function( view ) {
			return view.ID === viewID;
		} );

		const foundInUsers = window.toolset_view_block_strings.published_views.users.find( function( view ) {
			return view.ID === viewID;
		} );

		return foundInPosts || foundInTaxonomy || foundInUsers;
	}

	returnViewDeleted() {
		return <div className={ classnames( 'wpv-view-info-warning' ) }>
			{ sprintf( 'Error while retrieving the View preview. The selected View (ID: %s) was not found.', this.props.attributes.view.ID ) }
		</div>;
	}

	returnEmptyPreview() {
		return <div key="empty-preview" className={ classnames( 'block-preview-list' ) }>
			<div className={ classnames( 'row-fluid' ) }>
				<div className={ classnames( 'span-preset12', 'wpv-view-block-preview' ) }>
					{ __( 'The View returned no items.' ) }
				</div>
			</div>
		</div>;
	}

	returnViewItemsList() {
		return <div key="list-preview" className={ classnames( 'block-preview-list' ) }>
			{ this.returnViewListItems() }
		</div>;
	}

	returnViewItemsBootstrapGrid() {
		return <div key="grid-preview" className={ classnames( 'block-preview-list' ) }>
			{ this.returnViewItemsBootstrapGridItems() }
		</div>;
	}

	returnViewItemsBootstrapGridItems() {
		const columns = this.state.bootstrapGridColumns,
			columnWidth = 12 / parseInt( columns ),
			items = this.state.items,
			itemsPerRow = this.chunkArray( items.slice(), columns ),
			rows = itemsPerRow.map( ( row, index ) => {
				return <div key={ 'row' + index } className={ classnames( 'row-fluid' ) } >
					{
						row.map( ( item, index2 ) => {
							return <div key={ 'item' + index2 } className={ classnames( 'span-preset' + columnWidth, 'wpv-view-block-preview' ) }>
								{ this.getItemTitle( item ) }
							</div>;
						} )
					}
				</div>;
			} );

		return rows;
	}

	returnViewListItems() {
		if ( this.state.hasCustomSearch ) {
			let customSearchMessage = [];
			switch ( this.props.attributes.formDisplay ) {
				case 'form':
					customSearchMessage = [
						<h3 key="form-only-h3">{ __( 'Form only' ) }</h3>,
						<p key="form-only-p">{ __( 'This block will only display the custom search form.' ) }</p>,
					];
					break;
				case 'results':
					customSearchMessage = [
						<h3 key="results-only-h3">{ __( 'Results only' ) }</h3>,
						<p key="results-only-p">{ __( 'This block will only display the custom search results.' ) }</p>,
					];
					break;
				case 'full':
				default:
					customSearchMessage = [
						<h3 key="full-view-h3">{ __( 'Full View' ) }</h3>,
						<p key="full-view-p">{ __( 'This block will display a custom search form and its results.' ) }</p>,
					];
					break;
			}
			return <div className={ classnames( 'row-fluid' ) }>
				<div className={ classnames( 'span-preset12', 'wpv-view-block-preview', 'wpv-long-preview' ) }>
					{ customSearchMessage }
				</div>
			</div>;
		}

		let output;

		if ( this.state.items.length > 3 ) {
			const items = this.state.items.slice( 0, 3 );
			output = items.map( ( item, index ) => {
				return <div key={ 'item' + index } className={ classnames( 'row-fluid' ) }>
					<div className={ classnames( 'span-preset12', 'wpv-view-block-preview' ) }>
						{ this.getItemTitle( item ) }
					</div>
				</div>;
			} );
			output.push( <div key="more" className={ classnames( 'row-fluid', 'more' ) }>
				<div className={ classnames( 'span-preset12', 'wpv-view-block-preview' ) }>
					{ sprintf( 'Plus %s more items', this.state.items.length - 3 ) }
				</div>
			</div>
			);
		} else {
			output = this.state.items.map( ( item, index ) => {
				if ( item.ID === 0 ) {
					return <div key={ 'item' + index } className={ classnames( 'row-fluid', item.className ) }>
						<div className={ classnames( 'span-preset12', 'wpv-view-block-preview' ) }>
							<span></span> { this.getItemTitle( item ) }
						</div>
					</div>;
				}

				return <div key={ 'item' + index } className={ classnames( 'row-fluid' ) }>
					<div className={ classnames( 'span-preset12', 'wpv-view-block-preview' ) }>
						{ this.getItemTitle( item ) }
					</div>
				</div>;
			} );
		}
		return output;
	}

	chunkArray( myArray, chunkSize ) {
		const results = [];

		while ( myArray.length ) {
			results.push( myArray.splice( 0, chunkSize ) );
		}

		return results;
	}

	getItemTitle( item ) {
		if ( 'undefined' !== typeof( item.post_title ) ) {
			return item.post_title;
		} else if ( 'undefined' !== typeof( item.name ) ) {
			return item.name;
		} else if ( 'undefined' !== typeof( item.data.user_login ) ) {
			return item.data.user_login;
		}
		return '';
	}

	getViewInfo( viewId, limit, offset, orderby, order, secondaryOrderby, secondaryOrder ) {
		this.styles = {
			unformatted: __( 'Unformatted' ),
			'bootstrap-grid': __( 'Bootstrap grid' ),
			table: __( 'Table-based grid' ),
			table_of_fields: __( 'Table' ),
			un_ordered_list: __( 'Unordered list' ),
			ordered_list: __( 'Ordered list' ),
		};

		const data = new window.FormData();
		data.append( 'action', window.toolset_view_block_strings.actionName );
		data.append( 'wpnonce', window.toolset_view_block_strings.wpnonce );
		data.append( 'view_id', 'undefined' === typeof viewId ? this.props.attributes.view.ID : viewId );
		data.append( 'limit', 'undefined' === typeof limit ? this.props.attributes.limit : limit );
		data.append( 'offset', 'undefined' === typeof offset ? this.props.attributes.offset : offset );
		data.append( 'orderby', 'undefined' === typeof orderby ? this.props.attributes.orderby : orderby );
		data.append( 'order', 'undefined' === typeof order ? this.props.attributes.order : order );
		data.append( 'secondaryOrderby', 'undefined' === typeof secondaryOrderby ? this.props.attributes.secondaryOrderby : secondaryOrderby );
		data.append( 'secondaryOrder', 'undefined' === typeof secondaryOrder ? this.props.attributes.secondaryOrder : secondaryOrder );

		window.fetch( window.ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
		} ).then( res => res.json() )
			.then( response => {
				let newState = {};
				if (
					0 !== response &&
					response.success
				) {
					const items = response.data.view_output,
						viewTitle = response.data.view_title,
						viewPurpose = response.data.view_purpose,
						style = (
							null !== response.data.view_meta &&
							'undefined' !== typeof response.data.view_meta.style
						) ?
							response.data.view_meta.style :
							'',
						bootstrapGridColumns = (
							null !== response.data.view_meta &&
							'undefined' !== typeof response.data.view_meta.style &&
							'bootstrap-grid' === response.data.view_meta.style
						) ?
							response.data.view_meta.bootstrap_grid_cols :
							0,
						hasCustomSearch = response.data.hasCustomSearch,
						hasSubmit = response.data.hasSubmit,
						hasExtraAttributes = response.data.hasExtraAttributes;

					newState = {
						items,
						viewTitle,
						viewPurpose,
						style,
						bootstrapGridColumns,
						hasCustomSearch,
						hasSubmit,
						hasExtraAttributes,
					};
				} else {
					let message = '';
					if (
						'undefined' !== typeof response.data &&
						'undefined' !== typeof response.data.message ) {
						message = response.data.message;
					} else {
						message = __( 'An error occurred while trying to get the View information.' );
					}

					newState.error = true;
					newState.errorMessage = message;
				}

				newState.fetching = false;

				this.props.onPreviewStateUpdate( newState );
				return this.setState( newState );
			} );
	}

	componentWillMount() {
		if ( this.props.attributes.view.ID ) {
			// If the View is already there, we're loading a saved block, so we need to render
			// a different thing, which is why this doesn't use 'fetching', as that
			// is for when the user is putting in a new url on the placeholder form
			this.setState( { fetching: true } );
			this.getViewInfo();
		}
	}

	componentWillReceiveProps( nextProps ) {
		const newView = this.props.attributes.view.ID !== nextProps.attributes.view.ID;
		const newLimit = this.props.attributes.limit !== nextProps.attributes.limit;
		const newOffset = this.props.attributes.offset !== nextProps.attributes.offset;
		const newOrderby = this.props.attributes.orderby !== nextProps.attributes.orderby;
		const newOrder = this.props.attributes.order !== nextProps.attributes.order;
		const newSecondaryOrderby = this.props.attributes.secondaryOrderby !== nextProps.attributes.secondaryOrderby;
		const newSecondaryOrder = this.props.attributes.secondaryOrder !== nextProps.attributes.secondaryOrder;

		if (
			newView ||
			newLimit ||
			newOffset ||
			newOrderby ||
			newOrder ||
			newSecondaryOrderby ||
			newSecondaryOrder
		) {
			this.setState( {
				fetching: true,
				error: false,
				errorMessage: '',
			} );

			this.getViewInfo(
				nextProps.attributes.view.ID,
				nextProps.attributes.limit,
				nextProps.attributes.offset,
				nextProps.attributes.orderby,
				nextProps.attributes.order,
				nextProps.attributes.secondaryOrderby,
				nextProps.attributes.secondaryOrder
			);
		}
	}
}
