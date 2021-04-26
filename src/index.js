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
import { getOptionByValue, getOptionFromData, moveOption } from './utils';

/**
 * Fetch current coauthors and set state.
 *
 * @param {Object} props
 * @returns
 */
const fetchAndSetOptions = ( {
	currentTermsIds,
	currentUser,
	taxonomyRestBase,
	setSelectedOptions,
	setDropdownOptions,
} ) => {
	if ( ! taxonomyRestBase || undefined === currentUser.name ) {
		return;
	}

	apiFetch( {
		path: `/wp/v2/${ taxonomyRestBase }`,
	} ).then( ( terms ) => {
		const currentTermsOptions = [];

		const allOptions = terms.map( ( term ) => {
			console.log('termObj', term);
			const optionObj = getOptionFromData( term, 'termObj' );

			if ( currentTermsIds.includes( term.id ) ) {
				currentTermsOptions.push( optionObj );
			}

			return optionObj;
		} );

		// If there are no author terms, this is a new post,
		// so assign the user as the currently selected author
		if ( 0 === currentTermsOptions.length ) {
			currentTermsOptions.push( getOptionFromData( currentUser, 'userObj' ) );
		}

		setSelectedOptions( currentTermsOptions );
		setDropdownOptions( allOptions );
	} );
};

/**
 * The Render component that will be populated with data from
 * the select and methods from dispatch as composed below.
 *
 * @param {Object} props
 * @returns
 */
const Render = ( {
	currentTermsIds,
	taxonomyRestBase,
	currentUser,
	updateTerms,
} ) => {
	// Currently selected options
	const [ selectedOptions, setSelectedOptions ] = useState( [] );

	// Options that are available in the dropdown
	const [ filteredOptions, setDropdownOptions ] = useState( [] );

	// Run when taxonomyRestBase changes.
	// This is a proxy for detecting initial render.
	// The data is retrieved via the withSelect method below.
	useEffect( () => {
		fetchAndSetOptions( {
			currentUser,
			currentTermsIds,
			taxonomyRestBase,
			setSelectedOptions,
			setDropdownOptions,
		} );
	}, [ taxonomyRestBase, currentTermsIds, currentUser ] );

	// When the selected options change, edit the post terms.
	// This method is provided via withDispatch below.
	// below.
	useEffect( () => {
		console.log( 'updateTerms called' );
		const termIds = selectedOptions.map( ( option ) => option.id );
		updateTerms( termIds );
		console.log( selectedOptions[ 0 ] );
	}, [ selectedOptions ] );

	// Helper function to remove an item.
	const removeFromSelected = ( value ) => {
		const newSelections = selectedOptions.map( ( option ) => {
			if ( option.value !== value ) {
				return option;
			}
		} );
		setSelectedOptions( [ ...newSelections ] );
	};

	const onChange = ( newValue ) => {
		const newOption = getOptionByValue( newValue, filteredOptions );

		// Ensure value is not added twice
		const newSelectedOptions = selectedOptions.map( ( option ) => {
			if ( option !== newOption ) return option;
		} );

		setSelectedOptions( [ ...newSelectedOptions, newOption ] );
	};

	return (
		<>
			<AuthorsSelection
				selectedOptions={ selectedOptions }
				setSelectedOptions={ setSelectedOptions }
				removeFromSelected={ removeFromSelected }
			/>

			{ !! filteredOptions[ 0 ] ? (
				<ComboboxControl
					className="cap-combobox"
					label="Select An Author"
					value={ null }
					options={ filteredOptions }
					onChange={ onChange }
					onFilterValueChange={ () => {} }
				/>
			) : (
				<span>
					<p>
						{ __(
							'Loading authors, this could take a moment...',
							'coauthors'
						) }
					</p>
					<Spinner />
				</span>
			) }
		</>
	);
};

const CoAuthors = compose( [
	withState(),
	withSelect( ( select ) => {
		const { getTaxonomy, getEntity, getCurrentUser } = select( 'core' );
		const { getCurrentPost } = select( 'core/editor' );

		const taxonomy = getTaxonomy( 'author' );
		const postType = getEntity( 'postType', 'guest-author' );
		const currentUser = getCurrentUser();
		const taxonomyRestBase = taxonomy?.rest_base;
		const currentTermsIds = ( () => {
			const post = getCurrentPost();

			return post?.[ taxonomyRestBase ];
		} )();

		console.log('userObj', currentUser);

		return {
			currentTermsIds,
			currentUser,
			taxonomyRestBase,
			postTypeRestBase: postType.rest_base,
		};
	} ),
	withDispatch( ( dispatch, { currentTermsIds, taxonomyRestBase } ) => {
		return {
			updateTerms: ( newTerms ) => {
				console.log( newTerms[ 0 ] );
				if ( null !== taxonomyRestBase && undefined !== newTerms ) {
					dispatch( 'core/editor' ).editPost( {
						[ taxonomyRestBase ]: newTerms,
					} );
				}
			},
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
