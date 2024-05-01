import { memo } from '@wordpress/element';
import { __experimentalUseBlockPreview as useBlockPreview } from '@wordpress/block-editor';

/**
 * CoAuthor Template Block Preview
 */
function CoAuthorTemplateBlockPreview( {
	blocks,
	blockContextId,
	isHidden,
	setActiveBlockContextId,
} ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
		props: {
			className: 'wp-block-co-authors-plus-coauthor',
		},
	} );

	const handleOnClick = () => {
		setActiveBlockContextId( blockContextId );
	};

	const style = {
		display: isHidden ? 'none' : undefined,
	};

	return (
		<div
			{ ...blockPreviewProps }
			tabIndex={ 0 }
			role="button"
			onClick={ handleOnClick }
			onKeyUp={ handleOnClick }
			style={ style }
		/>
	);
}

export default memo( CoAuthorTemplateBlockPreview );
