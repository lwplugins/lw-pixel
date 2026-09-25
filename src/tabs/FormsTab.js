/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import Section from '../components/Section';
import StatusBadge from '../components/StatusBadge';
import { OptionSwitch, SwitchList } from '../components/Switches';

// Form plugin names are product names: not translated.
const FORMS = [
	[ 'form_cf7', 'Contact Form 7' ],
	[ 'form_wpforms', 'WPForms' ],
	[ 'form_elementor', 'Elementor Pro Forms' ],
	[ 'form_gravityforms', 'Gravity Forms' ],
	[ 'form_forminator', 'Forminator' ],
	[ 'form_formidable', 'Formidable Forms' ],
	[ 'form_ninjaforms', 'Ninja Forms' ],
	[ 'form_fluentforms', 'Fluent Forms' ],
	[ 'form_wsform', 'WS Form' ],
];

/**
 * Forms: which form plugins fire a Lead on submission, with a badge telling
 * whether each plugin is active on this site.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function FormsTab( { store } ) {
	const { options, meta } = store.data;
	const detected = FORMS.filter( ( [ key ] ) => meta.formsDetected[ key ] );
	const rows = [
		...detected,
		...FORMS.filter( ( [ key ] ) => ! meta.formsDetected[ key ] ),
	];

	return (
		<Section
			title={ __( 'Form integrations', 'lw-pixel' ) }
			description={ __(
				'A successful submission fires a Lead event, also when the form is sent without reloading the page.',
				'lw-pixel'
			) }
		>
			{ ! options.event_lead && (
				<Callout tone="warning">
					{ __(
						'The Lead event is switched off on the Events screen, so form submissions are not tracked.',
						'lw-pixel'
					) }{ ' ' }
					<a href="#events">{ __( 'Open Events', 'lw-pixel' ) }</a>
				</Callout>
			) }
			<SwitchList>
				{ rows.map( ( [ key, label ] ) => (
					<OptionSwitch
						key={ key }
						store={ store }
						name={ key }
						title={ sprintf(
							/* translators: %s: form plugin name. */
							__( 'Track %s submissions', 'lw-pixel' ),
							label
						) }
						badge={
							meta.formsDetected[ key ] ? (
								<StatusBadge status="ok">
									{ __( 'Active', 'lw-pixel' ) }
								</StatusBadge>
							) : (
								<StatusBadge status="idle">
									{ __( 'Not installed', 'lw-pixel' ) }
								</StatusBadge>
							)
						}
					/>
				) ) }
			</SwitchList>
		</Section>
	);
}
