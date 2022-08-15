import { useCallback, useState, useMemo } from "@wordpress/element";
import { ReplacementVariableEditor } from "@yoast/replacement-variable-editor";
import { useField } from "formik";
import PropTypes from "prop-types";

/**
 * @param {string} className The wrapper class.
 * @param {Object} props The props to pass down to the component.
 * @param {string} props.name The field name.
 * @returns {JSX.Element} The Formik compatible element.
 */
const FormikReplacementVariableEditorField = ( { className = "", ...props } ) => {
	const [ editorRef, setEditorRef ] = useState( null );
	const [ field, , { setTouched, setValue } ] = useField( props );

	const handleChange = useCallback( newValue => {
		setTouched( true, false );
		setValue( newValue );
	}, [ props.name ] );

	const handleFocus = useCallback( () => editorRef?.focus(), [ editorRef ] );

	const value = useMemo( () => ( field.value?.match( /%%\w+%%$/ ) ? `${ field.value } ` : field.value ) || "", [ field.value ] );

	return (
		<div className={ className }>
			<ReplacementVariableEditor
				{ ...field }
				{ ...props }
				content={ value }
				onChange={ handleChange }
				editorRef={ setEditorRef }
				onFocus={ handleFocus }
			/>
		</div>
	);
};

FormikReplacementVariableEditorField.propTypes = {
	name: PropTypes.string.isRequired,
	className: PropTypes.string,
};

export default FormikReplacementVariableEditorField;
