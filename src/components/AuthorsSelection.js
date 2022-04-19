/**
 * External dependencies.
 */
import { chevronUp, chevronDown, close } from '@wordpress/icons';
import { Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { moveItem, removeItem } from '../utils';

export const AuthorsSelection = ( { selectedAuthors, updateAuthors } ) => {
	const onClick = ( author, action ) => {
		let authors;

		switch ( action ) {
			case 'moveDown':
				authors = moveItem( author, selectedAuthors, 'down' );
				break;

			case 'moveUp':
				authors = moveItem( author, selectedAuthors, 'up' );
				break;

			case 'remove':
				authors = removeItem( author, selectedAuthors );
				break;
		}

		updateAuthors( authors );
	};

	return selectedAuthors.map( ( author, i ) => {
		const display = author.display;
		const value = author.value;

		return (
			<div key={ value } className="cap-author">
				<Flex align="center">
					<FlexItem>
						<span>{ display }</span>
					</FlexItem>
					<FlexItem justify="flex-end">
						<Flex>
							<div className="cap-icon-button-stack">
								<Button
									icon={ chevronUp }
									className={ 'cap-icon-button' }
									label={ __( 'Move Up', 'co-authors-plus' ) }
									disabled={
										i === 0 || 1 === selectedAuthors.length
									}
									onClick={ () =>
										onClick( author, 'moveUp' )
									}
								/>
								<Button
									icon={ chevronDown }
									className={ 'cap-icon-button' }
									label={ __(
										'Move down',
										'co-authors-plus'
									) }
									disabled={
										i === selectedAuthors.length - 1 ||
										1 === selectedAuthors.length
									}
									onClick={ () =>
										onClick( author, 'moveDown' )
									}
								/>
							</div>
							<Button
								icon={ close }
								iconSize={ 20 }
								className={ 'cap-icon-button' }
								label={ __(
									'Remove Author',
									'co-authors-plus'
								) }
								disabled={ 1 === selectedAuthors.length }
								onClick={ () => onClick( author, 'remove' ) }
							/>
						</Flex>
					</FlexItem>
				</Flex>
			</div>
		);
	} );
};
