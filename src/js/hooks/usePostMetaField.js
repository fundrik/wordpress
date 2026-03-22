import { useEntityProp } from '@wordpress/core-data';

export function usePostMetaField(postType, fieldName) {

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	const value = meta?.[fieldName];

	const setValue = (newValue) => {
		const nextMeta = { ...(meta ?? {}) };

		if (newValue === undefined) {
			delete nextMeta[fieldName];
		} else {
			nextMeta[fieldName] = newValue;
		}

		setMeta(nextMeta);
	};

	return [value, setValue];
}
