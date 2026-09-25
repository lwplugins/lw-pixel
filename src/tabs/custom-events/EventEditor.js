/**
 * WordPress dependencies
 */
// Same layout primitives as the sibling LW admins (no stable equivalents yet).
/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	SelectControl,
	TextControl,
	ToggleControl,
	__experimentalHeading as Heading,
	__experimentalVStack as VStack,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { __ } from '@wordpress/i18n';
import { arrowLeft, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ConfirmButton from '../../components/ConfirmButton';
import SettingRow from '../../components/SettingRow';
import { SwitchItem, SwitchList } from '../../components/Switches';
import { triggerOptions } from './labels';

/**
 * One custom event: name, trigger (with only the field that trigger needs),
 * where it fires, optional value, once per session. The run switch is a
 * card-level control in the header.
 *
 * @param {Object} props
 * @param {Object} props.events Custom events store.
 */
export default function EventEditor( { events } ) {
	const { editing, errors } = events;
	const draft = editing.draft;
	const isNew = ! editing.id;
	const text = ( field, props ) => (
		<TextControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			hideLabelFromVision
			autoComplete="off"
			spellCheck={ false }
			value={ String( draft[ field ] ?? '' ) }
			onChange={ ( value ) => events.set( field, value ) }
			{ ...props }
		/>
	);
	const number = ( field, label, min, max ) =>
		text( field, {
			label,
			type: 'number',
			min,
			max,
			step: 1,
			className: 'lw-px-number',
			onChange: ( value ) =>
				events.set( field, value === '' ? '' : Number( value ) ),
		} );

	return (
		<>
			<div className="lw-px-toolbar">
				{ events.hasEdits ? (
					<ConfirmButton
						__next40pxDefaultSize
						variant="tertiary"
						icon={ arrowLeft }
						question={ __(
							'Leave the editor and discard your unsaved changes?',
							'lw-pixel'
						) }
						confirmText={ __( 'Discard changes', 'lw-pixel' ) }
						onConfirm={ events.close }
					>
						{ __( 'All custom events', 'lw-pixel' ) }
					</ConfirmButton>
				) : (
					<Button
						__next40pxDefaultSize
						variant="tertiary"
						icon={ arrowLeft }
						onClick={ events.close }
					>
						{ __( 'All custom events', 'lw-pixel' ) }
					</Button>
				) }
				{ ! isNew && (
					<ConfirmButton
						__next40pxDefaultSize
						variant="tertiary"
						isDestructive
						icon={ trash }
						question={ __(
							'Delete this custom event?',
							'lw-pixel'
						) }
						confirmText={ __( 'Delete', 'lw-pixel' ) }
						onConfirm={ () => events.remove( { id: editing.id } ) }
					>
						{ __( 'Delete', 'lw-pixel' ) }
					</ConfirmButton>
				) }
			</div>
			<Card className="lw-admin-section lw-px-card">
				<CardHeader className="lw-px-card__header">
					<Heading level={ 3 } size={ 15 }>
						{ isNew
							? __( 'New custom event', 'lw-pixel' )
							: draft.event_name || editing.server.event_name }
					</Heading>
					<ToggleControl
						__nextHasNoMarginBottom
						className="lw-px-card__switch"
						label={
							draft.enabled
								? __( 'On', 'lw-pixel' )
								: __( 'Off', 'lw-pixel' )
						}
						aria-label={ __( 'Run this event', 'lw-pixel' ) }
						checked={ !! draft.enabled }
						onChange={ ( value ) => events.set( 'enabled', value ) }
					/>
				</CardHeader>
				<CardBody>
					<VStack spacing={ 5 }>
						<SettingRow
							title={ __( 'Event name', 'lw-pixel' ) }
							help={ __(
								'Generic event name (PascalCase). Each pixel will receive its mapped variant.',
								'lw-pixel'
							) }
							errors={ errors.event_name }
						>
							{ text( 'event_name', {
								label: __( 'Event name', 'lw-pixel' ),
								placeholder: 'MyCustomEvent',
								className: 'lw-px-mono',
							} ) }
						</SettingRow>
						<SettingRow
							title={ __( 'Label', 'lw-pixel' ) }
							help={ __(
								'Optional. Only shown in this list; the event name is used when empty.',
								'lw-pixel'
							) }
							errors={ errors.title }
						>
							{ text( 'title', {
								label: __( 'Label', 'lw-pixel' ),
							} ) }
						</SettingRow>
						<SettingRow
							title={ __( 'Trigger', 'lw-pixel' ) }
							errors={ errors.trigger_type }
						>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Trigger', 'lw-pixel' ) }
								hideLabelFromVision
								value={ draft.trigger_type }
								options={ triggerOptions() }
								onChange={ ( value ) =>
									events.set( 'trigger_type', value )
								}
							/>
						</SettingRow>
						{ draft.trigger_type === 'click' && (
							<SettingRow
								title={ __( 'CSS selector', 'lw-pixel' ) }
								help={ __(
									'The event fires when a visitor clicks an element matching this selector (or anything inside it).',
									'lw-pixel'
								) }
								errors={ errors.selector }
							>
								{ text( 'selector', {
									label: __( 'CSS selector', 'lw-pixel' ),
									placeholder: '.my-button, #cta',
									className: 'lw-px-mono',
								} ) }
							</SettingRow>
						) }
						{ draft.trigger_type === 'scroll' && (
							<SettingRow
								title={ __( 'Scroll percentage', 'lw-pixel' ) }
								help={ __(
									'For "Scroll" trigger (1–100).',
									'lw-pixel'
								) }
								errors={ errors.scroll_pct }
							>
								{ number(
									'scroll_pct',
									__( 'Scroll percentage', 'lw-pixel' ),
									1,
									100
								) }
							</SettingRow>
						) }
						{ draft.trigger_type === 'time' && (
							<SettingRow
								title={ __( 'Time on page (s)', 'lw-pixel' ) }
								help={ __(
									'For "Time on page" trigger.',
									'lw-pixel'
								) }
								errors={ errors.time_seconds }
							>
								{ number(
									'time_seconds',
									__( 'Time on page (s)', 'lw-pixel' ),
									1,
									86400
								) }
							</SettingRow>
						) }
						<SettingRow
							title={ __( 'Page URL pattern', 'lw-pixel' ) }
							help={ __(
								'Where the event should fire. Empty = all pages. Wildcards (*) supported.',
								'lw-pixel'
							) }
							errors={ errors.page_pattern }
						>
							{ text( 'page_pattern', {
								label: __( 'Page URL pattern', 'lw-pixel' ),
								placeholder: '/products/*',
								className: 'lw-px-mono',
							} ) }
						</SettingRow>
						<SettingRow
							title={ __( 'Value', 'lw-pixel' ) }
							help={ __(
								'Optional event value + ISO currency code.',
								'lw-pixel'
							) }
							errors={ [
								...( errors.value || [] ),
								...( errors.currency || [] ),
							] }
						>
							<div className="lw-admin-inline lw-px-value">
								{ text( 'value', {
									label: __( 'Value', 'lw-pixel' ),
									placeholder: '0',
									inputMode: 'decimal',
								} ) }
								{ text( 'currency', {
									label: __( 'Currency', 'lw-pixel' ),
									placeholder: 'USD',
									maxLength: 3,
									className: 'lw-px-currency',
									onChange: ( value ) =>
										events.set(
											'currency',
											value.toUpperCase()
										),
								} ) }
							</div>
						</SettingRow>
						<SwitchList>
							<SwitchItem
								title={ __(
									'Fire only once per browser session',
									'lw-pixel'
								) }
								checked={ !! draft.fire_once }
								changed={
									draft.fire_once !== editing.server.fire_once
								}
								errors={ errors.fire_once }
								onChange={ ( value ) =>
									events.set( 'fire_once', value )
								}
							/>
						</SwitchList>
					</VStack>
				</CardBody>
			</Card>
		</>
	);
}
