/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NestedField } from '../components/Fields';
import Section from '../components/Section';
import { OptionSwitch, SwitchList } from '../components/Switches';

/**
 * Events: the standard events and the automatic (behaviour) events. A
 * paired field (thresholds, extensions, URLs) sits under its switch and is
 * editable only while the switch is on.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function EventsTab( { store } ) {
	const { options } = store.data;
	const sw = ( name, title, help, children ) => (
		<OptionSwitch
			store={ store }
			name={ name }
			title={ title }
			help={ help }
		>
			{ children }
		</OptionSwitch>
	);

	return (
		<>
			<Section
				title={ __( 'Standard events', 'lw-pixel' ) }
				description={ __(
					'Sent to every active pixel, each in its own event format.',
					'lw-pixel'
				) }
			>
				<SwitchList>
					{ sw(
						'event_pageview',
						__( 'PageView', 'lw-pixel' ),
						__( 'Fire PageView on every page', 'lw-pixel' )
					) }
					{ sw(
						'event_view_content',
						__( 'ViewContent', 'lw-pixel' ),
						__(
							'Fire ViewContent on singular templates (posts, pages, CPTs)',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_search',
						__( 'Search', 'lw-pixel' ),
						__(
							'Fire Search on the search results page',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_lead',
						__( 'Lead', 'lw-pixel' ),
						__(
							'Fire Lead on form submissions. Choose the form plugins on the Forms screen.',
							'lw-pixel'
						)
					) }
				</SwitchList>
			</Section>
			<Section
				title={ __( 'Auto-tracked events', 'lw-pixel' ) }
				description={ __(
					'Visitor behaviour tracked without any code.',
					'lw-pixel'
				) }
			>
				<SwitchList>
					{ sw(
						'event_scroll',
						__( 'Scroll depth', 'lw-pixel' ),
						__(
							'Fire Scroll events at percentage thresholds',
							'lw-pixel'
						),
						<NestedField
							store={ store }
							name="event_scroll_thresholds"
							label={ __( 'Thresholds (%)', 'lw-pixel' ) }
							help={ __(
								'Comma-separated percentages (1–100).',
								'lw-pixel'
							) }
							placeholder="25,50,75,100"
							disabled={ ! options.event_scroll }
						/>
					) }
					{ sw(
						'event_time_on_page',
						__( 'Time on page', 'lw-pixel' ),
						__(
							'Fire TimeOnPage events at second thresholds',
							'lw-pixel'
						),
						<NestedField
							store={ store }
							name="event_time_thresholds"
							label={ __( 'Thresholds (seconds)', 'lw-pixel' ) }
							help={ __(
								'Comma-separated seconds.',
								'lw-pixel'
							) }
							placeholder="10,30,60,180"
							disabled={ ! options.event_time_on_page }
						/>
					) }
					{ sw(
						'event_download',
						__( 'Downloads', 'lw-pixel' ),
						__( 'Fire Download events on file links', 'lw-pixel' ),
						<NestedField
							store={ store }
							name="event_download_extensions"
							label={ __( 'File extensions', 'lw-pixel' ) }
							help={ __(
								'Comma-separated file extensions to track.',
								'lw-pixel'
							) }
							placeholder="pdf,doc,zip,mp3"
							disabled={ ! options.event_download }
						/>
					) }
					{ sw(
						'event_login',
						__( 'Login', 'lw-pixel' ),
						__( 'Fire Login event when a user logs in', 'lw-pixel' )
					) }
					{ sw(
						'event_signup',
						__( 'Sign-up', 'lw-pixel' ),
						__(
							'Fire CompleteRegistration event on user signup',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_comment',
						__( 'Comment', 'lw-pixel' ),
						__(
							'Fire Comment event on new comment submission',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_click_phone',
						__( 'Phone clicks', 'lw-pixel' ),
						__(
							'Fire a Contact event when a visitor clicks a tel: link',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_click_email',
						__( 'Email clicks', 'lw-pixel' ),
						__(
							'Fire a Contact event when a visitor clicks a mailto: link',
							'lw-pixel'
						)
					) }
					{ sw(
						'event_thankyou',
						__( 'Thank-you pages', 'lw-pixel' ),
						__(
							'Fire a Lead event when the URL matches one of the fragments below',
							'lw-pixel'
						),
						<NestedField
							store={ store }
							name="event_thankyou_urls"
							label={ __( 'URL fragments', 'lw-pixel' ) }
							help={ __(
								'One URL fragment per line, e.g. koszonjuk. Matched case-insensitively against the page address, including any query string.',
								'lw-pixel'
							) }
							disabled={ ! options.event_thankyou }
							multiline
						/>
					) }
				</SwitchList>
			</Section>
		</>
	);
}
