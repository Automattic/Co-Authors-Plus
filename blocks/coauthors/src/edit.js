/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	BlockControls,
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	InspectorControls,
	RichText
} from '@wordpress/block-editor';
import { TextControl, ToolbarGroup, PanelBody } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { list, grid } from '@wordpress/icons';

import classnames from 'classnames';

import MemoizedCoAuthorTemplateBlockPreview from './modules/memoized-coauthor-template-block-preview';

/**
 * CoAuthor Template Inner Blocks
 */
function CoAuthorTemplateInnerBlocks () {
	return <div { ...useInnerBlocksProps(
		{ className: 'wp-block-cap-coauthor' },
		{ template : [['cap/coauthor-display-name']]}
	) } />;
}

const ALLOWED_FORMATS = [
	'core/bold',
	'core/italic',
	'core/text-color',
];

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes, clientId, context, isSelected } ) {

	const { separator, lastSeparator, layout, prefix, suffix } = attributes;
	const { postId } = context;

	/* Default state for full site editing */
	const [ coAuthors, setCoAuthors ] = useState([{
		id: 0,
		display_name: 'CoAuthor Display Name',
		link: ''
	}]);

	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const noticesDispatch = useDispatch('core/notices');

	useEffect(()=>{
		if ( ! postId ) {
			return;
		}

		const controller = new AbortController();

		apiFetch( {
			path: `/coauthor-blocks/v1/coauthors/${postId}/`,
			signal: controller.signal
		} )
		.then( setCoAuthors )
		.catch( handleError )

		return () => {
			controller.abort();
		}
	},[postId]);

	/**
	 * Handle Error
	 * 
	 * @param {Error}
	 */
	function handleError( error ) {
		if ( 'AbortError' === error.name ) {
			return;
		}
		noticesDispatch.createErrorNotice( error.message, { isDismissible: true } );
	}

	const blocks = useSelect( (select) => {
		return select( blockEditorStore ).getBlocks( clientId );
	});

	const setLayout = ( nextLayout ) => {
		setAttributes( {
			layout: { ...layout, ...nextLayout },
		} );
	}

	const layoutControls = [
		{
			icon: list,
			title: __( 'Inline' ),
			onClick: () => setLayout( { type: 'inline' } ),
			isActive: layout.type === 'inline',
		},
		{
			icon: grid,
			title: __( 'Block' ),
			onClick: () =>
				setLayout( { type: 'block' } ),
			isActive: layout.type === 'block',
		},
	];

	return (
		<>
			<BlockControls>
				<ToolbarGroup controls={ layoutControls } />
			</BlockControls>
			<div { ...useBlockProps({className: classnames( [ `is-layout-cap-${layout.type}` ] )}) }>
				{
					coAuthors &&
					'inline' === layout.type &&
					( isSelected || prefix ) &&
					(
						<RichText
							allowedFormats={ ALLOWED_FORMATS }
							className="wp-block-cap-coauthor__prefix"
							multiline={ false }
							aria-label={ __( 'Prefix' ) }
							placeholder={ __( 'Prefix' ) + ' ' }
							value={ prefix }
							onChange={ ( value ) =>
								setAttributes( { prefix: value } )
							}
							tagName="span"
						/>
					)
				}
				{
					coAuthors && 
					coAuthors
					.map( ( { id, display_name, link } ) => {
						const isHidden = id === ( activeBlockContextId || coAuthors[0]?.id );
						return (
							<BlockContextProvider
								key={ id }
								value={ { coAuthorId: id, display_name, link } }
							>
								{ isHidden ? (<CoAuthorTemplateInnerBlocks />) : null }
								<MemoizedCoAuthorTemplateBlockPreview
									blocks={blocks}
									blockContextId={id}
									setActiveBlockContextId={ setActiveBlockContextId }
									isHidden={isHidden}
								/>
							</BlockContextProvider>
						);
					})
					.reduce( ( previous, current, index, all ) => (
						<>
						{ previous }
						{
							'inline' === layout.type &&
							(
								<span className="wp-block-cap-coauthor__separator">
									{ ( lastSeparator && index === (all.length - 1) ) ? `${lastSeparator}` : `${separator}` }
								</span>
							)	
						}
						{ current }
						</>
					))
				}
				{
					coAuthors &&
					'inline' === layout.type &&
					( isSelected || suffix ) &&
					(
						<RichText
							allowedFormats={ ALLOWED_FORMATS }
							className="wp-block-cap-coauthor__suffix"
							multiline={ false }
							aria-label={ __( 'Suffix' ) }
							placeholder={ __( 'Suffix' ) + ' ' }
							value={ suffix }
							onChange={ ( value ) =>
								setAttributes( { suffix: value } )
							}
							tagName="span"
						/>
					)
				}
			</div>
			<InspectorControls>
				<PanelBody title={ __( 'CoAuthors Layout' ) }>
					{
						'inline' === layout.type &&
						(
							<>
							<TextControl
								autoComplete="off"
								label={ __( 'Separator' ) }
								value={ separator || '' }
								onChange={ ( nextValue ) => {
									setAttributes( { separator: nextValue } );
								} }
								help={ __( 'Enter character(s) used to separate authors.' ) }
							/>
							<TextControl
								autoComplete="off"
								label={ __( 'Last Separator' ) }
								value={ lastSeparator || '' }
								onChange={ ( nextValue ) => {
									setAttributes( { lastSeparator: nextValue } );
								} }
								help={ __( 'Enter character(s) used to distinguish the last author.' ) }
							/>
							</>
						)
					}
				</PanelBody>
			</InspectorControls>
		</>
	);
}
