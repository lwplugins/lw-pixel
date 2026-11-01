/**
 * WordPress dependencies
 */
import { Button, Card } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, connection } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import StatusIcon from './StatusIcon';
import { errorMessage } from '../data/api';

/**
 * Connection test state bar: what the test does, the button (a card-level
 * control, so it lives in this bar) and the last result. It checks the
 * SAVED ID and key, so it waits for unsaved edits to be saved.
 *
 * @param {Object}                 props
 * @param {Object}                 props.store     Settings store.
 * @param {string}                 props.idKey     Saved ID option key.
 * @param {string}                 props.secretKey Secret option key.
 * @param {() => Promise<Object>}  props.test      Runs the test, resolves to { ok, status, message }.
 * @param {Object<string, string>} props.hints     Hint texts: idle (what the test does), dirty (unsaved edits), empty (ID or key not saved).
 */
export default function ConnectionTest( {
	store,
	idKey,
	secretKey,
	test,
	hints,
} ) {
	const [ result, setResult ] = useState( null );
	const [ running, setRunning ] = useState( false );
	const { saved, data } = store;
	const dirty = [ idKey, secretKey ].some( ( key ) => store.isDirty( key ) );
	const ready = !! saved[ idKey ] && !! data.meta.secrets[ secretKey ]?.set;

	let hint = hints.idle;
	if ( dirty ) {
		hint = hints.dirty;
	} else if ( ! ready ) {
		hint = hints.empty;
	}

	const run = async () => {
		setRunning( true );
		try {
			setResult( await test() );
		} catch ( e ) {
			setResult( { ok: false, status: 0, message: errorMessage( e ) } );
		}
		setRunning( false );
	};

	return (
		<Card className="lw-admin-section lw-px-statebar">
			<div className="lw-px-statebar__bar">
				<span className="lw-px-statebar__icon" aria-hidden="true">
					<Icon icon={ connection } size={ 24 } />
				</span>
				<div className="lw-px-statebar__text">
					<strong>{ __( 'Test the connection', 'lw-pixel' ) }</strong>
					<span>{ hint }</span>
				</div>
				<Button
					__next40pxDefaultSize
					variant="secondary"
					isBusy={ running }
					disabled={ running || dirty || ! ready }
					accessibleWhenDisabled
					onClick={ run }
				>
					{ __( 'Test connection', 'lw-pixel' ) }
				</Button>
			</div>
			<div aria-live="polite">
				{ result && (
					<div
						className={ `lw-px-result ${
							result.ok ? 'is-ok' : 'is-error'
						}` }
					>
						<StatusIcon
							status={ result.ok ? 'ok' : 'critical' }
							size={ 20 }
						/>
						<div>
							<strong>
								{ result.ok
									? __( 'Connection works', 'lw-pixel' )
									: __( 'Connection failed', 'lw-pixel' ) }
							</strong>
							<p>
								{ result.message }
								{ result.status > 0 &&
									` ${ sprintf(
										/* translators: %d: HTTP status code. */
										__( '(HTTP %d)', 'lw-pixel' ),
										result.status
									) }` }
							</p>
						</div>
					</div>
				) }
			</div>
		</Card>
	);
}
