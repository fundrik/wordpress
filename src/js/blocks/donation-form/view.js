const FORM_SELECTOR = '.wp-block-fundrik-donation-form .fundrik-donation-form';
const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

const parseAmountMinor = ( amountRaw ) => {

	const normalized = String( amountRaw ).trim();

	if ( ! /^\d+$/.test( normalized ) ) {
		return null;
	}

	const amountMajor = Number.parseInt( normalized, 10 );
	const amountMinor = amountMajor * 100;

	if ( ! Number.isInteger( amountMinor ) || amountMinor <= 0 ) {
		return null;
	}

	return amountMinor;
};

const setMessage = ( form, message, state ) => {

	const messageNode = form.querySelector( '.fundrik-donation-form__message' );

	if ( ! messageNode ) {
		return;
	}

	messageNode.textContent = message;
	messageNode.dataset.state = state;
};

const setFormBusy = ( form, isBusy ) => {

	const submitButton = form.querySelector( '.fundrik-donation-form__submit' );
	const amountInput = form.querySelector( '.fundrik-donation-form__amount' );

	if ( submitButton instanceof HTMLButtonElement ) {
		submitButton.disabled = isBusy;
	}

	if ( amountInput instanceof HTMLInputElement ) {
		amountInput.disabled = isBusy;
	}

	form.classList.toggle( 'is-busy', isBusy );
};

const formatUuidFromBytes = ( bytes ) => {

	const hex = Array.from(
		bytes,
		( byte ) => byte.toString( 16 ).padStart( 2, '0' ),
	);

	return `${ hex.slice( 0, 4 ).join( '' ) }-${ hex.slice( 4, 6 ).join( '' ) }-${ hex.slice( 6, 8 ).join( '' ) }-${ hex.slice( 8, 10 ).join( '' ) }-${ hex.slice( 10, 16 ).join( '' ) }`;
};

const generateDonationId = () => {

	if ( typeof window.crypto?.randomUUID === 'function' ) {
		return window.crypto.randomUUID();
	}

	const bytes = new Uint8Array( 16 );

	if ( typeof window.crypto?.getRandomValues === 'function' ) {
		window.crypto.getRandomValues( bytes );
	} else {
		for ( let index = 0; index < bytes.length; index += 1 ) {
			bytes[ index ] = Math.floor( Math.random() * 256 );
		}
	}

	bytes[ 6 ] = ( bytes[ 6 ] & 0x0f ) | 0x40;
	bytes[ 8 ] = ( bytes[ 8 ] & 0x3f ) | 0x80;

	return formatUuidFromBytes( bytes );
};

const ensureDonationId = ( form ) => {

	const existing = ( form.dataset.donationId || '' ).trim();

	if ( UUID_PATTERN.test( existing ) ) {
		return existing;
	}

	const generated = generateDonationId();
	form.dataset.donationId = generated;

	return generated;
};

const getErrorMessage = ( responseData ) => {

	if ( responseData && typeof responseData.message === 'string' && responseData.message !== '' ) {
		return responseData.message;
	}

	return 'Failed to submit donation.';
};

const handleSubmit = async ( event ) => {

	event.preventDefault();

	const form = event.currentTarget;

	if ( ! ( form instanceof HTMLFormElement ) ) {
		return;
	}

	const amountInput = form.querySelector( '.fundrik-donation-form__amount' );

	if ( ! ( amountInput instanceof HTMLInputElement ) ) {
		return;
	}

	const campaignId = Number.parseInt( form.dataset.campaignId || '0', 10 );
	const restUrl = form.dataset.restUrl || '';
	const amountMinor = parseAmountMinor( amountInput.value );
	const donationId = ensureDonationId( form );

	if ( ! Number.isInteger( campaignId ) || campaignId <= 0 ) {
		setMessage( form, 'Campaign ID is invalid.', 'error' );
		return;
	}

	if ( ! restUrl ) {
		setMessage( form, 'Donation endpoint URL is unavailable.', 'error' );
		return;
	}

	if ( ! UUID_PATTERN.test( donationId ) ) {
		setMessage( form, 'Donation ID is unavailable.', 'error' );
		return;
	}

	if ( amountMinor === null ) {
		setMessage( form, 'Amount must be a positive integer.', 'error' );
		return;
	}

	setFormBusy( form, true );
	setMessage( form, 'Submitting...', 'pending' );

	try {

		const response = await fetch( restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				donation_id: donationId,
				campaign_id: campaignId,
				amount: amountMinor,
			} ),
		} );

		const responseData = await response.json().catch( () => ( {} ) );

		if ( ! response.ok ) {
			setMessage( form, getErrorMessage( responseData ), 'error' );
			return;
		}

		amountInput.value = amountInput.defaultValue;
		setMessage( form, 'Donation submitted.', 'success' );
		form.dataset.donationId = generateDonationId();

	} catch ( error ) {

		setMessage( form, 'Failed to submit donation.', 'error' );

	} finally {
		setFormBusy( form, false );
	}
};

const initDonationForms = () => {

	const forms = document.querySelectorAll( FORM_SELECTOR );

	forms.forEach( ( formNode ) => {

		if ( ! ( formNode instanceof HTMLFormElement ) ) {
			return;
		}

		formNode.addEventListener( 'submit', handleSubmit );
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initDonationForms );
} else {
	initDonationForms();
}
