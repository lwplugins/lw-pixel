/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { errorMessage } from './api';

/**
 * Load one read-only resource (skeleton while loading, retry on failure).
 *
 * @param {() => Promise} fetcher API call.
 * @return {Object} { data, error, isLoading, reload }.
 */
export default function useRemote( fetcher ) {
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( null );

	const reload = useCallback( () => {
		setError( null );
		return fetcher().then( setData, ( e ) =>
			setError( errorMessage( e ) )
		);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		reload();
	}, [ reload ] );

	return { data, error, isLoading: ! data && ! error, reload };
}
