/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, lock } from '@wordpress/icons';

/**
 * Two-column settings row: title + help on the left, the control on the right.
 * Controls inside hide their own label from sight (hideLabelFromVision) or are
 * tied to the title through `htmlFor`, so each field has one accessible name.
 *
 * A row bound to an option (`name`) also shows the wp-config.php lock hint
 * and the server's validation messages for that key.
 *
 * @param {Object}   props
 * @param {string}   props.title    Visible title.
 * @param {Element}  props.help     Description under the title.
 * @param {string}   props.htmlFor  Id of the control the title labels.
 * @param {boolean}  props.stacked  Control goes under the text (wide controls).
 * @param {string}   props.locked   Constant pinning the key in wp-config.php, or ''.
 * @param {string[]} props.errors   Server validation messages.
 * @param {Element}  props.children The control(s).
 */
export default function SettingRow( {
	title,
	help,
	htmlFor,
	stacked = false,
	locked = '',
	errors = [],
	children,
} ) {
	const Title = htmlFor ? 'label' : 'span';
	const errorId = htmlFor ? `${ htmlFor }-errors` : undefined;

	return (
		<div
			className={ `lw-admin-row ${ stacked ? 'is-stacked' : '' } ${
				errors.length ? 'has-error' : ''
			}` }
		>
			<div className="lw-admin-row__text">
				<Title className="lw-admin-row__title" htmlFor={ htmlFor }>
					{ title }
				</Title>
				{ help && <p className="lw-admin-row__help">{ help }</p> }
			</div>
			<div className="lw-admin-row__control">
				{ children }
				{ locked && (
					<p className="lw-admin-locked">
						<Icon icon={ lock } size={ 16 } />
						<span>
							{ __( 'Set in wp-config.php', 'lw-pixel' ) }{ ' ' }
							<code>{ locked }</code>
						</span>
					</p>
				) }
				{ errors.length > 0 && (
					<ul className="lw-admin-fielderror" id={ errorId }>
						{ errors.map( ( message ) => (
							<li key={ message }>{ message }</li>
						) ) }
					</ul>
				) }
			</div>
		</div>
	);
}
