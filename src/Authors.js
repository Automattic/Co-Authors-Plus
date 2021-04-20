/**
 * WordPress dependencies
 */
import { ComboboxControl } from "@wordpress/components";
import { useState } from "@wordpress/compose";

const options = [
	{
		value: "small",
		label: "Small"
	},
	{
		value: "normal",
		label: "Normal"
	},
	{
		value: "large",
		label: "Large"
	},
	{
		value: "huge",
		label: "Huge"
	}
];

const Authors = () => {
	const [fontSize, setFontSize] = useState();
	const [filteredOptions, setFilteredOptions] = useState(options);
	return (
		<ComboboxControl
			label="Font Size"
			value={fontSize}
			onChange={setFontSize}
			options={filteredOptions}
			onInputChange={(inputValue) =>
				setFilteredOptions(
					options.filter(option =>
						option.label.toLowerCase().startsWith(inputValue.toLowerCase())
					)
				)
			}
		/>
	);
};

export default Authors;