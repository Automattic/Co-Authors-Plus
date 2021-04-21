/**
 * WordPress dependencies
 */
import {
	ComboboxControl,
	Spinner,
	Button,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { chevronUp, chevronDown, close } from '@wordpress/icons';
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
import { getOptionFrom, moveOption } from './utils';

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
			const optionObj = getOptionFrom( term, 'termObj' );

			if ( currentTermsIds.includes( term.id ) ) {
				currentTermsOptions.push( optionObj );
			}

			return optionObj;
		} );

		// If there are no author terms, this is a new post,
		// so assign the user as the currently selected author
		if ( 0 === currentTermsOptions.length ) {
			currentTermsOptions.push( getOptionFrom( currentUser, 'userObj' ) );
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
		updateTerms( [ 4, 5 ] );
	}, [ selectedOptions ] );

	// Helper function to remove an item.
	const removeFromSelected = ( value ) => {
		const newSelectedOptions = selectedOptions.filter(
			( option ) => option.value !== value
		);
		setSelectedOptions( [ ...newSelectedOptions ] );
	};

	// The component markup and bindings.
	const AuthorsList = () => {
		return selectedOptions.map( ( { name, value }, i ) => {
			const option = getOptionFrom( value, 'valueStr', selectedOptions );

			return (
				<p key={ value } className="cap-author">
					<Flex align="center">
						<FlexItem>
							<span>{ name }</span>
						</FlexItem>
						<FlexItem justify="flex-end">
							<Flex>
								<div className="cap-icon-button-stack">
									<Button
										icon={chevronUp}
										className={ 'cap-icon-button' }
										label={ __(
											'Move Up',
											'coauthors-plus'
										) }
										disabled={ i === 0 }
										onClick={ () =>
											moveOption(
												option,
												selectedOptions,
												'up',
												setSelectedOptions
											)
										}
									/>
									<Button
										icon={chevronDown}
										className={ 'cap-icon-button' }
										label={ __(
											'Move down',
											'coauthors-plus'
										) }
										disabled={
											i === selectedOptions.length - 1
										}
										onClick={ () =>
											moveOption(
												option,
												selectedOptions,
												'down',
												setSelectedOptions
											)
										}
									/>
								</div>
								<Button
									icon={close}
									iconSize={20}
									className={ 'cap-icon-button' }
									label={ __(
										'Remove Author',
										'coauthors-plus'
									) }
									onClick={ () =>
										removeFromSelected( value )
									}
								/>
							</Flex>
						</FlexItem>
					</Flex>
				</p>
			);
		} );
	};

	const onChange = ( newValue ) => {
		const newOption = getOptionFrom(
			newValue,
			'valueStr',
			filteredOptions
		);

		console.log( newOption );
		// Ensure value is not added twice
		// const newSelectedOptions = selectedOptions.filter( option => option !== newOption );

		// console.log(newOption);
		// setSelectedOptions( [ ...newSelectedOptions, newOption ] );
		// console.log(newValue);
	};

	return (
		<>
			<AuthorsList />

			{ !! filteredOptions[ 0 ] ? (
				<ComboboxControl
					label="Select An Author"
					value={ null }
					options={ filteredOptions }
					onChange={ onChange }
					onFilterValueChange={ ( inputValue ) => {
						// const newOptions = filteredOptions.filter( option =>
						// 	option.label.toLowerCase().startsWith(
						// 		inputValue.toLowerCase()
						// 	) &&
						// 	! currentTerms.filter( term => term.id === option.id )
						// );
						// setDropdownOptions(
						// 	newOptions
						// )
					} }
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
		const currentTermsIds = getCurrentPost().coauthors;
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
		return {
			updateTerms: ( newTerms ) => {
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
