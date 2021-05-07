/**
 * Move an item up or down in an array.
 *
 * @param {string} targetItem Item to move.
 * @param {array} itemsArr Array in which to move the item.
 * @param {string} direction 'up' or 'down'
 * @returns Array with reordered items.
 */
export const moveItem = ( targetItem, itemsArr, direction ) => {
	const currIndex = itemsArr
		.map( ( item ) => item.value )
		.indexOf( targetItem.value );
	const indexUpdate = direction == 'up' ? -1 : 1;
	const newIndex = currIndex + indexUpdate;

	const arrCopy = itemsArr.map( ( item ) => Object.assign( {}, item ) );
	const targetCopy = arrCopy[ currIndex ];

	const newItems = ( () => {
		return arrCopy.filter( ( item ) => {
			if ( item.value ) {
				return item.value !== targetCopy.value;
			} else {
				return item !== targetCopy;
			}
		} );
	} )();
	const sortedArr = [ ...newItems ];

	sortedArr.splice( newIndex, 0, targetCopy );

	return sortedArr;
};

/**
 * Remove an item from the array.
 *
 * @param {object} targetItem
 * @param {array} itemsArr
 * @returns array of items with the target item removed.
 */
export const removeItem = ( targetItem, itemsArr ) => {
	return itemsArr.filter( ( item ) => item.value !== targetItem.value );
};

/**
 * Add a new author.
 *
 * @param {string} newAuthorValue
 * @param {array} currAuthors
 * @param {array} dropDownAuthors
 * @returns Array of author objects including the new author.
 */
export const addItem = ( newAuthorValue, currAuthors, dropDownAuthors ) => {
	const newAuthorObj = dropDownAuthors.filter(
		( item ) => item.value === newAuthorValue
	);
	return [ ...currAuthors, newAuthorObj[ 0 ] ];
};
