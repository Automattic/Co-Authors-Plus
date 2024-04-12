import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	__experimentalUseBorderProps as useBorderProps,
	BlockControls,
	BlockAlignmentToolbar,
} from '@wordpress/block-editor';
import {
	SelectControl,
	PanelBody,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import classnames from 'classnames';

import PlaceholderImage from '../components/placeholder-image';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {
	const { isLink, rel, size, verticalAlign, align } = attributes;
	const authorPlaceholder = useSelect(
		( select ) => select( 'co-authors-plus/blocks' ).getAuthorPlaceholder(),
		[]
	);
	const author = context[ 'co-authors-plus/author' ] || authorPlaceholder;
	const layout = context[ 'co-authors-plus/layout' ] || '';

	const { avatar_urls } = author;

	if ( ! avatar_urls || 0 === avatar_urls.length ) {
		return null;
	}

	const sizes = Object.keys( avatar_urls ).map( ( size ) => {
		return {
			value: size,
			label: `${ size } x ${ size }`,
		};
	} );

	const borderProps = useBorderProps( attributes );

	const src = avatar_urls[ size ] ?? '';

	return (
		<>

			{ 'default' !== layout ? (
				<BlockControls>
					<BlockAlignmentToolbar value={ align } onChange={ ( nextAlign ) => { setAttributes({align: nextAlign}) } } controls={['none', 'left', 'center', 'right']} />
				</BlockControls>
			) : (
				null
			) }
			
			<div { ...useBlockProps( {
				className: classnames({
					[`align${align}`]: 'default' !== layout && align && 'none' !== align
				})
			}
			) }>
				{ '' === src ? (
					<PlaceholderImage
						className={ borderProps.className }
						dimensions={ { width: size, height: size } }
						style={ {
							height: size,
							width: size,
							minWidth: 'auto',
							minHeight: 'auto',
							padding: 0,
							verticalAlign,
							...borderProps.style,
						} }
					/>
				) : (
					<img
						style={ { ...borderProps.style, verticalAlign } }
						width={ size }
						height={ size }
						src={ `${ avatar_urls[ size ] }` }
					/>
				) }
			</div>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar Settings', 'co-authors-plus' ) }>
					<SelectControl
						label={ __( 'Avatar size', 'co-authors-plus' ) }
						value={ size }
						options={ sizes }
						onChange={ ( nextSize ) => {
							setAttributes( {
								size: Number( nextSize ),
							} );
						} }
					/>
					<ToggleControl
						label={ __(
							'Make avatar a link to author archive.',
							'co-authors-plus'
						) }
						onChange={ () => setAttributes( { isLink: ! isLink } ) }
						checked={ isLink }
					/>
					{ isLink && (
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Link rel', 'co-authors-plus' ) }
							value={ rel }
							onChange={ ( newRel ) =>
								setAttributes( { rel: newRel } )
							}
						/>
					) }
				</PanelBody>
				{ 'default' === layout ? (
					<PanelBody
						initialOpen={ false }
						title={ __( 'Co-Authors Layout', 'co-authors-plus' ) }
					>
						<SelectControl
							label={ __( 'Vertical align', 'co-authors-plus' ) }
							value={ verticalAlign }
							options={ [
								{
									value: '',
									label: __( 'Default', 'co-authors-plus' ),
								},
								{
									value: 'baseline',
									label: __( 'Baseline', 'co-authors-plus' ),
								},
								{
									value: 'bottom',
									label: __( 'Bottom', 'co-authors-plus' ),
								},
								{
									value: 'middle',
									label: __( 'Middle', 'co-authors-plus' ),
								},
								{
									value: 'sub',
									label: __( 'Sub', 'co-authors-plus' ),
								},
								{
									value: 'super',
									label: __( 'Super', 'co-authors-plus' ),
								},
								{
									value: 'text-bottom',
									label: __( 'Text Bottom', 'co-authors-plus' ),
								},
								{
									value: 'text-top',
									label: __( 'Text Top', 'co-authors-plus' ),
								},
								{
									value: 'top',
									label: __( 'Top', 'co-authors-plus' ),
								},
							] }
							onChange={ ( value ) => {
								setAttributes( {
									verticalAlign: '' === value ? undefined : value,
								} );
							} }
							help={ __(
								'Vertical alignment defaults to bottom in the block layout and middle in the inline layout.',
								'co-authors-plus'
							) }
						/>
					</PanelBody>
				) : (
					null
			) }
			</InspectorControls>
		</>
	);
}
