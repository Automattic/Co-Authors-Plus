import { createReduxStore, register } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

register(
	createReduxStore( 'cap/blocks', {
		reducer: ( state = window.coAuthorsBlocks ) => {
			return state;
		},
		selectors: {
			getAuthorPlaceholder: ( state ) => applyFilters( 'cap.author-placeholder', state.authorPlaceholder ),
		},
	} )
);
