/**
 * WordPress dependencies
 */
import { ComboboxControl, Spinner, IconButton } from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { compose, withState } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { withSelect, withDispatch } from '@wordpress/data';

/**
 * Internal Dependencies
 */
import { getOptionFrom, moveOption } from './utils';

const fetchAndSetOptions = ({
	currentTermsIds,
	currentUser,
	taxonomyRestBase,
	setSelectedOptions,
	setFilteredOptions
}) => {

	if ( ! taxonomyRestBase || undefined === currentUser.name ) {
		return;
	}

	apiFetch( {
		path: `/wp/v2/${ taxonomyRestBase }`,
	} ).then( ( terms ) => {

		const currentTermsOptions = [];

		const allOptions = terms.map( ( term ) => {

			const optionObj = getOptionFrom( term, 'termObj' );

			if( currentTermsIds.includes( term.id ) ) {
				currentTermsOptions.push( optionObj );
			}

			return optionObj;
		});

		// If there are no author temrs, this is a new post,
		// so assign the user as the currently selected author
		if ( 0 === currentTermsOptions.length ) {
			currentTermsOptions.push( getOptionFrom( currentUser, 'userObj' ) );
		}

		setSelectedOptions( currentTermsOptions );
		setFilteredOptions( allOptions );
	} );
};

const Render = ( props ) => {

	const {
		currentTermsIds,
		taxonomyRestBase,
		currentUser,
	} = props;

	const [selectedOptions, setSelectedOptions] = useState( [] );
	const [filteredOptions, setFilteredOptions] = useState( [] );

	// Run when taxonomyRestBase changes
	// This will only be on initial render
	useEffect( () => {
		fetchAndSetOptions({
			currentUser,
			currentTermsIds,
			taxonomyRestBase,
			setSelectedOptions,
			setFilteredOptions,
		});
	}, [ taxonomyRestBase, currentUser, currentTermsIds ]);

	const removeFromSelected = ( value ) => {
		const newSelectedOptions = selectedOptions.filter( option => option.value !== value );
		setSelectedOptions( [ ...newSelectedOptions ] );
	};

	const AuthorsList = () => {
		return selectedOptions.map( ( { name, value }, i ) => {

			const option = getOptionFrom( value, 'valueStr', selectedOptions );

			return (
				<p key={value}>
					<span>{name}</span>
					<IconButton
						icon="arrow-up-alt2"
						label="Move up"
						disabled={ i === 0 }
						size="10"
						onClick={ ( i ) => moveOption( option, selectedOptions, 'up', setSelectedOptions ) }
					/>
					<IconButton
						icon="arrow-down-alt2"
						label="Move down"
						size="10"
						disabled={ i === selectedOptions.length - 1 }
						onClick={ ( i ) => moveOption( option, selectedOptions, 'down', setSelectedOptions ) }
					/>
					<IconButton
						icon="no-alt"
						label="Remove author"
						size="10"
						onClick={ () => removeFromSelected( value ) }
					/>
				</p>
			);
		});
	};

	const onChange = ( newValue ) => {
		const newOption = getOptionFrom( newValue, 'valueStr', filteredOptions );

		// Ensure value is not added twice
		const newSelectedOptions = selectedOptions.filter( option => option !== newOption );
		setSelectedOptions( [ ...newSelectedOptions, newOption ] );
	};

	return (
		<>
			<AuthorsList />

			{ !! filteredOptions[0] ?
				<ComboboxControl
					label="Select An Author"
					value={null}
					options={filteredOptions}
					onChange={onChange}
					onFilterValueChange={
						(inputValue) => {
							// const newOptions = filteredOptions.filter( option =>
							// 	option.label.toLowerCase().startsWith(
							// 		inputValue.toLowerCase()
							// 	) &&
							// 	! currentTerms.filter( term => term.id === option.id )
							// );
							// setFilteredOptions(
							// 	newOptions
							// )
						}
					}
				/>
			: <span>
				<p>{ __( 'Loading authors, this could take a moment...', 'coauthors' ) }</p>
				<Spinner />
			</span>
			}
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
		const currentTermsIds = getCurrentPost().author;
		const currentUser = getCurrentUser();

		if ( ! taxonomy ) {
			return {
				allAuthorTerms: [],
				taxonomyRestBase: null,
			};
		}

		return {
			currentTermsIds,
			currentUser,
			taxonomyRestBase: taxonomy.rest_base,
			postTypeRestBase: postType.rest_base,
		};
	} ),
	withDispatch( ( dispatch, { currentTermsIds, taxonomyRestBase } ) => {
		// return {
		// 	updateTerms: ( termId ) => {
		// 		const hasTerm = currentTermsIds.indexOf( termId ) !== -1;
		// 		const newTerms = hasTerm
		// 			? without( currentTermsIds, termId )
		// 			: [ ...currentTermsIds, termId ];

		// 		dispatch( 'core/editor' ).editPost( {
		// 			[ taxonomyRestBase ]: newTerms,
		// 		} );
		// 	},
		// };
	} ),
] )( Render );

const PluginDocumentSettingPanelDemo = () => (
	<PluginDocumentSettingPanel
		name="custom-panel"
		title="Authors"
		className="authors"
	>
		<CoAuthors />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-coauthors-document-setting', {
	render: PluginDocumentSettingPanelDemo,
	icon: 'users'
} );