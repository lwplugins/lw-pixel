/**
 * WordPress dependencies
 */
import { FormToggle } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import FieldErrors from './FieldErrors';

/**
 * A list of switches. Each switch sits at the start of its own full-width
 * row (never alone in the right column of a two-column row).
 *
 * @param {Object}  props
 * @param {Element} props.children SwitchItem rows.
 */
export function SwitchList( { children } ) {
	return <ul className="lw-px-switches">{ children }</ul>;
}

/**
 * One switch row: switch, title (its label), help, an optional badge and an
 * optional nested field that only matters while the switch is on.
 *
 * @param {Object}                     props
 * @param {string}                     props.title    Setting name.
 * @param {Element}                    props.help     Description.
 * @param {boolean}                    props.checked  Value.
 * @param {(checked: boolean) => void} props.onChange Receives the new boolean.
 * @param {boolean}                    props.disabled Disabled.
 * @param {Element}                    props.badge    Optional badge after the title.
 * @param {string[]}                   props.errors   Validation messages.
 * @param {boolean}                    props.changed  Differs from the saved value.
 * @param {Element}                    props.children Nested field.
 */
export function SwitchItem( {
	title,
	help,
	checked,
	onChange,
	disabled = false,
	badge,
	errors = [],
	changed = false,
	children,
} ) {
	const id = useInstanceId( SwitchItem, 'lw-px-switch' );
	const classes = [
		'lw-px-switch',
		checked ? 'is-on' : 'is-off',
		changed ? 'is-changed' : '',
		errors.length ? 'has-error' : '',
	];

	return (
		<li className={ classes.filter( Boolean ).join( ' ' ) }>
			<FormToggle
				id={ id }
				checked={ checked }
				disabled={ disabled }
				aria-describedby={ help ? `${ id }-help` : undefined }
				onChange={ ( event ) => onChange( event.target.checked ) }
			/>
			<div className="lw-px-switch__body">
				<div className="lw-px-switch__head">
					<label className="lw-px-switch__title" htmlFor={ id }>
						{ title }
					</label>
					{ badge }
				</div>
				{ help && (
					<p className="lw-px-switch__help" id={ `${ id }-help` }>
						{ help }
					</p>
				) }
				<FieldErrors errors={ errors } />
				{ children && (
					<div className="lw-px-switch__nested">{ children }</div>
				) }
			</div>
		</li>
	);
}

/**
 * SwitchItem bound to a settings option. Renders nothing when the option
 * does not exist (an option retired on the server simply disappears).
 *
 * @param {Object}  props
 * @param {Object}  props.store    Settings store.
 * @param {string}  props.name     Option key.
 * @param {string}  props.title    Title.
 * @param {Element} props.help     Help.
 * @param {Element} props.badge    Badge.
 * @param {boolean} props.disabled Disabled.
 * @param {Element} props.children Nested field.
 */
export function OptionSwitch( {
	store,
	name,
	title,
	help,
	badge,
	disabled,
	children,
} ) {
	if ( ! store.has( name ) ) {
		return null;
	}

	return (
		<SwitchItem
			title={ title }
			help={ help }
			badge={ badge }
			disabled={ disabled }
			checked={ !! store.data.options[ name ] }
			changed={ store.isDirty( name ) }
			errors={ store.errors[ name ] }
			onChange={ ( value ) => store.set( name, value ) }
		>
			{ children }
		</SwitchItem>
	);
}
