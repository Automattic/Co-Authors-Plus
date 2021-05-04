import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';

const DEFAULT_STATE = {
	authors: [],
	postId: null,
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

	fetchFromAPI( path ) {
		return {
			type: 'FETCH_FROM_API',
			path,
		};
	},
};

export const coauthorsStore = createReduxStore( 'cap/authors', {
	reducer( state = DEFAULT_STATE, action ) {

		switch ( action.type ) {

			case 'SET_AUTHORS':
				return {
					...state,
					authors: [
						...state.authors,
						...action.authors
					]
				};

			case 'SET_AUTHORS_STORE':
				return {
					...state,
					authors: [
						...action.authors
					]
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
	},

	controls: {
		FETCH_FROM_API( action ) {
			return apiFetch( { path: action.path } );
		},
	},

	resolvers: {
		*getAuthors( postId ) {
			//path: `/coauthors/v1/authors/${ postId }?new_authors=${ newAuthorsStr }`,
			const path = `/coauthors/v1/authors/${ postId }`;
			const result = yield actions.fetchFromAPI( path );

			const authors = result.map(
				( {
					display_name,
					user_nicename,
					email
				} ) => {
					return {
						label: `${ display_name } | ${ email }`,
						display: display_name,
						value: user_nicename
					}
				}
			);
			return actions.setAuthors( authors );
		},
	},
} );
