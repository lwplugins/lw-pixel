/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import Section from '../components/Section';
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
 * Forms: only the form plugins detected on this site, each with its switch.
 * The others are just named, since their switch has no effect until installed.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function FormsTab( { store } ) {
	const { options, meta } = store.data;
	const installed = FORMS.filter( ( [ key ] ) => meta.formsDetected[ key ] );
	const supported = FORMS.map( ( [ , label ] ) => label ).join( ', ' );

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
			{ installed.length > 0 ? (
				<SwitchList>
					{ installed.map( ( [ key, label ] ) => (
						<OptionSwitch
							key={ key }
							store={ store }
							name={ key }
							title={ sprintf(
								/* translators: %s: form plugin name. */
								__( 'Track %s submissions', 'lw-pixel' ),
								label
							) }
						/>
					) ) }
				</SwitchList>
			) : (
				<p className="lw-px-forms__empty">
					{ __(
						'None of the supported form plugins is installed on this site.',
						'lw-pixel'
					) }
				</p>
			) }
			<p className="lw-px-forms__note">
				{ sprintf(
					/* translators: %s: comma-separated list of form plugin names. */
					__(
						'Supported: %s. A form plugin shows up here once it is active, and its tracking starts automatically.',
						'lw-pixel'
					),
					supported
				) }
			</p>
		</Section>
	);
}
