/**
 * External dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { formatAuthorData } from './utils';

const DEFAULT_STATE = {
	authors: [],
};

const COAUTHORS_ENDPOINT = '/coauthors/v1/authors';

const actions = {
	setAuthors( authors ) {
		return {
			type: 'SET_AUTHORS',
			authors: [ ...authors ],
		};
	},

	setAuthorsStore( newAuthors ) {
		return {
			type: 'SET_AUTHORS_STORE',
			authors: [ ...newAuthors ],
		};
	},

	apiRequest( path, method = 'GET' ) {
		return {
			type: 'API_REQUEST',
			path,
			method,
		};
	},
};

export const coauthorsStore = createReduxStore( 'cap/authors', {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_AUTHORS':
				return {
					...state,
					authors: [ ...state.authors, ...action.authors ],
				};

			case 'SET_AUTHORS_STORE':
				return {
					...state,
					authors: [ ...action.authors ],
				};
		}

		return state;
	},

	actions,

	selectors: {
		getAuthors( state ) {
			const { authors } = state;
			return authors;
		},

		saveAuthors( state ) {
			const { authors } = state;
			return authors;
		},
	},

	controls: {
		API_REQUEST( action ) {
			return apiFetch( { path: action.path, method: action.method } );
		},
	},

	resolvers: {
		*getAuthors( postId ) {
			const path = `${ COAUTHORS_ENDPOINT }/${ postId }`;
			const result = yield actions.apiRequest( path );

			const authors = result.map( ( author ) =>
				formatAuthorData( author )
			);
			return actions.setAuthors( authors );
		},

		*saveAuthors( postId, authors ) {
			const authorsStr = authors
				.map( ( item ) => item.value )
				.join( ',' );
			const path = `${ COAUTHORS_ENDPOINT }/${ postId }?new_authors=${ authorsStr }`;

			yield actions.apiRequest( path, 'POST' );
		},
	},
} );
