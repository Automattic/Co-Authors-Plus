import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { SelectControl, PanelBody, ToggleControl } from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {

	const { avatar_urls, link } = context;
	const { isLink, size } = attributes;

	if ( ! avatar_urls ) {
		return null;
	}

	const sizes = Object.keys( avatar_urls ).map( (size) => {
		return {
			value: size,
			label: `${ size } x ${ size }`
		};
	});

	// what to do if existing size is not in sizes array?

	const image = <img width={size} height={size} src={`${avatar_urls[size]}`} />;

	return (
		<>
		<div { ...useBlockProps() }>
			{(
				isLink ? (
					<a href={link} onClick={(e => e.preventDefault())}>
						{image}
					</a>
				) : (
					image
				)
			)}
		</div>
		<InspectorControls>
			<PanelBody title={ __( 'Avatar Settings' ) }>
				<ToggleControl
					// __nextHasNoMarginBottom
					label={ __( 'Make avatar a link to author archive.' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
				<SelectControl
					// __nextHasNoMarginBottom
					label={ __( 'Avatar size' ) }
					value={ size }
					options={ sizes }
					onChange={ ( nextSize ) => {
						setAttributes( {
							size: Number( nextSize ),
						} );
					} }
				/>
			</PanelBody>
		</InspectorControls>
		</>
	);
}
