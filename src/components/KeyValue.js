/**
 * Read-only label → value rows (status blocks), as a description list.
 *
 * @param {Object} props
 * @param {Array}  props.rows [ { label, value, help, key } ] (falsy rows skipped).
 */
export default function KeyValue( { rows } ) {
	return (
		<dl className="lw-admin-kv">
			{ rows.filter( Boolean ).map( ( row ) => (
				<div key={ row.key || row.label } className="lw-admin-kv__row">
					<dt>
						{ row.label }
						{ row.help && (
							<span className="lw-admin-kv__help">
								{ row.help }
							</span>
						) }
					</dt>
					<dd>{ row.value }</dd>
				</div>
			) ) }
		</dl>
	);
}
