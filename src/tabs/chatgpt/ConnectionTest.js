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
import StatusIcon from '../../components/StatusIcon';
import { api, errorMessage } from '../../data/api';

const KEYS = [ 'chatgpt_pixel_id', 'chatgpt_api_key' ];

/**
 * Connection test state bar: what the test does, the button (a card-level
 * control, so it lives in this bar) and the last result. It checks the
 * SAVED pixel ID and key, so it waits for unsaved edits to be saved.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ConnectionTest( { store } ) {
	const [ result, setResult ] = useState( null );
	const [ running, setRunning ] = useState( false );
	const { saved, data } = store;
	const dirty = KEYS.some( ( key ) => store.isDirty( key ) );
	const ready =
		!! saved.chatgpt_pixel_id && !! data.meta.secrets.chatgpt_api_key?.set;

	let hint = __(
		'Sends one sample event with a validate-only flag: OpenAI checks the pixel ID and the key, and records nothing.',
		'lw-pixel'
	);
	if ( dirty ) {
		hint = __(
			'Save your changes first: the test uses the saved pixel ID and key.',
			'lw-pixel'
		);
	} else if ( ! ready ) {
		hint = __(
			'Save a pixel ID and a Conversions API key to run the test.',
			'lw-pixel'
		);
	}

	const run = async () => {
		setRunning( true );
		try {
			setResult( await api.testChatgpt() );
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
