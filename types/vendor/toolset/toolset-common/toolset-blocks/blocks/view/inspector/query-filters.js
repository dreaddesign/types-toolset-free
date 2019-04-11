/**
 * The View block QueryFilters component.
 *
 * A "QueryFilters" component is created that is used inside the Toolset View block Inspector component to handle the
 * case where the selected View needs to support Query Filter controls..
 *
 * @since  2.6.0
 */

/**
 * Internal block libraries
 */
const {
	Component,
} = wp.element;

const {
	TextControl,
} = wp.components;

export default class QueryFilters extends Component {
	render() {
		const {
			attributes,
			onChangeQueryFilters,
		} = this.props;

		const {
			hasExtraAttributes,
			queryFilters,
		} = attributes;

		const output = hasExtraAttributes.map( ( attribute, index ) => {
			return <TextControl
				key={ index }
				label={ attribute.filter_label }
				value={ 'undefined' !== typeof queryFilters[ attribute.filter_type ] ? queryFilters[ attribute.filter_type ] : '' }
				onChange={
					( value ) => {
						onChangeQueryFilters( value, attribute.filter_type );
					}
				}
				help={ attribute.description }
				placeholder={ attribute.placeholder }
			/>;
		} );
		return output;
	}
}
