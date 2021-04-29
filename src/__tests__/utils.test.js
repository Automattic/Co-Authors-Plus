import { selectedAuthorsMock, termObj, userObj } from '../__mocks__/data';
import { getOptionByValue, getOptionFromData, moveOption } from '../utils.js';

describe( 'Utility - getOptionByValue', () => {
	it( 'should retrieve an option by value', () => {
		expect(
			getOptionByValue( 'cap-tester', selectedAuthorsMock )
		).toStrictEqual( selectedAuthorsMock[ 0 ] );
	} );

	it( 'should return null if there are no selected options', () => {
		expect( getOptionByValue( 'cap-nonexistent', [] ) ).toStrictEqual(
			null
		);
	} );

	it( "should return null if there the author doesn't exist", () => {
		expect(
			getOptionByValue( 'cap-nonexistent', selectedAuthorsMock )
		).toStrictEqual( null );
	} );
} );

describe.only( 'Utility - getOptionFromData', () => {

	const expected = {
		label: "Some Name tester 9 local@test.com",
		name: "tester",
		value: "cap-tester",
	};

	it( 'should get an option from a user object', () => {
		expect( getOptionFromData( userObj, 'userObj' ) ).toStrictEqual(
			expected
		);
	} );

	it( 'should get an option from a term object', () => {
		expect( getOptionFromData( termObj, 'termObj' ) ).toStrictEqual(
			expected
		);
	} );
} );

describe( 'Utility - moveOption', () => {
	it( 'should add an option', () => {
		const currentOptions = [ ...optionsMock ];
		const newOption = { ...currentOptions[ 0 ] };

		expect( addAndGetOptions( newOption, currentOptions ) ).toHaveLength(
			currentOptions.length
		);
	} );

	it( 'should not add duplicate options', () => {
		const currentOptions = [ ...optionsMock ];
		const newOption = { ...currentOptions[ 0 ] };

		expect( addAndGetOptions( newOption, currentOptions ) ).toHaveLength(
			currentOptions.length
		);
	} );

	it( 'should move an option up and down', () => {
		const stateMock = jest.fn();

		moveOption( 'a', [ 'a', 'b', 'c' ], 'down', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'b', 'a', 'c' ] );

		moveOption( 'c', [ 'a', 'b', 'c' ], 'up', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'a', 'c', 'b' ] );
	} );
} );
