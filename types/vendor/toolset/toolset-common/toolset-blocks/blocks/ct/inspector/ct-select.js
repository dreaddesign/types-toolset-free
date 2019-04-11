/**
 * The ContentTemplate block CTSelect component.
 *
 * A "CTSelect" component is created that is used inside the Toolset Content Template block Inspector component to handle
 * the CT selection.
 *
 * @since  2.6.0
 */

/**
 * Block dependencies
 */

const {
	__,
} = wp.i18n;

const {
	Component,
} = wp.element;

export default class CTSelect extends Component {
	render() {
		const {
			attributes,
			className,
			onChangeCT,
		} = this.props;

		const {
			ct,
		} = attributes;

		const cts = Object.values( window.toolset_ct_block_strings.published_cts );

		return (
			'undefined' !== typeof cts &&
			cts.length > 0 ?
				// eslint-disable-next-line jsx-a11y/no-onchange
				<select
					onChange={ onChangeCT }
					value={ ct }
					className={ className }
				>
					<option disabled="disabled" value="">{ __( 'Select a Content Template' ) }</option>
					{
						cts.map(
							( item ) =>
								<option
									key={ item.post_name }
									value={ item.post_name }
								>
									{ item.post_title }
								</option>
						)
					}
				</select> :
				<select
					disabled="disabled"
					className={ className }
				>
					<option>{ __( 'Create a Content Template first' ) }</option>
				</select>
		);
	}
}
