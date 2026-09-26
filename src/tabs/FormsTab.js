/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { Icon } from '@wordpress/components';

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
 * One form plugin switch with its detection badge.
 *
 * @param {Object}  props
 * @param {Object}  props.store     Settings store.
 * @param {string}  props.name      Option key.
 * @param {string}  props.label     Form plugin name.
 * @param {boolean} props.installed Whether the plugin is active here.
 */
function FormSwitch( { store, name, label, installed } ) {
	return (
		<OptionSwitch
			store={ store }
			name={ name }
			title={ sprintf(
				/* translators: %s: form plugin name. */
				__( 'Track %s submissions', 'lw-pixel' ),
				label
			) }
			badge={
				installed ? (
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
	);
}

/**
 * Forms: installed form plugins first; the ones not installed here sit in a
 * collapsed group, since their switch only matters once the plugin exists.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function FormsTab( { store } ) {
	const { options, meta } = store.data;
	const [ open, setOpen ] = useState( false );
	const installed = FORMS.filter( ( [ key ] ) => meta.formsDetected[ key ] );
	const missing = FORMS.filter( ( [ key ] ) => ! meta.formsDetected[ key ] );

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
						<FormSwitch
							key={ key }
							store={ store }
							name={ key }
							label={ label }
							installed
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
			{ missing.length > 0 && (
				<div className="lw-px-forms__missing">
					<button
						type="button"
						className="lw-px-forms__toggle"
						aria-expanded={ open }
						aria-controls="lw-px-forms-missing"
						onClick={ () => setOpen( ! open ) }
					>
						<Icon
							icon={ open ? chevronUp : chevronDown }
							size={ 20 }
						/>
						{ sprintf(
							/* translators: %d: number of form plugins. */
							_n(
								'Not installed (%d)',
								'Not installed (%d)',
								missing.length,
								'lw-pixel'
							),
							missing.length
						) }
					</button>
					<div id="lw-px-forms-missing" hidden={ ! open }>
						<p className="lw-px-forms__note">
							{ __(
								'Tracking starts automatically once you install one of these, while its switch is on.',
								'lw-pixel'
							) }
						</p>
						<SwitchList>
							{ missing.map( ( [ key, label ] ) => (
								<FormSwitch
									key={ key }
									store={ store }
									name={ key }
									label={ label }
									installed={ false }
								/>
							) ) }
						</SwitchList>
					</div>
				</div>
			) }
		</Section>
	);
}
