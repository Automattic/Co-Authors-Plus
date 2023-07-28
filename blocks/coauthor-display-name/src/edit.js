/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, ToggleControl } from '@wordpress/components';
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
	const { display_name, link } = context;
	const { isLink, rel } = attributes;
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
					label={ __( 'Make title a link' ) }
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
