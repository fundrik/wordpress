import { getGlobalEditorSettings } from './getGlobalEditorSettings';

const donationFormEditorSettingsValidators = {
	defaultAmount: Number.isInteger,
	defaultAmountLabel: ( value ) => typeof value === 'string',
};

export const getDonationFormEditorSettings = () =>
	getGlobalEditorSettings( {
		globalKey: 'fundrikDonationFormEditorSettings',
		validators: donationFormEditorSettingsValidators,
	} );
