function isValidAmount( value ) {

	return Number.isInteger( value ) && value > 0;
}

function resolveAmount( value ) {

	if ( typeof value !== 'string' || ! /^[0-9]+$/.test( value ) ) {
		return null;
	}

	const amountMajor = Number.parseInt( value, 10 );

	if ( ! isValidAmount( amountMajor ) ) {
		return null;
	}

	return amountMajor * 100;
}

export { isValidAmount, resolveAmount };
