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
	RichText,
	__experimentalGetGapCSSValue,
	AlignmentControl
} from '@wordpress/block-editor';
import { TextControl, ToolbarGroup, PanelBody } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { list, grid } from '@wordpress/icons';

import classnames from 'classnames';

import MemoizedCoAuthorTemplateBlockPreview from './components/memoized-coauthor-template-block-preview';

/**
 * CoAuthor Template Inner Blocks
 */
function CoAuthorTemplateInnerBlocks () {
	return <div { ...useInnerBlocksProps(
		{ className: 'wp-block-co-authors-plus-coauthor' },
		{ template : [['co-authors-plus/name']]}
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

	const { prefix, separator, lastSeparator, suffix, layout, textAlign } = attributes;
	const { postId } = context;
	const authorPlaceholder = useSelect( select => select( 'co-authors-plus/blocks' ).getAuthorPlaceholder(), []);
	const [ coAuthors, setCoAuthors ] = useState([authorPlaceholder]);
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const noticesDispatch = useDispatch('core/notices');

	useEffect(()=>{
		if ( ! postId ) {
			return;
		}

		const controller = new AbortController();

		apiFetch( {
			path: `/coauthors/v1/coauthors?post_id=${postId}`,
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
			title: __( 'Inline', 'co-authors-plus' ),
			onClick: () => setLayout( { type: 'inline' } ),
			isActive: layout.type === 'inline',
		},
		{
			icon: grid,
			title: __( 'Block', 'co-authors-plus' ),
			onClick: () =>
				setLayout( { type: 'block' } ),
			isActive: layout.type === 'block',
		},
	];

	return (
		<>
			<BlockControls>
				<ToolbarGroup controls={ layoutControls } />
				<AlignmentControl
					value={ textAlign }
					onChange={ ( nextAlign ) => {
						setAttributes( { textAlign: nextAlign } );
					} }
				/>
			</BlockControls>
			<div { ...useBlockProps({
					className: classnames( {
						[`is-layout-cap-${layout.type}`]: layout.type,
						[`has-text-align-${ textAlign }`]: textAlign,
					}),
					style: {
						gap: __experimentalGetGapCSSValue( attributes?.style?.spacing?.blockGap )
					}
				})
			}>
				{
					coAuthors &&
					'inline' === layout.type &&
					( isSelected || prefix ) &&
					(
						<RichText
							allowedFormats={ ALLOWED_FORMATS }
							className="wp-block-co-authors-plus-coauthors__prefix"
							multiline={ false }
							aria-label={ __( 'Prefix', 'co-authors-plus' ) }
							placeholder={ __( 'Prefix', 'co-authors-plus' ) + ' ' }
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
					.map( ( author ) => {
						const isHidden = author.id === ( activeBlockContextId || coAuthors[0]?.id );
						return (
							<BlockContextProvider
								key={ author.id }
								value={ {'co-authors-plus/author': author } }
							>
								{ isHidden ? (<CoAuthorTemplateInnerBlocks />) : null }
								<MemoizedCoAuthorTemplateBlockPreview
									blocks={blocks}
									blockContextId={author.id}
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
								<span className="wp-block-co-authors-plus-coauthors__separator">
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
							className="wp-block-co-authors-plus-coauthors__suffix"
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
				{
					'inline' === layout.type &&
					(
						<PanelBody title={ __( 'Co-authors Layout', 'co-authors-plus' ) }>
						<TextControl
							autoComplete="off"
							label={ __( 'Separator', 'co-authors-plus' ) }
							value={ separator || '' }
							onChange={ ( nextValue ) => {
								setAttributes( { separator: nextValue } );
							} }
							help={ __( 'Enter character(s) used to separate authors.', 'co-authors-plus' ) }
						/>
						<TextControl
							autoComplete="off"
							label={ __( 'Last Separator', 'co-authors-plus' ) }
							value={ lastSeparator || '' }
							onChange={ ( nextValue ) => {
								setAttributes( { lastSeparator: nextValue } );
							} }
							help={ __( 'Enter character(s) used to separate the last author.', 'co-authors-plus' ) }
						/>
						</PanelBody>
					)
				}
			</InspectorControls>
		</>
	);
}
