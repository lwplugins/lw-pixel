/**
 * WordPress dependencies
 */
import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';
import { OptionSwitch, SwitchList } from '../components/Switches';

/**
 * Advanced: admin exclusion, debug logging and custom code. Custom code is
 * editable only by users who may post unfiltered HTML; everyone else gets a
 * clearly read-only view (the server would reject the change anyway).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function AdvancedTab( { store } ) {
	const { options, meta } = store.data;
	const canEdit = meta.canUnfilteredHtml;
	const code = ( name, title ) =>
		store.has( name ) && (
			<SettingRow
				key={ name }
				title={ title }
				stacked
				errors={ store.errors[ name ] }
			>
				<TextareaControl
					__nextHasNoMarginBottom
					className="lw-px-mono lw-px-code"
					label={ title }
					hideLabelFromVision
					rows={ 6 }
					spellCheck={ false }
					readOnly={ ! canEdit }
					value={ options[ name ] || '' }
					onChange={ ( value ) => store.set( name, value ) }
				/>
			</SettingRow>
		);

	return (
		<>
			<Section title={ __( 'General', 'lw-pixel' ) }>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="disable_for_admins"
						title={ __(
							'Do not load pixels for users with manage_options capability',
							'lw-pixel'
						) }
						help={ __(
							'Recommended. Prevents your own admin sessions from polluting reports.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
			</Section>
			<Section
				title={ __( 'Custom code', 'lw-pixel' ) }
				description={ __(
					'Raw HTML/JS injected on every page. No sanitization is performed — make sure your code is trusted.',
					'lw-pixel'
				) }
			>
				{ ! canEdit && (
					<Callout tone="warning">
						{ __(
							'Read only: your account may not post unfiltered HTML (on multisite only super admins can), so you cannot change custom code here.',
							'lw-pixel'
						) }
					</Callout>
				) }
				{ code( 'head_code', __( '<head> code', 'lw-pixel' ) ) }
				{ code( 'body_open_code', __( 'After <body>', 'lw-pixel' ) ) }
				{ code( 'footer_code', __( 'Before </body>', 'lw-pixel' ) ) }
			</Section>
		</>
	);
}
