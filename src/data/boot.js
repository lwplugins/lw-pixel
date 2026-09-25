/**
 * Server-provided boot data (SettingsPage inline script `window.lwPixelAdmin`).
 */
const boot = window.lwPixelAdmin || {};

export const VERSION = boot.version || '';
export const NAMESPACE = boot.namespace || 'lw-pixel/v1';
export const DOCS_URL =
	boot.docsUrl || 'https://github.com/lwplugins/lw-pixel#readme';
