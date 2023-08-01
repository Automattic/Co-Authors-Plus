/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {
	const { author_name } = context;
	const { isLink, rel } = attributes;
	const [ author, setAuthor ] = useState({
		link: '#',
		display_name: 'CoAuthor Name'
	});
	const noticesDispatch = useDispatch('core/notices');

	useEffect(() => {
		if ( ! author_name ) {
			return;
		}

		const controller = new AbortController();

		apiFetch( {
			path: `/coauthor-blocks/v1/coauthor/${author_name}`,
			signal: controller.signal
		} )
		.then( setAuthor )
		.catch( handleError )

		return () => {
			controller.abort();
		}
	}, [author_name]);

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

	const { link, display_name } = author;

	return (
		<>
		<p { ...useBlockProps() }>
			{
				isLink ? (
					<a
						href={link}
						rel={rel}
						onClick={ ( event ) => event.preventDefault() }
					>
						{ display_name }
					</a>
				) : display_name
			}
		</p>
		<InspectorControls>
			<PanelBody title={ __( 'Settings' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Make coauthor name a link' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
				{ isLink && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Link rel' ) }
							value={ rel }
							onChange={ ( newRel ) =>
								setAttributes( { rel: newRel } )
							}
						/>
					</>
				) }
			</PanelBody>
		</InspectorControls>
		</>
	);
}
