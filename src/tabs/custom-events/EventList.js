/**
 * WordPress dependencies
 */
import { Button, FormToggle } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { pencil, plus, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Callout from '../../components/Callout';
import ConfirmButton from '../../components/ConfirmButton';
import Section from '../../components/Section';
import StatusBadge from '../../components/StatusBadge';
import { triggerSummary } from './labels';

/**
 * Every custom event with its switch, trigger, pages and row actions.
 *
 * @param {Object} props
 * @param {Object} props.events Custom events store.
 */
export default function EventList( { events } ) {
	const { list, busy } = events;
	const enabled = list.events.filter( ( event ) => event.enabled ).length;

	return (
		<Section
			title={ __( 'Custom events', 'lw-pixel' ) }
			description={ __(
				'Fire your own events on page load, on a click, at a scroll depth or after some time on the page. Every active pixel receives them.',
				'lw-pixel'
			) }
			actions={
				<Button
					__next40pxDefaultSize
					variant="primary"
					icon={ plus }
					onClick={ () => events.open( null ) }
				>
					{ __( 'Add custom event', 'lw-pixel' ) }
				</Button>
			}
		>
			{ enabled > list.runLimit && (
				<Callout tone="warning">
					{ sprintf(
						/* translators: %d: maximum number of events. */
						__(
							'Only the first %d switched-on events run on your site.',
							'lw-pixel'
						),
						list.runLimit
					) }
				</Callout>
			) }
			{ list.events.length === 0 ? (
				<p className="lw-admin-muted">
					{ __( 'No custom events yet.', 'lw-pixel' ) }
				</p>
			) : (
				<ul className="lw-px-events">
					{ list.events.map( ( event ) => {
						const name = event.data.event_name || event.title;
						return (
							<li key={ event.id } className="lw-px-events__row">
								<FormToggle
									checked={ event.enabled }
									disabled={ busy === event.id }
									aria-label={ sprintf(
										/* translators: %s: event name. */
										__( 'Run %s', 'lw-pixel' ),
										name
									) }
									onChange={ ( e ) =>
										events.toggle( event, e.target.checked )
									}
								/>
								<div className="lw-px-events__main">
									<div className="lw-px-events__name">
										<strong>{ name }</strong>
										{ event.title &&
											event.title !== name && (
												<span className="lw-admin-hint">
													{ event.title }
												</span>
											) }
										{ ! event.enabled && (
											<StatusBadge status="idle">
												{ __( 'Off', 'lw-pixel' ) }
											</StatusBadge>
										) }
									</div>
									<span className="lw-admin-hint">
										{ triggerSummary( event.data ) }
										{ ' · ' }
										{ event.data.page_pattern ? (
											<code className="lw-admin-code">
												{ event.data.page_pattern }
											</code>
										) : (
											__( 'All pages', 'lw-pixel' )
										) }
									</span>
								</div>
								<div className="lw-px-events__actions">
									<Button
										size="compact"
										variant="tertiary"
										icon={ pencil }
										onClick={ () => events.open( event ) }
									>
										{ __( 'Edit', 'lw-pixel' ) }
										<span className="screen-reader-text">
											{ ` ${ name }` }
										</span>
									</Button>
									<ConfirmButton
										size="compact"
										variant="tertiary"
										isDestructive
										icon={ trash }
										disabled={ busy === event.id }
										label={ sprintf(
											/* translators: %s: event name. */
											__( 'Delete %s', 'lw-pixel' ),
											name
										) }
										question={ sprintf(
											/* translators: %s: event name. */
											__(
												'Delete the custom event "%s"?',
												'lw-pixel'
											),
											name
										) }
										confirmText={ __(
											'Delete',
											'lw-pixel'
										) }
										onConfirm={ () =>
											events.remove( event )
										}
									/>
								</div>
							</li>
						);
					} ) }
				</ul>
			) }
		</Section>
	);
}
