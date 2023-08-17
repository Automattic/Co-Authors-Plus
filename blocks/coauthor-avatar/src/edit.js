import classnames from 'classnames';

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, __experimentalUseBorderProps as useBorderProps, store as blockEditorStore } from '@wordpress/block-editor';
import { Placeholder, SelectControl, PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import PlaceholderImage from '../../components/placeholder-image';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {

	const { isLink, rel, size, verticalAlign } = attributes;
	const authorPlaceholder = useSelect( select => select( 'cap/blocks' ).getAuthorPlaceholder(), []);
	const author = context['cap/author'] || authorPlaceholder;
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
		<figure { ...useBlockProps() }>
			{
				'' === src ?
				(
					<PlaceholderImage
						className={borderProps.className}
						dimensions={{width: size, height: size}}
						style={ {
							height: size,
							width: size,
							minWidth: 'auto',
							minHeight: 'auto',
							padding: 0,
							verticalAlign, 
							...borderProps.style
						} }
					/>
				) : (
					<img
						style={{...borderProps.style, verticalAlign}}
						width={size}
						height={size}
						src={`${avatar_urls[size]}`}
					/>
				)
			}
		</figure>
		<InspectorControls>
			<PanelBody title={ __( 'Avatar Settings' ) }>
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
			</PanelBody>
			<PanelBody initialOpen={false} title={__('Coauthors Layout')}>
				<SelectControl
					label={ __( 'Vertical align' ) }
					value={ verticalAlign }
					options={ [
						{value: '', label: 'Middle ( Default )'},
						{value: 'baseline', label: 'Baseline'},
						{value: 'bottom', label: 'Bottom'},
						{value: 'sub', label: 'Sub'},
						{value: 'super', label: 'Super'},
						{value: 'text-bottom', label: 'Text Bottom'},
						{value: 'text-top', label: 'Text Top'},
						{value: 'top', label: 'Top'},
					] }
					onChange={ ( value ) => {
						setAttributes( {
							verticalAlign: '' === value ? undefined : value
						} );
					} }
					help={ __( 'Vertical alignment applies when displaying coauthors in the "inline" layout.' )}
				/>
			</PanelBody>
		</InspectorControls>
		</>
	);
}
