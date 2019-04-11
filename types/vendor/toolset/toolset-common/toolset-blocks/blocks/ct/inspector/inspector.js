/**
 * The Content Template block inspector component.
 *
 * An "Inspector" component is created that is used inside the Toolset Content Template block to handle all the functionality related
 * to the controls on the Gutenberg editor sidebar.
 *
 * @since  2.6.0
 */

/**
 * Block dependencies
 */
import CTSelect from './ct-select';
// import QueryFilters from './query-filters';
// import interpolateComponents from 'interpolate-components';

/**
 * Internal block libraries
 */
const {
	__,
	// sprintf,
} = wp.i18n;

const {
	Component,
} = wp.element;

const {
	InspectorControls,
} = wp.blocks;

// const {
// 	SelectControl,
// } = wp.blocks.InspectorControls;

const {
	// PanelBody,
	// PanelRow,
	// Notice,
} = wp.components;

/**
 * Create an Inspector Controls wrapper Component
 */
export default class Inspector extends Component {
	render() {
		const {
			attributes,
			className,
			onChangeCT,
		} = this.props;

		const {
			ct,
		} = attributes;

		// const getCTSelectOptions = () => {
		// 	const newOptions = cts.map(
		// 		item => {
		// 			const rCT = {};
		// 			rCT.value = item.ID;
		// 			rCT.label = item.post_title;
		// 			return rCT;
		// 		}
		// 	);
		//
		// 	newOptions.unshift(
		// 		{
		// 			value: '',
		// 			label: __( 'Select a Content Template' ),
		// 		}
		// 	);
		//
		// 	return newOptions;
		// };

		return (
			<InspectorControls>
				<div className={ className }>
					<h2>{ __( 'Content Template' ) }</h2>
					<CTSelect
						attributes={
							{
								ct: ct,
							}
						}
						className="blocks-select-control__input"
						onChangeCT={ onChangeCT }
					/>
				</div>
			</InspectorControls>
		);
	}
}
