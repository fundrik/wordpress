export const getGlobalEditorSettings = ( {
	globalKey,
	validators,
} ) => {

	const settings = globalThis[ globalKey ];

	if (
		settings === null
		|| typeof settings !== 'object'
		|| Array.isArray( settings )
	) {
		throw new Error( `${ globalKey } is missing or invalid.` );
	}

	return Object.fromEntries(
		Object.entries( validators ).map( ( [ key, validate ] ) => {

			const value = settings[ key ];

			if ( validate( value ) ) {
				return [ key, value ];
			}

			throw new Error( `${ globalKey }.${ key } is missing or invalid.` );
		} ),
	);
};
