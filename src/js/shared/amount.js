const MINOR_UNITS_PER_MAJOR = 100;

function parseAmountToMinorUnits( value ) {

	if ( typeof value !== 'string' ) {
		return null;
	}

	const trimmed = value.trim();

	if ( ! /^\d+$/.test( trimmed ) ) {
		return null;
	}

	const amountMajor = Number.parseInt( trimmed, 10 );

	if ( Number.isNaN( amountMajor ) || amountMajor <= 0 ) {
		return null;
	}

	return amountMajor * MINOR_UNITS_PER_MAJOR;
}

export {
	parseAmountToMinorUnits,
};
