/**
 * External dependencies.
 */
import { chevronUp, chevronDown, close } from '@wordpress/icons';
import { Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { getOptionByValue, moveOption } from '../utils';

export const AuthorsSelection = ( {
	selectedAuthors,
	removeFromSelected,
	setSelectedAuthors,
} ) => {
	return selectedAuthors.map( ( value, i ) => {

		return (
			<div key={ value } className="cap-author">
				<Flex align="center">
					<FlexItem>
						<span>{ value }</span>
					</FlexItem>
					<FlexItem justify="flex-end">
						<Flex>
							<div className="cap-icon-button-stack">
								<Button
									icon={ chevronUp }
									className={ 'cap-icon-button' }
									label={ __( 'Move Up', 'coauthors-plus' ) }
									disabled={ i === 0 }
									onClick={ () =>
										moveOption(
											value,
											selectedAuthors,
											'up',
											setSelectedAuthors
										)
									}
								/>
								<Button
									icon={ chevronDown }
									className={ 'cap-icon-button' }
									label={ __(
										'Move down',
										'coauthors-plus'
									) }
									disabled={
										i === selectedAuthors.length - 1
									}
									onClick={ () =>
										moveOption(
											value,
											selectedAuthors,
											'down',
											setSelectedAuthors
										)
									}
								/>
							</div>
							<Button
								icon={ close }
								iconSize={ 20 }
								className={ 'cap-icon-button' }
								label={ __(
									'Remove Author',
									'coauthors-plus'
								) }
								onClick={ () => removeFromSelected( value ) }
							/>
						</Flex>
					</FlexItem>
				</Flex>
			</div>
		);
	} );
};
