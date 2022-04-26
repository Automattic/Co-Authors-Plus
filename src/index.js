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
import {
	select,
	subscribe,
	withDispatch,
	withSelect,
	register,
} from '@wordpress/data';

/**
 * Internal Dependencies
 */
import './style.css';
import { AuthorsSelection } from './components/AuthorsSelection';
import { addItemByValue, formatAuthorData } from './utils';
import { coauthorsStore } from './store';

/**
 * Register our data store.
 */
register( coauthorsStore );

/**
 * The Render component that will be populated with data from
 * the select and methods from dispatch as composed below.
 *
 * @param {Object}   root0
 * @param {boolean}  root0.authors         Array of authors from the store.
 * @param {Function} root0.setAuthorsStore Method to save data new authors to the store.
 * @return {JSX.Element}                   Document sidebar panel component.
 */
const Render = ( { authors, setAuthorsStore } ) => {
	// Currently selected options
	const [ selectedAuthors, setSelectedAuthors ] = useState( [] );

	// Options that are available in the dropdown
	const [ dropdownOptions, setDropdownOptions ] = useState( [] );

	const updateAuthors = ( newAuthors ) => {
		setAuthorsStore( newAuthors );
		setSelectedAuthors( newAuthors );
	};

	const onChange = ( newAuthorValue ) => {
		const newAuthors = addItemByValue(
			newAuthorValue,
			selectedAuthors,
			dropdownOptions
		);

		updateAuthors( newAuthors );
	};

	// Run when authors updates.
	useEffect( () => {
		if ( ! authors.length ) {
			return;
		}

		setSelectedAuthors( authors );
	}, [ authors ] );

	/**
	 * The callback for updating autocomplete in the ComboBox component.
	 * Fetch a list of authors matching the search text.
	 *
	 * @param {string} query The text to search.
	 */
	const onFilterValueChange = ( query ) => {
		const existingAuthors = selectedAuthors
			.map( ( item ) => item.value )
			.join( ',' );

		apiFetch( {
			path: `/coauthors/v1/search/?q=${ query }&existing_authors=${ existingAuthors }`,
			method: 'GET',
		} ).then( ( response ) => {
			const formattedAuthors = ( ( items ) => {
				if ( items.length > 0 ) {
					return items.map( ( item ) => formatAuthorData( item ) );
				}
				return [];
			} )( response );

			setDropdownOptions( formattedAuthors );
		} );
	};

	return (
		<>
			{ Boolean( selectedAuthors.length ) ? (
				<>
					<AuthorsSelection
						selectedAuthors={ selectedAuthors }
						setSelectedAuthors={ setSelectedAuthors }
						updateAuthors={ updateAuthors }
					/>
				</>
			) : (
				<Spinner />
			) }

			<ComboboxControl
				className="cap-combobox"
				label={ __( 'Select An Author', 'co-authors-plus' ) }
				value={ null }
				options={ dropdownOptions }
				onChange={ onChange }
				onFilterValueChange={ onFilterValueChange }
			/>
		</>
	);
};

/**
 * Retrieve selectors and data from WordPress,
 * then pass it to our render component.
 */
const CoAuthors = compose( [
	withState(),
	withSelect( ( scopedSelect ) => {
		const { getCurrentPost } = scopedSelect( 'core/editor' );
		const post = getCurrentPost();
		const postId = post.id;

		const { getAuthors } = scopedSelect( 'cap/authors' );

		const authors = getAuthors( postId );

		return {
			postId,
			authors,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { setAuthorsStore } = dispatch( 'cap/authors' );

		return {
			setAuthorsStore: ( authors ) => {
				setAuthorsStore( authors );

				// Save post meta to enable the publish button:
				// https://github.com/WordPress/gutenberg/issues/13774
				dispatch( 'core/editor' ).editPost( {
					meta: { _non_existing_meta: Date.now() },
				} );
			},
		};
	} ),
] )( Render );

// Save authors when the post is saved.
// https://github.com/WordPress/gutenberg/issues/17632
const { isSavingPost, getCurrentPost } = select( 'core/editor' );
const { getAuthors, saveAuthors } = select( 'cap/authors' );

let checked = true; // Start in a checked state.

subscribe( () => {
	if ( isSavingPost() ) {
		checked = false;
	} else if ( ! checked ) {
		const { id } = getCurrentPost();
		const authors = getAuthors( id );
		saveAuthors( id, authors );
		checked = true;
	}
} );

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
