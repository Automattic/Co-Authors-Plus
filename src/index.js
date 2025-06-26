/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { select, subscribe } from "@wordpress/data";

/**
 * Components
 */
import CoAuthors from './components/co-authors';

/**
 * Component for rendering the plugin sidebar.
 */
const PluginDocumentSettingPanelAuthors = () => (
	<PluginDocumentSettingPanel
		name="coauthors-panel"
		title={ __( 'Authors', 'co-authors-plus' ) }
		className="coauthors"
	>
		<CoAuthors />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-coauthors-document-setting', {
	render: PluginDocumentSettingPanelAuthors,
	icon: 'users',
} );

