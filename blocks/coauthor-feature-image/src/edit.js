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

/**
 * Edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes, context, clientId } ) {

	const { isLink, rel, width, height, aspectRatio, sizeSlug, scale } = attributes;

	const author = context['cap/author'] || {
		id: 0,
		display_name: 'FirstName LastName',
		link: '#',
		avatar_urls: [],
		featured_media: 0
	};

	const media = useSelect( (select) => {
		return 0 !== author.featured_media && select( coreStore ).getMedia( author.featured_media, { context: 'view' } )
	}, [author.featured_media]);

	const mediaUrl = getMediaSourceUrlBySizeSlug( media, sizeSlug );

	const { imageSizes, imageDimensions } = useSelect(
		( select ) => select( blockEditorStore ).getSettings(),
		[]
	);

	const imageSizeOptions = imageSizes.map(
		( { name, slug } ) => ( { value: slug, label: name } )
	);
	
	const borderProps = useBorderProps( attributes );

	const imageStyles = {
		...borderProps.style,
		height: aspectRatio ? '100%' : height,
		width: !! aspectRatio && '100%',
		objectFit: !! ( height || aspectRatio ) && scale,
	};

	const widthFromImageDimensions = imageDimensions[sizeSlug]?.width;
	const hasWidthOrHeight = width || height;

	const blockProps = useBlockProps( {
		style: {
			width: hasWidthOrHeight ? width : widthFromImageDimensions,
			height,
			aspectRatio,
		}
	} );

	return (
		<>
			<DimensionControls
				clientId={ clientId }
				attributes={ attributes }
				setAttributes={ setAttributes }
				imageSizeOptions={ imageSizeOptions }
			/>
			<figure { ...blockProps }>
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
							style={ imageStyles }
						/>
					) : (
						<Placeholder
							className={ classnames('block-editor-media-placeholder', borderProps.className ) }
							withIllustration={ true }
							style={ {
								height: !! aspectRatio && '100%',
								width: !! aspectRatio && '100%',
								minWidth: 'auto',
								minHeight: 'auto',
								...borderProps.style
							} }
						/>
					)
				}
			</figure>
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
