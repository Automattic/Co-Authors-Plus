/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls, AlignmentControl, BlockControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {

	const { isLink, rel, textAlign } = attributes;
	const author = context['cap/author'] || {
		id: 0,
		display_name: 'FirstName LastName',
		link: '#',
		avatar_urls: {
			24: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2224%22%20height%3D%2224%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%225%22%3E24x24%3C%2Ftext%3E%3C%2Fsvg%3E',
			48: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2210%22%3E48x48%3C%2Ftext%3E%3C%2Fsvg%3E',
			96: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2296%22%20height%3D%2296%22%20viewBox%3D%220%200%2096%2096%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2296%22%20height%3D%2296%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2219%22%3E96x96%3C%2Ftext%3E%3C%2Fsvg%3E'
		}
	};

	const { link, display_name } = author;

	return (
		<>
		<BlockControls>
			<AlignmentControl
				value={ textAlign }
				onChange={ ( nextAlign ) => {
					setAttributes( { textAlign: nextAlign } );
				} }
			/>
		</BlockControls>
		<p { ...useBlockProps({ className: classnames( {[`has-text-align-${ textAlign }`]: textAlign} )}) }>
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
