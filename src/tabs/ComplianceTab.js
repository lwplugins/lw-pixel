/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import { SelectRow } from '../components/Fields';
import Section from '../components/Section';
import { OptionSwitch, SwitchList } from '../components/Switches';

/**
 * Compliance: medical traffic mode and Meta Limited Data Use.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ComplianceTab( { store } ) {
	const { options, meta } = store.data;
	const labels = {
		auto: __( 'Auto (Meta resolves geo)', 'lw-pixel' ),
		force_california: __( 'Force California', 'lw-pixel' ),
	};

	return (
		<>
			<Section
				title={ __( 'Medical traffic mode', 'lw-pixel' ) }
				description={ __(
					'Strips identifiable parameters (IP, UA, advanced matching, page URL params) from event payloads. Recommended for healthcare / medical sites.',
					'lw-pixel'
				) }
			>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="compliance_medical"
						title={ __(
							'Treat this site as medical / health traffic',
							'lw-pixel'
						) }
					/>
				</SwitchList>
			</Section>
			<Section
				title={ __( 'Limited Data Use (LDU)', 'lw-pixel' ) }
				description={ __(
					'Adds the Meta data_processing_options block to CAPI events for California (CCPA) compliance.',
					'lw-pixel'
				) }
			>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="compliance_ldu"
						title={ __( 'Mark events with LDU', 'lw-pixel' ) }
					/>
				</SwitchList>
				<SelectRow
					store={ store }
					name="compliance_ldu_mode"
					title={ __( 'LDU mode', 'lw-pixel' ) }
					help={ __(
						'"Auto" lets Meta resolve geo. "Force California" sets country=1, state=1000.',
						'lw-pixel'
					) }
					disabled={ ! options.compliance_ldu }
					options={ meta.lduModes.map( ( value ) => ( {
						value,
						label: labels[ value ] || value,
					} ) ) }
				/>
				{ options.compliance_ldu && ! options.fb_capi_enabled && (
					<Callout>
						{ __(
							'LDU applies to Meta Conversion API events, which are switched off on the Meta screen.',
							'lw-pixel'
						) }
					</Callout>
				) }
			</Section>
		</>
	);
}
