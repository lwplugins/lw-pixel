/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import ConfirmButton from '../../components/ConfirmButton';
import LoadError from '../../components/LoadError';
import Section from '../../components/Section';
import StatusBadge from '../../components/StatusBadge';
import YesNo from '../../components/YesNo';
import { SkeletonRows, SkeletonSection } from '../../components/skeleton';
import { api, errorMessage } from '../../data/api';
import useRemote from '../../data/useRemote';

/**
 * A preview value as text (or a yes/no icon for switches and secrets).
 *
 * @param {Object}                           row   Preview row { secret }.
 * @param {string|number|boolean|Array|null} value Value.
 * @return {Element|string} Display.
 */
function display( row, value ) {
	if ( row.secret ) {
		return (
			<YesNo
				value={ !! value }
				yes={ __( 'Set (hidden)', 'lw-pixel' ) }
				no={ __( 'Not set', 'lw-pixel' ) }
			/>
		);
	}
	if ( typeof value === 'boolean' ) {
		return (
			<YesNo
				value={ value }
				yes={ __( 'On', 'lw-pixel' ) }
				no={ __( 'Off', 'lw-pixel' ) }
			/>
		);
	}
	if ( value === null || value === undefined || value === '' ) {
		return (
			<span className="lw-admin-muted">
				{ __( 'empty', 'lw-pixel' ) }
			</span>
		);
	}
	return (
		<code className="lw-admin-code">
			{ Array.isArray( value ) ? value.join( ', ' ) : String( value ) }
		</code>
	);
}

/**
 * One importer card: detection state, what would change, and the import
 * button (asks first; disabled while the settings hold unsaved edits, as
 * the import writes the stored settings).
 *
 * @param {Object}        props
 * @param {Object}        props.migrator Importer from the API.
 * @param {Object}        props.store    Settings store.
 * @param {() => Promise} props.onDone   Called after a successful import.
 */
function Importer( { migrator, store, onDone } ) {
	const [ running, setRunning ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const count = migrator.preview.length;

	const run = async () => {
		setRunning( true );
		try {
			const result = await api.runMigrator( migrator.id );
			createSuccessNotice(
				sprintf(
					/* translators: %d: number of imported settings. */
					_n(
						'Import finished: %d setting updated.',
						'Import finished: %d settings updated.',
						result.updated.length,
						'lw-pixel'
					),
					result.updated.length
				),
				{ type: 'snackbar' }
			);
			await onDone();
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setRunning( false );
	};

	return (
		<Section
			title={ migrator.label }
			badge={
				migrator.available ? (
					<StatusBadge status="ok">
						{ __( 'Detected', 'lw-pixel' ) }
					</StatusBadge>
				) : (
					<StatusBadge status="idle">
						{ __( 'Not detected', 'lw-pixel' ) }
					</StatusBadge>
				)
			}
			description={
				store.hasEdits && count > 0
					? __(
							'Save or discard your changes first: the import writes the saved settings.',
							'lw-pixel'
						)
					: null
			}
			actions={
				migrator.available &&
				count > 0 && (
					<ConfirmButton
						__next40pxDefaultSize
						variant="primary"
						isBusy={ running }
						disabled={ running || store.hasEdits }
						accessibleWhenDisabled
						question={ __(
							'This will overwrite matching LW Pixel options. Continue?',
							'lw-pixel'
						) }
						confirmText={ __( 'Import', 'lw-pixel' ) }
						onConfirm={ run }
					>
						{ sprintf(
							/* translators: %d: number of settings. */
							_n(
								'Import %d setting',
								'Import %d settings',
								count,
								'lw-pixel'
							),
							count
						) }
					</ConfirmButton>
				)
			}
		>
			{ ! migrator.available && (
				<p className="lw-admin-muted">
					{ __(
						'Source plugin is not active and no legacy data was found in the database.',
						'lw-pixel'
					) }
				</p>
			) }
			{ migrator.available && count === 0 && (
				<p className="lw-admin-muted">
					{ __(
						'Nothing to migrate — no matching settings were found.',
						'lw-pixel'
					) }
				</p>
			) }
			{ count > 0 && (
				<div className="lw-px-table-wrap">
					<table className="lw-px-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'LW Pixel option', 'lw-pixel' ) }
								</th>
								<th scope="col">
									{ __( 'Current value', 'lw-pixel' ) }
								</th>
								<th scope="col">
									{ __( 'Will become', 'lw-pixel' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ migrator.preview.map( ( row ) => (
								<tr key={ row.key }>
									<th scope="row">
										<code className="lw-admin-code">
											{ row.key }
										</code>
									</th>
									<td>{ display( row, row.from ) }</td>
									<td>{ display( row, row.to ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</Section>
	);
}

/**
 * Import settings from another plugin.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ImportSection( { store } ) {
	const remote = useRemote( api.migrators );

	if ( remote.error ) {
		return <LoadError message={ remote.error } onRetry={ remote.reload } />;
	}

	if ( remote.isLoading ) {
		return (
			<SkeletonSection>
				<SkeletonRows count={ 3 } />
			</SkeletonSection>
		);
	}

	return (
		<>
			<Section
				title={ __(
					'Import settings from another plugin',
					'lw-pixel'
				) }
				description={ __(
					'Bring your existing pixel configuration over to LW Pixel without retyping every ID.',
					'lw-pixel'
				) }
			/>
			{ ( remote.data.migrators || [] ).map( ( migrator ) => (
				<Importer
					key={ migrator.id }
					migrator={ migrator }
					store={ store }
					onDone={ () =>
						Promise.all( [ store.reload(), remote.reload() ] )
					}
				/>
			) ) }
		</>
	);
}
