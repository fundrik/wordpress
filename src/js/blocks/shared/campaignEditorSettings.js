import { getGlobalEditorSettings } from './getGlobalEditorSettings';

const campaignEditorSettingsValidators = {
	defaultAcceptsDonations: ( value ) => typeof value === 'boolean',
	defaultHasTarget: ( value ) => typeof value === 'boolean',
};

export const getCampaignEditorSettings = () =>
	getGlobalEditorSettings( {
		globalKey: 'fundrikCampaignEditorSettings',
		validators: campaignEditorSettingsValidators,
	} );
