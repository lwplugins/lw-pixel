/**
 * WordPress dependencies
 */
// Same layout primitives as the sibling LW admins (no stable equivalents yet).
/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
	__experimentalHeading as Heading,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import FieldErrors from './FieldErrors';
import StatusBadge from './StatusBadge';

/**
 * Status badge of a switchable card: on and complete, on but missing
 * something, or off.
 *
 * @param {boolean} on       Switched on.
 * @param {boolean} complete Everything it needs is filled in.
 * @param {string}  missing  Label when on but incomplete.
 * @return {Element} Badge.
 */
export function stateBadge( on, complete, missing ) {
	if ( ! on ) {
		return (
			<StatusBadge status="idle">{ __( 'Off', 'lw-pixel' ) }</StatusBadge>
		);
	}
	return complete ? (
		<StatusBadge status="ok">{ __( 'Active', 'lw-pixel' ) }</StatusBadge>
	) : (
		<StatusBadge status="warning">{ missing }</StatusBadge>
	);
}

/**
 * A card with a card-level switch in its header (a provider, or a feature
 * such as the Conversions API): icon, title, status, description, switch.
 *
 * @param {Object}  props
 * @param {Object}  props.store       Settings store.
 * @param {string}  props.name        Option key of the switch.
 * @param {string}  props.title       Heading (also the switch's accessible name).
 * @param {Object}  props.icon        Icon.
 * @param {Element} props.badge       Status badge.
 * @param {Element} props.extra       Extra badge (e.g. "New").
 * @param {string}  props.description Lead text.
 * @param {Element} props.children    Body.
 */
export default function ProviderCard( {
	store,
	name,
	title,
	icon,
	badge,
	extra,
	description,
	children,
} ) {
	if ( ! store.has( name ) ) {
		return null;
	}

	const on = !! store.data.options[ name ];

	return (
		<Card
			className={ `lw-admin-section lw-px-card ${ on ? '' : 'is-off' }` }
		>
			<CardHeader className="lw-px-card__header">
				<div className="lw-px-card__lead">
					{ icon && (
						<span className="lw-px-card__icon" aria-hidden="true">
							<Icon icon={ icon } size={ 22 } />
						</span>
					) }
					<VStack spacing={ 1 }>
						<div className="lw-px-card__title">
							<Heading level={ 3 } size={ 15 }>
								{ title }
							</Heading>
							{ badge }
							{ extra }
						</div>
						{ description && (
							<Text variant="muted">{ description }</Text>
						) }
					</VStack>
				</div>
				<ToggleControl
					__nextHasNoMarginBottom
					className="lw-px-card__switch"
					label={
						on ? __( 'On', 'lw-pixel' ) : __( 'Off', 'lw-pixel' )
					}
					aria-label={ title }
					checked={ on }
					onChange={ ( value ) => store.set( name, value ) }
				/>
			</CardHeader>
			{ ( children || store.errors[ name ] ) && (
				<CardBody>
					<VStack spacing={ 5 }>
						<FieldErrors errors={ store.errors[ name ] } />
						{ children }
					</VStack>
				</CardBody>
			) }
		</Card>
	);
}
