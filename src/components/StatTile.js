/**
 * Key/value tile (label, big value, detail line).
 *
 * @param {Object}  props
 * @param {string}  props.label  Small caps label.
 * @param {Element} props.value  Big value.
 * @param {Element} props.detail Detail line.
 */
export default function StatTile( { label, value, detail } ) {
	return (
		<div className="lw-admin-tile">
			<span className="lw-admin-tile__label">{ label }</span>
			<span className="lw-admin-tile__value">{ value }</span>
			{ detail && (
				<span className="lw-admin-tile__detail">{ detail }</span>
			) }
		</div>
	);
}
