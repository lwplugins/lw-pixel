/**
 * Field shorthands bound to the settings store, so the tabs stay
 * declarative. Every row shows the server's validation messages for its key
 * and renders nothing when the option does not exist.
 */
/**
 * WordPress dependencies
 */
import {
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import FieldErrors from './FieldErrors';
import SettingRow from './SettingRow';

/**
 * Text input row (title + help left, input right).
 *
 * @param {Object}                    props
 * @param {string}                    props.title       Title.
 * @param {Element}                   props.help        Help.
 * @param {Object}                    props.store       Settings store.
 * @param {string}                    props.name        Option key.
 * @param {string}                    props.placeholder Placeholder.
 * @param {boolean}                   props.disabled    Disabled.
 * @param {boolean}                   props.mono        Monospace (IDs).
 * @param {(value: string) => string} props.normalize   Optional value transform on change.
 */
export function TextRow( {
	title,
	help,
	store,
	name,
	placeholder,
	disabled = false,
	mono = false,
	normalize,
} ) {
	if ( ! store.has( name ) ) {
		return null;
	}

	return (
		<SettingRow
			title={ title }
			help={ help }
			errors={ store.errors[ name ] }
		>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				className={ mono ? 'lw-px-mono' : '' }
				label={ title }
				hideLabelFromVision
				disabled={ disabled }
				placeholder={ placeholder }
				autoComplete="off"
				spellCheck={ false }
				value={ String( store.data.options[ name ] ?? '' ) }
				onChange={ ( value ) =>
					store.set( name, normalize ? normalize( value ) : value )
				}
			/>
		</SettingRow>
	);
}

/**
 * Select row.
 *
 * @param {Object}   props
 * @param {string}   props.title    Title.
 * @param {Element}  props.help     Help.
 * @param {Object}   props.store    Settings store.
 * @param {string}   props.name     Option key.
 * @param {Object[]} props.options  { value, label }.
 * @param {boolean}  props.disabled Disabled.
 */
export function SelectRow( { title, help, store, name, options, disabled } ) {
	if ( ! store.has( name ) ) {
		return null;
	}

	return (
		<SettingRow
			title={ title }
			help={ help }
			errors={ store.errors[ name ] }
		>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				disabled={ disabled }
				value={ String( store.data.options[ name ] ?? '' ) }
				options={ options }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

/**
 * Nested labelled input under a switch (thresholds, extensions, URL list).
 *
 * @param {Object}  props
 * @param {string}  props.label       Visible label.
 * @param {Element} props.help        Help under the input.
 * @param {Object}  props.store       Settings store.
 * @param {string}  props.name        Option key.
 * @param {string}  props.placeholder Placeholder.
 * @param {boolean} props.disabled    Disabled (the switch is off).
 * @param {boolean} props.multiline   Textarea instead of an input.
 */
export function NestedField( {
	label,
	help,
	store,
	name,
	placeholder,
	disabled = false,
	multiline = false,
} ) {
	if ( ! store.has( name ) ) {
		return null;
	}

	const Control = multiline ? TextareaControl : TextControl;
	const extra = multiline ? { rows: 4 } : { __next40pxDefaultSize: true };

	return (
		<>
			<Control
				__nextHasNoMarginBottom
				{ ...extra }
				className="lw-px-mono"
				label={ label }
				help={ help }
				disabled={ disabled }
				placeholder={ placeholder }
				spellCheck={ false }
				value={ String( store.data.options[ name ] ?? '' ) }
				onChange={ ( value ) => store.set( name, value ) }
			/>
			<FieldErrors errors={ store.errors[ name ] } />
		</>
	);
}
