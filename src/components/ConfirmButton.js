/**
 * WordPress dependencies
 */
import {
	Button,
	// Core has no stable ConfirmDialog yet (same as the sibling LW admins).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Button that asks first (in-page dialog, never window.confirm).
 *
 * @param {Object}     props
 * @param {string}     props.question    Dialog text.
 * @param {string}     props.confirmText Confirm button text.
 * @param {() => void} props.onConfirm   Runs after confirming.
 * @param {Element}    props.children    Button content.
 */
export default function ConfirmButton( {
	question,
	confirmText,
	onConfirm,
	children,
	...buttonProps
} ) {
	const [ open, setOpen ] = useState( false );

	return (
		<>
			<Button { ...buttonProps } onClick={ () => setOpen( true ) }>
				{ children }
			</Button>
			<ConfirmDialog
				isOpen={ open }
				confirmButtonText={ confirmText }
				onConfirm={ () => {
					setOpen( false );
					onConfirm();
				} }
				onCancel={ () => setOpen( false ) }
			>
				{ question }
			</ConfirmDialog>
		</>
	);
}
