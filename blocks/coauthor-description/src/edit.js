/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context } ) {
	const author = context['cap/author'] || {
		id: 0,
		display_name: 'FirstName LastName',
		description: '<p>Post hoc impie perpetratum quod in aliis quoque iam timebatur, tamquam licentia crudelitati indulta per suspicionum nebulas aestimati quidam.</p>',
		link: '#',
		avatar_urls: {
			24: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2224%22%20height%3D%2224%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%225%22%3E24x24%3C%2Ftext%3E%3C%2Fsvg%3E',
			48: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2210%22%3E48x48%3C%2Ftext%3E%3C%2Fsvg%3E',
			96: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2296%22%20height%3D%2296%22%20viewBox%3D%220%200%2096%2096%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2296%22%20height%3D%2296%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2219%22%3E96x96%3C%2Ftext%3E%3C%2Fsvg%3E'
		}
	};

	const { description } = author;

	return (
		<div { ...useBlockProps({className:['is-layout-flow']}) } dangerouslySetInnerHTML={ { __html: description } } />
	);
}
