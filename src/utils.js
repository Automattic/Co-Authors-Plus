
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
