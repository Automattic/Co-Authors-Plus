import { optionsMock } from './fixture';
import { formatOption, moveOption } from '../utils.js';

describe( 'Utility', () => {
	it( 'should retrieve an option by value', () => {
		expect(
			formatOption( 'cap-pmcdev', 'valueStr', optionsMock )
		).toStrictEqual( {
			name: 'pmcdev',
			value: 'cap-pmcdev',
			label: 'pmcdev local@test.com something else',
		} );
	} );

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
