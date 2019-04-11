/**
 * The View block OptGroup component.
 *
 * An "OptGroup" component is created that is used inside the Toolset View block Inspector component to handle the View
 * selection. A special component is needed in order to support grouping of Posts/Taxonomy/Users Views.
 *
 * @since  2.6.0
 */

/**
 * Internal block libraries
 */
const {
	Component,
} = wp.element;

/**
 * Create an input field Component
 */
export default class OptGroup extends Component {
	render() {
		const { label, items } = this.props.attributes;
		return (
			<optgroup
				label={ label }
			>
				{
					items.map(
						( item ) =>
							<option
								key={ item.ID }
								value={ item.ID }
							>
								{ item.post_title }
							</option>
					)
				}
			</optgroup>
		);
	}
}
