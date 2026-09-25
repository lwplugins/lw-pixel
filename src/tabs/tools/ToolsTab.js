/**
 * Internal dependencies
 */
import ImportSection from './ImportSection';
import SystemReportSection from './SystemReportSection';

/**
 * Tools: settings import from another plugin and the system report.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ToolsTab( { store } ) {
	return (
		<>
			<ImportSection store={ store } />
			<SystemReportSection />
		</>
	);
}
