/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import FieldErrors from '../components/FieldErrors';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';
import StatusBadge from '../components/StatusBadge';
import { CONSENT_LISTS, categoryOf, withCategory } from '../data/consent';

/**
 * Consent: which LW Cookie category each pixel waits for (stored as the
 * three consent_*_pixels lists).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ConsentTab( { store } ) {
	const { options, meta } = store.data;
	const choices = [
		{ value: 'marketing', label: __( 'Marketing', 'lw-pixel' ) },
		{ value: 'analytics', label: __( 'Analytics', 'lw-pixel' ) },
		{ value: 'functional', label: __( 'Functional', 'lw-pixel' ) },
	];
	const errors = Object.values( CONSENT_LISTS ).flatMap(
		( key ) => store.errors[ key ] || []
	);

	return (
		<>
			{ meta.lwCookieActive ? (
				<Callout>
					{ __(
						'LW Cookie is active: every pixel waits until the visitor accepts its category, also on cached pages.',
						'lw-pixel'
					) }
				</Callout>
			) : (
				<Callout tone="warning">
					{ __(
						'No consent plugin detected. Without LW Cookie (or a consent plugin that uses the lw_pixel_is_category_allowed filter) every pixel fires for every visitor.',
						'lw-pixel'
					) }
				</Callout>
			) }
			<Section
				title={ __( 'Consent categories', 'lw-pixel' ) }
				description={ __(
					'Choose the cookie category each pixel belongs to. A pixel fires only after the visitor accepts that category.',
					'lw-pixel'
				) }
			>
				<FieldErrors errors={ errors } />
				{ meta.pixels.map( ( pixel ) => {
					const current = categoryOf( options, pixel );
					const own = pixel.defaultCategory
						? choices
						: [
								...choices,
								{
									value: '',
									label: __(
										'Not gated (always fires)',
										'lw-pixel'
									),
								},
							];

					return (
						<SettingRow
							key={ pixel.id }
							title={ pixel.label }
							help={
								pixel.enabled ? null : (
									<StatusBadge status="idle">
										{ __( 'Off', 'lw-pixel' ) }
									</StatusBadge>
								)
							}
						>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ pixel.label }
								hideLabelFromVision
								value={ current }
								options={ own }
								onChange={ ( value ) =>
									store.setMany(
										withCategory( options, pixel.id, value )
									)
								}
							/>
						</SettingRow>
					);
				} ) }
			</Section>
		</>
	);
}
