import classnames from 'classnames';

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, __experimentalUseBorderProps as useBorderProps, store as blockEditorStore } from '@wordpress/block-editor';
import { Placeholder, SelectControl, PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {

	const { isLink, rel, size } = attributes;
	const settings = useSelect( select => select( blockEditorStore ).getSettings(), []);
	const author = context['cap/author'] || settings['cap/author-example'];
	const { avatar_urls } = author;

	if ( ! avatar_urls || 0 === avatar_urls.length ) {
		return null;
	}

	const sizes = Object.keys( avatar_urls ).map( (size) => {
		return {
			value: size,
			label: `${ size } x ${ size }`
		};
	});

	const borderProps = useBorderProps( attributes );

	const src = avatar_urls[size] ?? '';
	
	return (
		<>
		<div { ...useBlockProps() }>
			{
				'' === src ?
				(
					<Placeholder
					className={ classnames('block-editor-media-placeholder', borderProps.className ) }
						withIllustration={ true }
						style={ {
							height: size,
							width: size,
							minWidth: 'auto',
							minHeight: 'auto',
							padding: 0,
							...borderProps.style
						} }
					/>
				) : (
					<img style={{...borderProps.style}} width={size} height={size} src={`${avatar_urls[size]}`} />
				)
			}
		</div>
		<InspectorControls>
			<PanelBody title={ __( 'Avatar Settings' ) }>
				<ToggleControl
					label={ __( 'Make avatar a link to author archive.' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
				{ isLink && (
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Link rel' ) }
						value={ rel }
						onChange={ ( newRel ) =>
							setAttributes( { rel: newRel } )
						}
					/>
				) }
				<SelectControl
					label={ __( 'Avatar size' ) }
					value={ size }
					options={ sizes }
					onChange={ ( nextSize ) => {
						setAttributes( {
							size: Number( nextSize )
						} );
					} }
				/>
			</PanelBody>
		</InspectorControls>
		</>
	);
}
