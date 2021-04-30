import { moveItem } from '../utils.js';

describe( 'Utility - moveItem', () => {

	const stateMock = jest.fn();

	it( 'should move an option down', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		moveItem( 'admin', initialArray, 'down', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'laras126', 'admin', 'drake', 'fizzbuzz' ] );

	} );

	it( 'should move an option up', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		moveItem( 'laras126', initialArray, 'up', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'laras126', 'admin', 'drake', 'fizzbuzz' ] );
	} );

	it( 'should move items at the end', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		moveItem( 'drake', initialArray, 'down', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'admin', 'laras126', 'fizzbuzz', 'drake' ] );
	});

	it( 'should move items multiple times in multiple directions', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		moveItem( 'drake', initialArray, 'up', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'admin', 'drake', 'laras126', 'fizzbuzz' ] );

		moveItem( 'admin', [ 'admin', 'drake', 'laras126', 'fizzbuzz' ], 'down', stateMock );

		expect( stateMock ).toHaveBeenCalledWith( [ 'drake', 'admin', 'laras126', 'fizzbuzz' ] );
	});

} );
