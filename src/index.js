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
	dispatch,
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
import { addItem } from './utils';
import { coauthorsStore } from './store';

/**
 * Register our data store.
 */
register( coauthorsStore );

// /**
//  * Fetch current coauthors and set state.
//  *
//  * @param {Object} props
//  * @returns
//  */
// const setInitialAuthors = ( {
// 	authors,
// 	setSelectedAuthors,
// } ) => {
// 	if ( ! authors ) {
// 		return;
// 	}

// 	setSelectedAuthors( authors );
// };

/**
 * The Render component that will be populated with data from
 * the select and methods from dispatch as composed below.
 *
 * @param {Object} props
 * @returns
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
		const newAuthors = addItem(
			newAuthorValue,
			selectedAuthors,
			dropdownOptions
		);

		updateAuthors( newAuthors );
	};

	// Run on first render.
	useEffect( () => {
		if ( ! authors.length ) {
			return;
		}
		setSelectedAuthors( authors );
	}, [ authors ] );

	const onFilterValueChange = ( query ) => {
		const existingAuthors = selectedAuthors
			.map( ( item ) => item.value )
			.join( ',' );

		apiFetch( {
			path: `/coauthors/v1/search/?q=${ query }&existing_authors=${ existingAuthors }`,
			method: 'GET',
		} ).then( ( response ) => {
			const formatAuthorData = ( {
				display_name,
				user_nicename,
				email,
			} ) => {
				return {
					label: `${ display_name } | ${ email }`,
					display: display_name,
					value: user_nicename,
				};
			};

			const formattedAuthors = ( ( items ) => {
				if ( items.length > 0 ) {
					return items.map( ( item ) => formatAuthorData( item ) );
				} else {
					return [];
				}
			} )( response );

			setDropdownOptions( formattedAuthors );
		} );
	};

	return (
		<>
			{ selectedAuthors.length ? (
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
		const postId = post.id;

		const { getAuthors } = select( 'cap/authors' );

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
					meta: { _non_existing_meta: true },
				} );
			},
		};
	} ),
] )( Render );

const { isSavingPost, getCurrentPost } = select( 'core/editor' );
const { getAuthors, saveAuthors } = select( 'cap/authors' );

let checked = true; // Start in a checked state.

subscribe( () => {
	if ( isSavingPost() ) {
		checked = false;
	} else {
		if ( ! checked ) {
			const { id } = getCurrentPost();
			const authors = getAuthors( id );
			saveAuthors( id, authors );
			checked = true;
		}
	}
} );

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
