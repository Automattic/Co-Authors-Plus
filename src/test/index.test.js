import { getOptionFrom, moveOption } from '../utils.js';

describe( 'Utility', () => {
	it( 'should retrieve an option by value', () => {
		const optionsMock = [
			{
				name: 'pmcdev',
				value: 'cap-pmcdev',
				label: 'pmcdev local@test.com something else'
			},
			{
				name: 'guest',
				value: 'cap-guest',
				label: 'guest local@test.com something else'
			},
			{
				name: 'demo',
				value: 'cap-demo',
				label: 'demo local@test.com something else'
			}
		];

		expect( getOptionFrom( 'cap-pmcdev', 'valueStr', optionsMock ) ).toStrictEqual( {
			name: 'pmcdev',
			value: 'cap-pmcdev',
			label: 'pmcdev local@test.com something else'
		} );
	} );

	it( 'should move an option up and down', () => {
		const stateMock = jest.fn();

		moveOption(
			'a',
			[ 'a', 'b', 'c' ],
			'down',
			stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'b', 'a', 'c' ] );

		moveOption(
			'c',
			[ 'a', 'b', 'c' ],
			'up',
			stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'a', 'c', 'b' ] );
	} );

} );
