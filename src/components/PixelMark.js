/**
 * WordPress dependencies
 */
import { SVG, Circle } from '@wordpress/primitives';

/**
 * LW Pixel mark: the plugin logo (assets/img/title-icon.svg, a solid dot),
 * inlined. The fill follows `color`, which the stylesheet sets to the brand
 * variable ($lw-brand).
 */
export default function PixelMark() {
	return (
		<SVG
			className="lw-admin-mark"
			viewBox="0 0 640 640"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
			focusable="false"
		>
			<Circle cx="320" cy="320" r="160" fill="currentColor" />
		</SVG>
	);
}
