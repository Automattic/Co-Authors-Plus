
export const moveItem = ( targetItem, itemsArr, direction, setState ) => {

	const currIndex = itemsArr.indexOf( targetItem );
	const indexUpdate = direction == 'up' ? -1 : 1;
	const newIndex = currIndex + indexUpdate;

	const arrCopy = [ ...itemsArr];
	const targetCopy = arrCopy[currIndex];

	const newItems = arrCopy.filter( ( item ) => item !== targetCopy );
	const sortedArr = [...newItems]

	sortedArr.splice( newIndex, 0, targetCopy );

	setState( sortedArr );
};
