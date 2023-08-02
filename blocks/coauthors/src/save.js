/**
 * Save
 */
import { useBlockProps, InnerBlocks, __experimentalGetGapCSSValue } from '@wordpress/block-editor';
import classnames from 'classnames';

/**
 * Save
 *
 * @return {WPElement} Element to render.
 */
export default function save( { attributes } ) {

	const { layout, textAlign } = attributes;

	const style = {
		gap: 'block' === layout.type ? __experimentalGetGapCSSValue( attributes.style?.spacing?.blockGap ) : null
	};

	const className = classnames({
		[`is-layout-cap-${layout.type}`]: layout.type,
		[`has-text-align-${ textAlign }`]: textAlign
	});

	return (
		<div { ...useBlockProps.save( { className, style } ) }>
			<InnerBlocks.Content />
		</div>
	);
}
