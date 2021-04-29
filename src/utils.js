/**
 *
 * @param {Object} data
 * @param {String} type
 * @returns
 */
export const getOptionFromData = ( data, type ) => {
	switch ( type ) {
		case 'termObj':
			return {
				value: data.slug,
				name: data.name,
				label: data.description,
			};
		case 'userObj':
			return {
				value: data.slug,
				name: data.name,
				label: data.name,
			};
		default:
			return null;
	}
};

/**
 * Retrieve a particular option from the selectedAuthors state
 * by value.
 *
 * @param {String} optionValueStr The cap-* prefixed slug of the author, the value of the value property for each items in selectedAuthors state.
 * @param {Array} optionsArr The selectedAuthors state object.
 * @returns Object with data for the author.
 */
export const getOptionByValue = ( optionValueStr, optionsArr = [] ) => {
	if ( ! optionsArr.length ) return null;

	const filtered = optionsArr.filter( ( { value } ) => {
		return value === optionValueStr;
	} );

	return filtered.length ? filtered[ 0 ] : null;
};

export const moveOption = ( option, optionsArr, direction, setState ) => {
	const swap = ( arr, from, to ) => {
		const arrCopy = [ ...arr ];
		const temp = arrCopy[ from ];

		arrCopy[ from ] = arrCopy[ to ];
		arrCopy[ to ] = temp;

		return arrCopy;
	};

	const currIndex = optionsArr.indexOf( option );

	switch ( direction ) {
		case 'up':
			if ( 0 === currIndex ) return;
			setState( swap( optionsArr, currIndex, currIndex - 1 ) );
		case 'down':
			if ( currIndex === optionsArr.length - 1 ) return;
			setState( swap( optionsArr, currIndex, currIndex + 1 ) );
		default:
			break;
	}
};
