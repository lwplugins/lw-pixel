/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { copy, download } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import LoadError from '../../components/LoadError';
import Section from '../../components/Section';
import YesNo from '../../components/YesNo';
import { SkeletonRows, SkeletonSection } from '../../components/skeleton';
import { api } from '../../data/api';
import useRemote from '../../data/useRemote';

/**
 * Save text as a file in the browser.
 *
 * @param {string} text     Content.
 * @param {string} filename File name.
 */
function saveFile( text, filename ) {
	const url = window.URL.createObjectURL(
		new window.Blob( [ text ], { type: 'application/json' } )
	);
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = filename;
	document.body.appendChild( link );
	link.click();
	link.remove();
	window.URL.revokeObjectURL( url );
}

/**
 * The system report for support: pixel status and the full JSON (secrets and
 * custom code only as their length), with copy and download.
 */
export default function SystemReportSection() {
	const remote = useRemote( api.systemReport );
	const { createSuccessNotice } = useDispatch( noticesStore );
	const report = remote.data?.report || {};
	const text = JSON.stringify( report, null, 2 );
	const copyRef = useCopyToClipboard( text, () =>
		createSuccessNotice( __( 'Report copied.', 'lw-pixel' ), {
			type: 'snackbar',
		} )
	);

	if ( remote.error ) {
		return <LoadError message={ remote.error } onRetry={ remote.reload } />;
	}

	if ( remote.isLoading ) {
		return (
			<SkeletonSection>
				<SkeletonRows count={ 4 } />
			</SkeletonSection>
		);
	}

	const pixels = Object.entries( report.pixels || {} );
	const date = new Date().toISOString().slice( 0, 10 );

	return (
		<Section
			title={ __( 'System report', 'lw-pixel' ) }
			description={ __(
				'Attach it to a support request. Secrets and custom code are left out (only their length is shown).',
				'lw-pixel'
			) }
			actions={
				<div className="lw-admin-inline">
					<Button
						__next40pxDefaultSize
						variant="secondary"
						icon={ copy }
						ref={ copyRef }
					>
						{ __( 'Copy', 'lw-pixel' ) }
					</Button>
					<Button
						__next40pxDefaultSize
						variant="secondary"
						icon={ download }
						onClick={ () =>
							saveFile(
								text,
								`lw-pixel-system-report-${ date }.json`
							)
						}
					>
						{ __( 'Download', 'lw-pixel' ) }
					</Button>
				</div>
			}
		>
			<div className="lw-px-table-wrap">
				<table className="lw-px-table">
					<caption className="screen-reader-text">
						{ __( 'Pixel status', 'lw-pixel' ) }
					</caption>
					<thead>
						<tr>
							<th scope="col">{ __( 'Pixel', 'lw-pixel' ) }</th>
							<th scope="col">{ __( 'Enabled', 'lw-pixel' ) }</th>
							<th scope="col">
								{ __( 'Configured', 'lw-pixel' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ pixels.map( ( [ id, pixel ] ) => (
							<tr key={ id }>
								<th scope="row">{ pixel.label || id }</th>
								<td>
									<YesNo value={ !! pixel.enabled } />
								</td>
								<td>
									<YesNo value={ !! pixel.configured } />
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</div>
			<pre
				className="lw-px-report"
				tabIndex={ 0 }
				aria-label={ __( 'System report (JSON)', 'lw-pixel' ) }
			>
				{ text }
			</pre>
		</Section>
	);
}
