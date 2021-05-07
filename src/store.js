import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

const DEFAULT_STATE = {
	authors: [],
};

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
			const path = `/coauthors/v1/authors/${ postId }`;
			const result = yield actions.apiRequest( path );

			const authors = result.map(
				( { display_name, user_nicename, email } ) => {
					return {
						label: `${ display_name } | ${ email }`,
						display: display_name,
						value: user_nicename,
					};
				}
			);
			return actions.setAuthors( authors );
		},

		*saveAuthors( postId, authors ) {
			const authorsStr = authors
				.map( ( item ) => item.value )
				.join( ',' );
			const path = `/coauthors/v1/authors/${ postId }?new_authors=${ authorsStr }`;

			yield actions.apiRequest( path, 'POST' );
		},
	},
} );
