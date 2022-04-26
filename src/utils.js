/**
 * Move an item up or down in an array.
 *
 * @param {string} targetItem Item to move.
 * @param {Array}  itemsArr   Array in which to move the item.
 * @param {string} direction  'up' or 'down'
 * @return {Array} Array with reordered items.
 */
export const moveItem = ( targetItem, itemsArr, direction ) => {
	const currIndex = itemsArr
		.map( ( item ) => item.value )
		.indexOf( targetItem.value );
	const indexUpdate = direction === 'up' ? -1 : 1;
	const newIndex = currIndex + indexUpdate;

	const arrCopy = itemsArr.map( ( item ) => Object.assign( {}, item ) );
	const targetCopy = arrCopy[ currIndex ];

	const newItems = ( () => {
		return arrCopy.filter( ( item ) => {
			if ( item.value ) {
				return item.value !== targetCopy.value;
			}
			return item !== targetCopy;
		} );
	} )();
	const sortedArr = [ ...newItems ];

	sortedArr.splice( newIndex, 0, targetCopy );

	return sortedArr;
};

/**
 * Remove an item from the array.
 *
 * @param {Object} targetItem
 * @param {Array}  itemsArr
 * @return {Array} array of items with the target item removed.
 */
export const removeItem = ( targetItem, itemsArr ) => {
	return itemsArr.filter( ( item ) => item.value !== targetItem.value );
};

/**
 * Get the author object from the list of available authors,
 * then add it to the selected authors.
 *
 * @param {string} newAuthorValue
 * @param {Array}  currAuthors
 * @param {Array}  dropDownAuthors
 * @return {Array} Author objects including the new author.
 */
export const addItemByValue = (
	newAuthorValue,
	currAuthors,
	dropDownAuthors
) => {
	const newAuthorObj = dropDownAuthors.filter(
		( item ) => item.value === newAuthorValue
	);
	return [ ...currAuthors, newAuthorObj[ 0 ] ];
};

/**
 * Format the author option object.
 *
 * @param {Object} root0              An author object from the API endpoint.
 * @param {Object} root0.displayName  Name to display in the UI.
 * @param {Object} root0.userNicename The unique username.
 * @param {Object} root0.email
 *
 * @return {Object} The object containing data relevant to the Coauthors component.
 */
export const formatAuthorData = ( { displayName, userNicename, email } ) => {
	return {
		label: `${ displayName } | ${ email }`,
		display: displayName,
		value: userNicename,
	};
};
