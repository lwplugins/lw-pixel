/**
 * LW Pixel - Admin JavaScript
 *
 * @package LightweightPlugins\Pixel
 */

(function () {
	'use strict';

	function initTabs() {
		var tabLinks  = document.querySelectorAll( '.lw-pixel-tabs a' );
		var tabPanels = document.querySelectorAll( '.lw-pixel-tab-panel' );

		if ( ! tabLinks.length || ! tabPanels.length ) {
			return;
		}

		var hash     = window.location.hash.substring( 1 );
		var firstTab = tabLinks[0].getAttribute( 'href' ).substring( 1 );
		var validTab = false;

		tabLinks.forEach( function ( link ) {
			if ( link.getAttribute( 'href' ).substring( 1 ) === hash ) {
				validTab = true;
			}
		} );

		activateTab( validTab ? hash : firstTab );

		tabLinks.forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var tabId = this.getAttribute( 'href' ).substring( 1 );
				activateTab( tabId );
				history.replaceState( null, '', '#' + tabId );
			} );
		} );

		// Preserve active tab after Settings save redirect.
		var form = document.querySelector( '.lw-pixel-settings' );
		if ( form ) {
			form = form.closest( 'form' );
		}
		if ( form ) {
			form.addEventListener( 'submit', function () {
				var activeLink = document.querySelector( '.lw-pixel-tabs a.active' );
				if ( ! activeLink ) {
					return;
				}
				var tabSlug = activeLink.getAttribute( 'href' ).substring( 1 );
				var referer = form.querySelector( 'input[name="_wp_http_referer"]' );
				if ( referer && referer.value.indexOf( '#' ) === -1 ) {
					referer.value += '#' + tabSlug;
				}
			} );
		}

		function activateTab( tabId ) {
			tabLinks.forEach( function ( link ) {
				var linkTabId = link.getAttribute( 'href' ).substring( 1 );
				if ( linkTabId === tabId ) {
					link.classList.add( 'active' );
				} else {
					link.classList.remove( 'active' );
				}
			} );

			tabPanels.forEach( function ( panel ) {
				if ( panel.id === 'tab-' + tabId ) {
					panel.classList.add( 'active' );
				} else {
					panel.classList.remove( 'active' );
				}
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initTabs );
	} else {
		initTabs();
	}
})();
