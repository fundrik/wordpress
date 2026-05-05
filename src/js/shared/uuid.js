const UUID_V4_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

function formatUuidFromBytes( bytes ) {

	const hex = Array.from(
		bytes,
		( byte ) => byte.toString( 16 ).padStart( 2, '0' ),
	);

	return `${ hex.slice( 0, 4 ).join( '' ) }-${ hex.slice( 4, 6 ).join( '' ) }-${ hex.slice( 6, 8 ).join( '' ) }-${ hex.slice( 8, 10 ).join( '' ) }-${ hex.slice( 10, 16 ).join( '' ) }`;
}

function isUuidV4( value ) {

	if ( typeof value !== 'string' ) {
		return false;
	}

	return UUID_V4_PATTERN.test( value.trim() );
}

function generateUuidV4() {

	const cryptoApi = window.crypto;

	if ( typeof cryptoApi?.randomUUID === 'function' ) {
		return cryptoApi.randomUUID();
	}

	if ( typeof cryptoApi?.getRandomValues !== 'function' ) {
		return '';
	}

	const bytes = new Uint8Array( 16 );
	cryptoApi.getRandomValues( bytes );

	bytes[ 6 ] = ( bytes[ 6 ] & 0x0f ) | 0x40;
	bytes[ 8 ] = ( bytes[ 8 ] & 0x3f ) | 0x80;

	return formatUuidFromBytes( bytes );
}

export { generateUuidV4, isUuidV4 };
