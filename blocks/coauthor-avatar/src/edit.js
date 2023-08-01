import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { SelectControl, PanelBody, ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { context, attributes, setAttributes } ) {

	const { author_name } = context;
	const { isLink, size } = attributes;
	const [ author, setAuthor ] = useState({
		link: '#',
		display_name: 'CoAuthor Name',
		// how to get defaults??
		avatar_urls: {
			24: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2224%22%20height%3D%2224%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%225%22%3E24x24%3C%2Ftext%3E%3C%2Fsvg%3E',
			48: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2210%22%3E48x48%3C%2Ftext%3E%3C%2Fsvg%3E',
			96: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2296%22%20height%3D%2296%22%20viewBox%3D%220%200%2096%2096%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%2296%22%20height%3D%2296%22%20fill%3D%22%23eeeeee%22%3E%3C%2Frect%3E%3Ctext%20fill%3D%22%23111111%22%20font-family%3D%22sans-serif%22%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20font-size%3D%2219%22%3E96x96%3C%2Ftext%3E%3C%2Fsvg%3E'
		}
	});
	const noticesDispatch = useDispatch('core/notices');

	useEffect(() => {
		if ( ! author_name ) {
			return;
		}

		const controller = new AbortController();

		apiFetch( {
			path: `/coauthor-blocks/v1/coauthor/${author_name}`,
			signal: controller.signal
		} )
		.then( setAuthor )
		.catch( handleError )

		return () => {
			controller.abort();
		}
	}, [author_name]);

	/**
	 * Handle Error
	 * 
	 * @param {Error}
	 */
	function handleError( error ) {
		if ( 'AbortError' === error.name ) {
			return;
		}
		noticesDispatch.createErrorNotice( error.message, { isDismissible: true } );
	}

	const { avatar_urls, link, display_name } = author;

	if ( ! avatar_urls || 0 === avatar_urls.length ) {
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
					label={ __( 'Make avatar a link to author archive.' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
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
			</PanelBody>
		</InspectorControls>
		</>
	);
}
