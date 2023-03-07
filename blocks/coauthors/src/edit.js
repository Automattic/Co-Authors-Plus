/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';

import apiFetch from '@wordpress/api-fetch';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
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

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { clientId, context } ) {

	const { postId } = context;
	const [ coAuthors, setCoAuthors ] = useState([]);
	const [ activeBlockContextId, setActiveBlockContextId ] = useState();
	const noticesDispatch = useDispatch('core/notices');

	useEffect(()=>{
		const controller = new AbortController();

		apiFetch( {
			path: `/coauthors/v1/authors/${postId}`,
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

	return (
		<div { ...useBlockProps() }>
			{
				coAuthors && 
				coAuthors.map( ( { id, displayName } ) => {
					const isHidden = id === ( activeBlockContextId || coAuthors[0]?.id );
					return (
						<BlockContextProvider
							key={ id }
							value={ { coAuthorId: id, displayName } }
						>
							{ isHidden ? (<CoAuthorTemplateInnerBlocks />) : null }
							<MemoizedCoAuthorTemplateBlockPreview
								blocks={blocks}
								blockContextId={id}
								setActiveBlockContextId={ setActiveBlockContextId }
								isHidden={isHidden}
							/>
						</BlockContextProvider>
					)
				}
				)
			}
		</div>
	);
}
