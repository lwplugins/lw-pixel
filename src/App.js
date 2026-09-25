/**
 * Internal dependencies
 */
import FormSkeleton from './components/FormSkeleton';
import LoadError from './components/LoadError';
import Notices from './components/Notices';
import useCustomEvents from './data/useCustomEvents';
import useSettingsStore from './data/useSettingsStore';
import Footer from './shell/Footer';
import navMeta from './shell/navMeta';
import SideNav from './shell/SideNav';
import TopBar from './shell/TopBar';
import { GROUPS, TABS } from './shell/tabs';
import useSaveShortcut from './shell/useSaveShortcut';
import useTab from './shell/useTab';
import useUnsavedWarning from './shell/useUnsavedWarning';
import AdvancedTab from './tabs/AdvancedTab';
import ChatGptTab from './tabs/ChatGptTab';
import ComplianceTab from './tabs/ComplianceTab';
import ConsentTab from './tabs/ConsentTab';
import CustomEventsTab from './tabs/custom-events/CustomEventsTab';
import EventsTab from './tabs/EventsTab';
import FormsTab from './tabs/FormsTab';
import GoogleTab from './tabs/GoogleTab';
import MetaTab from './tabs/MetaTab';
import NetworksTab from './tabs/NetworksTab';
import OverviewTab from './tabs/OverviewTab';
import ToolsTab from './tabs/tools/ToolsTab';
import WooCommerceTab from './tabs/WooCommerceTab';

const SETTINGS_TABS = {
	overview: OverviewTab,
	meta: MetaTab,
	google: GoogleTab,
	chatgpt: ChatGptTab,
	networks: NetworksTab,
	events: EventsTab,
	forms: FormsTab,
	woocommerce: WooCommerceTab,
	consent: ConsentTab,
	compliance: ComplianceTab,
	advanced: AdvancedTab,
	tools: ToolsTab,
};

// A ?tab= link opens that tab (classic slugs are mapped in useTab).
const INITIAL_TAB =
	new URLSearchParams( window.location.search ).get( 'tab' ) || 'overview';

/**
 * Which store the top bar saves on this tab: the custom event editor while
 * it is open there, the settings on settings tabs, and on read-only tabs
 * the settings only while they hold unsaved edits.
 *
 * @param {Object} tab      Current tab.
 * @param {Object} settings Settings store.
 * @param {Object} events   Custom events store.
 * @return {Object|null} Store with { hasEdits, isSaving, save, discard }.
 */
function saveTarget( tab, settings, events ) {
	if ( tab.save === 'events' && events.editing ) {
		return events;
	}
	if ( ! settings.data ) {
		return null;
	}
	return tab.save === true || settings.hasEdits ? settings : null;
}

/**
 * Shell + one settings store: every settings tab edits it and one Save (top
 * bar or Cmd/Ctrl+S) writes all changed options atomically. Custom events
 * have their own editor and save.
 */
export default function App() {
	const settings = useSettingsStore();
	const events = useCustomEvents();
	const tab = useTab(
		TABS.map( ( t ) => t.id ),
		INITIAL_TAB
	);
	const current = TABS.find( ( t ) => t.id === tab ) || TABS[ 0 ];
	const target = saveTarget( current, settings, events );

	useUnsavedWarning( settings.hasEdits || events.hasEdits );
	useSaveShortcut(
		() => target?.save(),
		!! target && target.hasEdits && ! target.isSaving,
		!! target
	);

	let content;
	if ( current.id === 'custom-events' ) {
		content = <CustomEventsTab events={ events } />;
	} else if ( settings.error ) {
		content = (
			<LoadError message={ settings.error } onRetry={ settings.reload } />
		);
	} else if ( settings.isLoading ) {
		content = <FormSkeleton />;
	} else {
		const Tab = SETTINGS_TABS[ current.id ];
		content = <Tab store={ settings } events={ events } />;
	}

	return (
		<>
			<div className="lw-admin-shell">
				<SideNav
					tabs={ TABS }
					groups={ GROUPS }
					current={ current.id }
					meta={ navMeta( {
						errors: settings.errors,
						meta: settings.data?.meta || null,
					} ) }
					docsUrl={ settings.data?.meta.docsUrl }
				/>
				<div className="lw-admin-main">
					<TopBar title={ current.title } store={ target } />
					<main className="lw-admin-scroll">
						<div className="lw-admin-content">{ content }</div>
					</main>
					<Footer />
				</div>
			</div>
			<Notices />
		</>
	);
}
