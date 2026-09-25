/**
 * WordPress dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { createInterpolateElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, lock, seen, unseen } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';

/**
 * A write-only secret (access token, API key). The server only tells
 * whether one is set, where it comes from and a hint (last 4 characters).
 * Draft null = keep, a typed value replaces it on Save, "Remove" clears it
 * on Save; a wp-config.php constant pins it (read-only line).
 *
 * @param {Object}  props
 * @param {Object}  props.store       Settings store.
 * @param {string}  props.name        Option key.
 * @param {string}  props.title       Title.
 * @param {Element} props.help        Help.
 * @param {string}  props.placeholder Placeholder when nothing is saved.
 */
export default function SecretRow( { store, name, title, help, placeholder } ) {
	const [ visible, setVisible ] = useState( false );
	const id = useInstanceId( SecretRow, 'lw-px-secret' );

	if ( ! store.has( name ) ) {
		return null;
	}

	const { secrets, locked } = store.data.meta;
	const state = secrets[ name ] || { set: false, source: 'none', hint: '' };
	const draft = store.data.options[ name ];
	const hint = state.hint ? <code>{ state.hint }</code> : null;

	if ( locked[ name ] ) {
		// One read-only line: nothing to edit, so no two-column field row.
		return (
			<p className="lw-px-keyline">
				<strong>{ title }</strong>
				<Icon icon={ lock } size={ 16 } />
				<span>
					{ sprintf(
						/* translators: %s: PHP constant name. */
						__( 'Set in wp-config.php (%s)', 'lw-pixel' ),
						locked[ name ]
					) }
				</span>
				{ hint }
			</p>
		);
	}

	let status = __( 'Nothing saved yet.', 'lw-pixel' );
	if ( draft === '' ) {
		status = __(
			'The saved key will be removed when you save.',
			'lw-pixel'
		);
	} else if ( state.set ) {
		status = createInterpolateElement(
			__( 'Saved: <hint />', 'lw-pixel' ),
			{ hint: hint || <span>•••</span> }
		);
	}

	return (
		<SettingRow
			title={ title }
			htmlFor={ id }
			errors={ store.errors[ name ] }
			help={ help }
		>
			<p className="lw-admin-hint">{ status }</p>
			<div className="lw-admin-inline lw-px-keyrow">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					id={ id }
					label={ title }
					hideLabelFromVision
					type={ visible ? 'text' : 'password' }
					placeholder={
						state.set
							? __( 'Paste a new key to replace it', 'lw-pixel' )
							: placeholder
					}
					autoComplete="off"
					spellCheck={ false }
					value={ draft || '' }
					onChange={ ( value ) => {
						const key = value.trim();
						store.set( name, key === '' ? null : key );
					} }
				/>
				<Button
					__next40pxDefaultSize
					variant="secondary"
					icon={ visible ? unseen : seen }
					label={
						visible
							? __( 'Hide key', 'lw-pixel' )
							: __( 'Show key', 'lw-pixel' )
					}
					isPressed={ visible }
					onClick={ () => setVisible( ! visible ) }
				/>
				{ state.set && draft !== '' && (
					<Button
						__next40pxDefaultSize
						variant="tertiary"
						isDestructive
						onClick={ () => store.set( name, '' ) }
					>
						{ __( 'Remove', 'lw-pixel' ) }
					</Button>
				) }
				{ draft === '' && (
					<Button
						__next40pxDefaultSize
						variant="tertiary"
						onClick={ () => store.set( name, null ) }
					>
						{ __( 'Keep it', 'lw-pixel' ) }
					</Button>
				) }
			</div>
		</SettingRow>
	);
}
