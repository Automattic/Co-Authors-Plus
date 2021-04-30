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
import { withSelect } from '@wordpress/data';

/**
 * Internal Dependencies
 */
import './style.css';
import { AuthorsSelection } from './components/AuthorsSelection';

/**
 * Fetch current coauthors and set state.
 *
 * @param {Object} props
 * @returns
 */
const fetchAndSetOptions = ( {
	postId,
	selectedAuthors,
	setSelectedAuthors,
} ) => {
	if ( ! postId ) {
		return;
	}

	if ( selectedAuthors.length < 1 ) {
		apiFetch( {
			path: `/coauthors/v1/authors/${ postId }`,
		} )
			.then( ( result ) => {
				const authorNames = result.map(
					( author ) => author.user_nicename
				);
				setSelectedAuthors( authorNames );
			} )
			.catch( ( e ) => console.error( e ) );
	}
};

/**
 * The Render component that will be populated with data from
 * the select and methods from dispatch as composed below.
 *
 * @param {Object} props
 * @returns
 */
const Render = ( { postId, updateAuthors } ) => {
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

	const onChange = ( newAuthor ) => {
		const newAuthors = [ ...selectedAuthors, newAuthor ];

		setSelectedAuthors( newAuthors );
		updateAuthors( newAuthors );
	};

	const onFilterValueChange = ( query ) => {
		const existingAuthors = selectedAuthors.join( ',' );

		apiFetch( {
			path: `/coauthors/v1/search/?q=${ query }&existing_authors=${ existingAuthors }`,
			method: 'GET',
		} ).then( ( response ) => {
			const formatAuthorData = ( {
				id,
				display_name,
				user_nicename,
				email,
			} ) => {
				return {
					id,
					label: `${ display_name } | ${ email }`,
					name: display_name,
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
				<AuthorsSelection
					selectedAuthors={ selectedAuthors }
					setSelectedAuthors={ setSelectedAuthors }
					removeFromSelected={ removeFromSelected }
				/>
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

		const updateAuthors = ( newAuthors ) => {
			const authorsStr = newAuthors.join( ',' );

			apiFetch( {
				path: `/coauthors/v1/authors/${ postId }?new_authors=${ authorsStr }`,
				method: 'POST',
			} )
				.then( ( res ) => {
					console.log( res );
				} )
				.catch( ( e ) => console.error( e ) );
		};

		return {
			updateAuthors,
			postId,
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
