/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	useBlockProps,
	AlignmentControl,
	BlockControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import classnames from 'classnames';
import './editor.css';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {
	const { textAlign } = attributes;
	const authorPlaceholder = useSelect(
		( select ) => select( 'co-authors-plus/blocks' ).getAuthorPlaceholder(),
		[]
	);
	const author = context[ 'co-authors-plus/author' ] || authorPlaceholder;
	const { description } = author;

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
			<div
				{ ...useBlockProps( {
					className: classnames( {
						[ `has-text-align-${ textAlign }` ]: textAlign,
						'is-layout-flow': true,
					} ),
				} ) }
				dangerouslySetInnerHTML={ { __html: description.rendered } }
			/>
		</>
	);
}
