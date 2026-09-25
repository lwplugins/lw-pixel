/**
 * Internal dependencies
 */
import FormSkeleton from '../../components/FormSkeleton';
import LoadError from '../../components/LoadError';
import EventEditor from './EventEditor';
import EventList from './EventList';

/**
 * Custom events: the list, or the editor of one event (saved by the top bar
 * Save or Cmd/Ctrl+S). Replaces the classic, unlinked post type screen;
 * events are stored the same way, so existing ones show up here.
 *
 * @param {Object} props
 * @param {Object} props.events Custom events store.
 */
export default function CustomEventsTab( { events } ) {
	if ( events.error ) {
		return <LoadError message={ events.error } onRetry={ events.reload } />;
	}

	if ( events.isLoading ) {
		return <FormSkeleton />;
	}

	return events.editing ? (
		<EventEditor events={ events } />
	) : (
		<EventList events={ events } />
	);
}
