import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { getCampaignEditorSettings } from '../shared/campaignEditorSettings';
import { getDonationFormEditorSettings } from '../shared/donationFormEditorSettings';
import { usePostMetaFieldWithDefault } from '../../hooks/usePostMetaFieldWithDefault';

export default function Edit( {
	clientId,
	context: { postType },
} ) {

	const amountInputId = `fundrik-donation-amount-${ clientId }`;
	const { defaultAmount, defaultAmountLabel } = getDonationFormEditorSettings();
	const { defaultAcceptsDonations } = getCampaignEditorSettings();
	const [ acceptsDonations ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_accepts_donations',
		defaultAcceptsDonations,
	);

	if ( acceptsDonations === false ) {
		return (
			<div { ...useBlockProps() }>
				<p className="fundrik-donation-form__message" aria-live="polite">
					{ __( 'Donation form is hidden because donations are disabled for this campaign.', 'fundrik' ) }
				</p>
			</div>
		);
	}

	return (
		<div { ...useBlockProps() }>
			<form className="fundrik-donation-form">
				<label className="fundrik-donation-form__label" htmlFor={ amountInputId }>
					{ defaultAmountLabel }
				</label>
				<input
					id={ amountInputId }
					className="fundrik-donation-form__amount"
					type="text"
					name="amount"
					value={ String( defaultAmount ) }
					disabled
				/>
				<button className="fundrik-donation-form__submit" type="button" disabled>
					{ __( 'Donate', 'fundrik' ) }
				</button>
			</form>
		</div>
	);
}
