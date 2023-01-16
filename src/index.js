/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

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
		title="Authors"
		className="coauthors"
	>
		<CoAuthors />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-coauthors-document-setting', {
	render: PluginDocumentSettingPanelAuthors,
	icon: 'users',
} );
