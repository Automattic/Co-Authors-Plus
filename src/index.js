/**
 * WordPress dependencies
 */
import { ComboboxControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { compose, withState } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { withSelect, withDispatch } from '@wordpress/data';

/**
 * Internal Dependencies
 */
import './style.css';
import { AuthorsSelection } from './components/AuthorsSelection';
import { getOptionByValue, getOptionFromData } from './utils';

/**
 * Fetch current coauthors and set state.
 *
 * @param {Object} props
 * @returns
 */
const fetchAndSetOptions = ( {
	postId,
	selectedAuthors,
	setSelectedAuthors
} ) => {
	if ( ! postId ) {
		return;
	}

	if ( selectedAuthors.length < 1 ) {
		apiFetch( {
			path: `/coauthors/v1/authors/${ postId }`,
		} ).then( ( result ) => {
			const authorNames = result.map( author => author.user_nicename );
			setSelectedAuthors( authorNames );
		} ).catch( e => console.error( e ) );
	}
};

/**
 * The Render component that will be populated with data from
 * the select and methods from dispatch as composed below.
 *
 * @param {Object} props
 * @returns
 */
const Render = ( {
	postId,
} ) => {
	// Currently selected options
	const [ selectedAuthors, setSelectedAuthors ] = useState( [] );

	// Options that are available in the dropdown
	const [ dropdownOptions, setDropdownOptions ] = useState( [] );

	// Run when taxonomyRestBase changes.
	// This is a proxy for detecting initial render.
	// The data is retrieved via the withSelect method below.
	useEffect( () => {
		fetchAndSetOptions( {
			postId,
			selectedAuthors,
			setSelectedAuthors,
		} );
	}, [ postId, selectedAuthors ] );

	// When the selected options change, edit the post terms.
	// This method is provided via withDispatch below.
	// below.
	// useEffect( () => {
	// 	const termIds = selectedAuthors.map( ( option ) => option.id );
	// 	updateTerms( termIds );
	// }, [ selectedAuthors ] );

	// Helper function to remove an item.
	const removeFromSelected = ( value ) => {
		const newSelections = selectedAuthors.map( ( option ) => {
			if ( option.value !== value ) {
				return option;
			}
		} );
		setSelectedAuthors( [ ...newSelections ] );
	};

	const onChange = ( newAuthor ) => {
		console.log(newAuthor);
		setSelectedAuthors( [ ...selectedAuthors, newAuthor ] );
	};

	const onFilterValueChange = ( query ) => {

		const existingAuthors = selectedAuthors.join(',');

		console.log(existingAuthors);
		apiFetch( {
			path: `/coauthors/v1/search/${query}`,
			method: 'GET',
			data: {
				'existing_authors': existingAuthors,
			}
		} ).then( response => {

			const formattedOptions = response?.map( item => {
				return {
					id: item.id,
					label: `${item.display_name} | ${item.email}`,
					value: item.user_nicename,
					name: item.user_nicename,
				}
			})

			setDropdownOptions(formattedOptions);
		} ).catch( e => {
			console.log( e );
		} );
	};

	return (
		<>
			<AuthorsSelection
				selectedAuthors={ selectedAuthors }
				setSelectedAuthors={ setSelectedAuthors }
				removeFromSelected={ removeFromSelected }
			/>

			<ComboboxControl
				className="cap-combobox"
				label="Select An Author"
				value={ null }
				options={ dropdownOptions }
				onChange={ onChange }
				onFilterValueChange={ onFilterValueChange }
			/>
		</>
	);
};

const CoAuthors = compose( [
	withState(),
	withSelect( ( select ) => {
		const { getCurrentPost } = select( 'core/editor' );
		const post = getCurrentPost();

		return {
			postId: post.id
		};
	} ),
	withDispatch( ( dispatch, { postId } ) => {
		return {
			updateAuthors: ( newAuthors ) => {
				apiFetch( {
					path: '/coauthors/v1/authors',
					method: 'POST',
					data: {
						'post_id': postId,
						'new_authors': newAuthors
					},
				} ).then( ( res ) => {
					console.log( res );
				} );
			}
		};
	} ),
] )( Render );

const PluginDocumentSettingPanelAuthors = () => (
	<PluginDocumentSettingPanel
		name="custom-panel"
		title="Authors"
		className="authors"
	>
		<CoAuthors />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-coauthors-document-setting', {
	render: PluginDocumentSettingPanelAuthors,
	icon: 'users',
} );
