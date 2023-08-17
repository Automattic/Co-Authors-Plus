/**
 * Co-Author Feature Image
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	store as blockEditorStore,
	__experimentalUseBorderProps as useBorderProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { TextControl, PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import DimensionControls from './dimension-controls';
import PlaceholderImage from '../../components/placeholder-image';
import { getAvailableSizeSlug, getMediaSrc, getMediaDimensions, getPlaceholderImageDimensions } from './utils'

import './editor.scss';

/**
 * Edit
 *
 * @export
 * @param {Object} props { attributes, setAttributes, context, clientId }
 * @return {WPElement}
 */
export default function Edit( { attributes, setAttributes, context, clientId } ) {

	const { aspectRatio, height, isLink, rel, scale, sizeSlug, verticalAlign, width } = attributes;

	// Author
	const authorPlaceholder = useSelect(
		select => select( 'cap/blocks' ).getAuthorPlaceholder(),
		[]
	);
	const author = context['cap/author'] || authorPlaceholder;

	// Media
	const media = useSelect(
		select => 0 !== author.featured_media && select( coreStore ).getMedia( author.featured_media, { context: 'view' } ),
		[author.featured_media]
	);

	// Image Sizes and Dimensions
	const { imageSizes, imageDimensions } = useSelect(
		select => select( blockEditorStore ).getSettings(),
		[]
	);
	const imageSizeOptions = imageSizes.map( ( { name, slug } ) => ({ value: slug, label: name }));
	const availableSizeSlug = getAvailableSizeSlug( media, imageDimensions, sizeSlug );	
	const dimensions = getMediaDimensions( media, imageDimensions, availableSizeSlug );
	const placeholderDimensions = media ? {} : getPlaceholderImageDimensions(imageDimensions, availableSizeSlug);

	// Border
	const borderProps = useBorderProps( attributes );

	// Don't placehold feature images for real authors with no image.
	// Do placehold them in author archive contexts.
	const panic = 0 !== author.id && false === media;

	return (
		<>
			<DimensionControls
				clientId={ clientId }
				attributes={ attributes }
				setAttributes={ setAttributes }
				imageSizeOptions={ imageSizeOptions }
			/>
			{
				panic ? null : (
					<figure {...useBlockProps()}>
						{
							media ? (
								<img
									alt={__('Author feature image')}
									className={ borderProps.className }
									src={getMediaSrc(media, availableSizeSlug)}
									style={{
										width: ! width && height ? 'auto' : width,
										height: ! height && width ? 'auto' : height,
										aspectRatio,
										objectFit: scale,
										verticalAlign,
										...borderProps.style
									}}
									width={ dimensions.width }
									height={ dimensions.height }
								/>
							) : (
								<PlaceholderImage
									className={ borderProps.className }
									dimensions={placeholderDimensions}
									style={{
										width: ! width && height ? 'auto' : width,
										height: ! height && width ? 'auto' : height,
										aspectRatio,
										objectFit: scale,
										verticalAlign,
										...borderProps.style
									}}
								/>
							)
						}
					</figure>
				)
			}
			<InspectorControls>
				<PanelBody title={ __( 'Feature Image Settings' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Make feature image a link to author archive.' ) }
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
