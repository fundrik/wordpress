import { __ } from '@wordpress/i18n';
import { resolveCampaignId } from '../../shared/campaign';
import { parseAmountToMinorUnits } from '../../shared/amount';
import { generateDonationId } from '../../shared/donation';

const MESSAGE_STATE = Object.freeze( {
	PENDING: 'pending',
	SUCCESS: 'success',
	ERROR: 'error',
} );
const FORM_STATE = Object.freeze( {
	IDLE: 'idle',
	SUBMITTING: 'submitting',
	SUCCESS: 'success',
	ERROR: 'error',
} );
const donationForms = new WeakMap();

document.addEventListener(
	'DOMContentLoaded',
	() => document.querySelectorAll( 'form.fundrik-donation-form' ).forEach( initDonationForm ),
	{ once: true }
);

function initDonationForm( form ) {

	getDonationForm( form ).init();
}

function getDonationForm( form ) {

	let donationForm = donationForms.get( form );

	if ( ! donationForm ) {
		donationForm = new DonationForm( form );
		donationForms.set( form, donationForm );
	}

	return donationForm;
}

class DonationForm {

	constructor( form ) {
		this.form = form;
		this.elements = null;
		this.campaignId = null;
		this.restUrl = '';
		this.donationId = generateDonationId();
		this.state = FORM_STATE.IDLE;
		this.initialized = false;
		this.handleSubmit = this.handleSubmit.bind( this );
	}

	init() {

		if ( this.initialized ) {
			console.warn( __( 'Donation form initialization skipped: already initialized.', 'fundrik' ), this.form );
			return;
		}

		this.elements = this.resolveElements();

		if ( ! this.elements ) {
			console.warn( __( 'Donation form initialization failed: missing required elements.', 'fundrik' ), this.form );
			return;
		}

		const formConfig = this.resolveFormConfig();

		if ( ! formConfig ) {
			console.warn( __( 'Donation form initialization failed: invalid form config.', 'fundrik' ), this.form );
			return;
		}

		this.campaignId = formConfig.campaignId;
		this.restUrl = formConfig.restUrl;

		this.form.addEventListener( 'submit', this.handleSubmit );
		this.initialized = true;
	}

	resolveElements() {

		const amountInput = this.form.querySelector( 'input.fundrik-donation-form__amount-input' );

		if ( ! amountInput ) {
			return null;
		}

		const submitButton = this.form.querySelector( 'button.fundrik-donation-form__submit' );
		const messageElement = this.form.querySelector( 'div.fundrik-donation-form__message' );

		return {
			amountInput,
			submitButton,
			messageElement,
		};
	}

	resolveFormConfig() {

		const campaignId = resolveCampaignId( this.form.dataset.campaignId );

		if ( campaignId === null ) {
			return null;
		}

		const restUrl = this.form.dataset.restUrl;

		if ( ! restUrl ) {
			return null;
		}

		return {
			campaignId,
			restUrl,
		};
	}

	async handleSubmit( event ) {

		event.preventDefault();

		if ( this.state === FORM_STATE.SUBMITTING ) {
			return;
		}

		const requestData = this.resolveSubmitRequestData();

		if ( requestData.error ) {
			this.state = FORM_STATE.ERROR;
			this.showError( requestData.error );
			return;
		}

		this.state = FORM_STATE.SUBMITTING;
		this.showPending();

		try {
			const result = await this.submitDonation( requestData );

			if ( ! result.ok ) {
				this.state = FORM_STATE.ERROR;
				this.showError( result.message );
				return;
			}

			this.resetFormAfterSuccess();
			this.state = FORM_STATE.SUCCESS;
			this.showSuccess();

		} finally {
			if ( this.state === FORM_STATE.SUBMITTING ) {
				this.state = FORM_STATE.IDLE;
			}

			this.finish();
		}
	}

	resolveSubmitRequestData() {

		const amount = parseAmountToMinorUnits( this.elements.amountInput.value );

		if ( amount === null ) {
			return { error: __( 'Amount must be a positive integer.', 'fundrik' ) };
		}

		return {
			restUrl: this.restUrl,
			payload: {
				donation_id: this.donationId,
				campaign_id: this.campaignId,
				amount,
			},
		};
	}

	async submitDonation( request ) {

		try {
			const response = await fetch( request.restUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( request.payload ),
			} );

			const responseData = await response.json().catch( () => ( {} ) );

			if ( ! response.ok ) {
				return {
					ok: false,
					message: this.getErrorMessage( responseData ),
				};
			}

			return { ok: true };

		} catch {
			return {
				ok: false,
				message: __( 'Failed to submit donation.', 'fundrik' ),
			};
		}
	}

	resetFormAfterSuccess() {

		this.elements.amountInput.value = this.elements.amountInput.defaultValue;

		this.donationId = generateDonationId();
	}

	showPending() {

		this.setFormBusy( true );
		this.setMessage( __( 'Submitting...', 'fundrik' ), MESSAGE_STATE.PENDING );
	}

	showSuccess() {

		this.setMessage( __( 'Donation submitted.', 'fundrik' ), MESSAGE_STATE.SUCCESS );
	}

	showError( message ) {

		this.setMessage( message, MESSAGE_STATE.ERROR );
	}

	finish() {

		this.setFormBusy( false );
	}

	setFormBusy( isBusy ) {

		if ( this.elements.submitButton ) {
			this.elements.submitButton.disabled = isBusy;
		}

		this.elements.amountInput.disabled = isBusy;
		this.form.classList.toggle( 'is-busy', isBusy );
	}

	setMessage( message, state ) {

		if ( ! this.elements.messageElement ) {
			return;
		}

		this.elements.messageElement.textContent = message;
		this.elements.messageElement.dataset.state = state;
	}

	getErrorMessage( responseData ) {

		if ( responseData && typeof responseData.message === 'string' && responseData.message !== '' ) {
			return responseData.message;
		}

		return __( 'Failed to submit donation.', 'fundrik' );
	}
}
