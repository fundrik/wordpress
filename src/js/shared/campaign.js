function isValidCampaignId( value ) {

	return Number.isInteger( value ) && value > 0;
}

function resolveCampaignId( value ) {

	if ( typeof value !== 'string' || ! /^[0-9]+$/.test( value ) ) {
		return null;
	}

	const campaignId = Number.parseInt( value, 10 );

	if ( ! isValidCampaignId( campaignId ) ) {
		return null;
	}

	return campaignId;
}

export { isValidCampaignId, resolveCampaignId };
