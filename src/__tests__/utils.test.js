import { moveItem, removeItem, addItem } from '../utils.js';

describe( 'Utility - moveItem', () => {

	it( 'should move an option down', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		expect( moveItem( 'admin', initialArray, 'down' ) ).toStrictEqual( [ 'laras126', 'admin', 'drake', 'fizzbuzz' ] );
	} );

	it( 'should move an option up', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		expect( moveItem( 'laras126', initialArray, 'up' ) ).toStrictEqual( [ 'laras126', 'admin', 'drake', 'fizzbuzz' ] );
	} );

	it( 'should move items at the end', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		expect( moveItem( 'drake', initialArray, 'down' ) ).toStrictEqual( [ 'admin', 'laras126', 'fizzbuzz', 'drake' ] );
	});

	it( 'should move items multiple times in multiple directions', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		expect( moveItem( 'drake', initialArray, 'up' ) ).toStrictEqual( [ 'admin', 'drake', 'laras126', 'fizzbuzz' ] );

		expect( moveItem( 'admin', [ 'admin', 'drake', 'laras126', 'fizzbuzz' ], 'down' ) ).toStrictEqual( [ 'drake', 'admin', 'laras126', 'fizzbuzz' ] );
	});

	it.only( 'should work with objects', () => {
		const initialArray = [
			{
				value: 'drake',
				display: 'Drake'
			},
			{
				value: 'laras126',
				display: 'Lara S'
			},
			{
				value: 'admin',
				display: 'Administrator'
			},
		];

		expect( moveItem( {
			value: 'drake',
			display: 'Drake'
		}, initialArray, 'down' ) ).toStrictEqual( [
			{
				value: 'laras126',
				display: 'Lara S'
			},
			{
				value: 'drake',
				display: 'Drake'
			},
			{
				value: 'admin',
				display: 'Administrator'
			},
		] );
	});
} );

describe( 'Utility - removeItem', () => {
	it( 'should remove an item from an array', () => {
		const initialArray = [ 'admin', 'laras126', 'drake', 'fizzbuzz' ];

		expect( removeItem( 'fizzbuzz', initialArray ) ).toStrictEqual( [ 'admin', 'laras126', 'drake' ] );
	});
});


describe( 'Utility - addItem', () => {
	it.only( 'should add an item to the array', () => {

		const initialArray = [
			{
				value: 'laras126',
				display: 'Lara S'
			},
			{
				value: 'admin',
				display: 'Administrator'
			},
		];

		const objectsStore = [
			{
				value: 'drake',
				display: 'Drake'
			}
		];

		expect( addItem( 'drake', initialArray, objectsStore ) ).toStrictEqual( [
			{
				value: 'laras126',
				display: 'Lara S'
			},
			{
				value: 'admin',
				display: 'Administrator'
			},
			{
				value: 'drake',
				display: 'Drake'
			},
		] );
	});
});