import { usePostMetaField } from './usePostMetaField';

export function usePostMetaFieldWithDefault( postType, fieldName, defaultValue ) {

	const [ value, setValue ] = usePostMetaField( postType, fieldName );

	return [ value ?? defaultValue, setValue ];
}
