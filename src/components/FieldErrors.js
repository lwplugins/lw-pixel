/**
 * Server (or client) validation messages next to a field.
 *
 * @param {Object}   props
 * @param {string[]} props.errors Messages.
 * @param {string}   props.id     Optional id (for aria-describedby).
 */
export default function FieldErrors( { errors = [], id } ) {
	if ( ! errors.length ) {
		return null;
	}

	return (
		<ul className="lw-admin-fielderror" id={ id }>
			{ errors.map( ( message ) => (
				<li key={ message }>{ message }</li>
			) ) }
		</ul>
	);
}
