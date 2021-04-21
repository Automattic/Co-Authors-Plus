import { chevronUp, chevronDown, close } from '@wordpress/icons';
import {
	Button,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const AuthorsSelection = ({
	selectedOptions,
	moveOption,
	removeFromSelected,
	update,
	getOptionFrom
}) => {

	return selectedOptions.map( ( { name, value }, i ) => {
		const option = getOptionFrom( value, 'valueStr', selectedOptions );

		return (
			<div key={ value } className="cap-author">
				<Flex align="center">
					<FlexItem>
						<span>{ name }</span>
					</FlexItem>
					<FlexItem justify="flex-end">
						<Flex>
							<div className="cap-icon-button-stack">
								<Button
									icon={chevronUp}
									className={ 'cap-icon-button' }
									label={ __(
										'Move Up',
										'coauthors-plus'
									) }
									disabled={ i === 0 }
									onClick={ () =>
										moveOption(
											option,
											selectedOptions,
											'up',
											update
										)
									}
								/>
								<Button
									icon={chevronDown}
									className={ 'cap-icon-button' }
									label={ __(
										'Move down',
										'coauthors-plus'
									) }
									disabled={
										i === selectedOptions.length - 1
									}
									onClick={ () =>
										moveOption(
											option,
											selectedOptions,
											'down',
											update
										)
									}
								/>
							</div>
							<Button
								icon={close}
								iconSize={20}
								className={ 'cap-icon-button' }
								label={ __(
									'Remove Author',
									'coauthors-plus'
								) }
								onClick={ () =>
									removeFromSelected( value )
								}
							/>
						</Flex>
					</FlexItem>
				</Flex>
			</div>
		);
	} );
};