import classnames from 'classnames';

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	store as blockEditorStore,
	__experimentalUseBorderProps as useBorderProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { Placeholder, TextControl, PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import './editor.scss';

import DimensionControls from './dimension-controls';

/**
 * 
 * @param {Object|undefined} media 
 * @param {String|undefined} slug 
 * @return {String|undefined}
 */
function getMediaSourceUrlBySizeSlug( media, slug ) {
	return (
		media?.media_details?.sizes?.[ slug ]?.source_url || media?.source_url
	);
}

function getPlaceholderStyles(media, {width, height, aspectRatio, scale, sizeSlug}, imageDimensions) {
	const styles = { width, height, objectFit: scale };

	if ( ( width && height ) || ( (width || height) && ( aspectRatio && 'auto' !== aspectRatio ) ) ) {
		return {
			width: '100%',
			height: '100%',
			objectFit: scale
		}
	}

	const keys = Object.keys( imageDimensions )
	const sizeKey = (! media && 'full' === sizeSlug) ? keys[Math.max(0, keys.length - 1)] : sizeSlug;
	const size = imageDimensions[sizeKey];

	const newStyles = {};

	if ( size && (! width || ! height )) {
		if ( true === size.crop ) {
			newStyles['width']  = `${size.width}px`;
			if ( ( ! aspectRatio || 'auto' === aspectRatio ) ) {
				newStyles['aspectRatio'] = `${size.width}/${size.height}`;
			}
		} else if ( (size.width >= size.height) && size.width > 0 ) {
			newStyles['width'] = `${size.width}px`;
			if ( ! media ) {
				newStyles['aspectRatio'] = '1/1';
			}
		} else if ( size.height > 0 ) {
			newStyles['height'] = `${size.height}px`;
			if ( ! media ) {
				newStyles['aspectRatio'] = '1/1';
			}
		}
	}

	if ( ((! newStyles['width'] || ! newStyles['height'])) && ( aspectRatio && 'auto' !== aspectRatio ) ) {
		newStyles['aspectRatio'] = aspectRatio;
	}

	for ( const key in newStyles ) {
		if ( ! styles[key] ) {
			styles[key] = newStyles[key];
		}
	}

	return styles;
}

function getContainerStyles( { width, height, aspectRatio } ) {

	if ( ( width || height ) ) {
		return { width, height, aspectRatio };
	}

	return {}
}

/**
 * Edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes, context, clientId } ) {

	const authorPlaceholder = useSelect( select => select( 'cap/blocks' ).getAuthorPlaceholder(), []);
	const author = context['cap/author'] || authorPlaceholder;
	const borderProps = useBorderProps( attributes );
	const media = useSelect( (select) => {
		return 0 !== author.featured_media && select( coreStore ).getMedia( author.featured_media, { context: 'view' } )
	}, [author.featured_media]);

	const { isLink,
		rel,
		width,
		height,
		aspectRatio,
		sizeSlug, // AKA "resolution"
		scale // AKA "object-fit"
	} = attributes;

	const mediaUrl = getMediaSourceUrlBySizeSlug( media, sizeSlug );

	const { imageSizes, imageDimensions } = useSelect( select => select( blockEditorStore ).getSettings(), [] );

	const imageSizeOptions = imageSizes.map( ( { name, slug } ) => ( { value: slug, label: name } ) );

	const containerStyles = getContainerStyles( attributes );

	const imageEditStyles = {
		...borderProps.style,
		...getPlaceholderStyles( media, attributes, imageDimensions )
	};

	const placeholderStyles = {
		padding: 0,
		minHeight: '100%',
		minWidth: '100%',
		...borderProps.style,
		...getPlaceholderStyles( media, attributes, imageDimensions ),
	};
	
	// don't placehold feature images in a loop where there's no image.
	// but do placehold them in author archive contexts.
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
					<figure { ...useBlockProps( { style: containerStyles } ) }>
						{
							media ? (
								<img
									src={ mediaUrl }
									alt={
										media.alt_text
											? sprintf(
													// translators: %s: The image's alt text.
													__( 'Featured image: %s' ),
													media.alt_text
												)
											: __( 'Featured image' )
									}
									style={ imageEditStyles }
								/>
							) : (
								<Placeholder
									className={ classnames('block-editor-media-placeholder', borderProps.className ) }
									withIllustration={ true }
									style={ placeholderStyles }
								/>
							)
						}
					</figure>
				)
			}
			
			<InspectorControls>
				<PanelBody title={ __( 'Settings' ) }>
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
			</InspectorControls>
		</>
	);
}
