import { moveItem, removeItem, addItemByValue } from '../utils';
import {
	selectedAuthors,
	newAuthorValue,
	dropdownOptions,
} from '../__mocks__/authors';

describe( 'Utility - moveItem', () => {
	it( 'should move an option down', () => {
		expect(
			moveItem( selectedAuthors[ 0 ], selectedAuthors, 'down' )
		).toStrictEqual( [
			selectedAuthors[ 1 ],
			selectedAuthors[ 0 ],
			selectedAuthors[ 2 ],
			selectedAuthors[ 3 ],
		] );
	} );

	it( 'should move an option up', () => {
		expect(
			moveItem( selectedAuthors[ 2 ], selectedAuthors, 'up' )
		).toStrictEqual( [
			selectedAuthors[ 0 ],
			selectedAuthors[ 2 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 3 ],
		] );
	} );

	it( 'should move an item to last', () => {
		expect(
			moveItem( selectedAuthors[ 2 ], selectedAuthors, 'down' )
		).toStrictEqual( [
			selectedAuthors[ 0 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 3 ],
			selectedAuthors[ 2 ],
		] );
	} );

	it( 'should move items multiple times in multiple directions', () => {
		expect(
			moveItem( selectedAuthors[ 2 ], selectedAuthors, 'up' )
		).toStrictEqual( [
			selectedAuthors[ 0 ],
			selectedAuthors[ 2 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 3 ],
		] );

		const reorderedArray = [
			selectedAuthors[ 0 ],
			selectedAuthors[ 2 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 3 ],
		];

		expect(
			moveItem( selectedAuthors[ 2 ], reorderedArray, 'down' )
		).toStrictEqual( [
			selectedAuthors[ 0 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 2 ],
			selectedAuthors[ 3 ],
		] );
	} );
} );

describe( 'Utility - removeItem', () => {
	it( 'should remove an item from an array', () => {
		expect(
			removeItem( selectedAuthors[ 2 ], selectedAuthors )
		).toStrictEqual( [
			selectedAuthors[ 0 ],
			selectedAuthors[ 1 ],
			selectedAuthors[ 3 ],
		] );
	} );
} );

describe( 'Utility - addItemByValue', () => {
	it( 'should add an item from dropdown options to end of the array', () => {
		expect(
			addItemByValue( newAuthorValue, selectedAuthors, dropdownOptions )
		).toStrictEqual( [ ...selectedAuthors, dropdownOptions[ 0 ] ] );
	} );
} );
