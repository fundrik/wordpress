import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { getCampaignEditorSettings } from '../shared/campaignEditorSettings';
import { getDonationFormEditorSettings } from '../shared/donationFormEditorSettings';
import { usePostMetaFieldWithDefault } from '../../hooks/usePostMetaFieldWithDefault';

export default function Edit( {
	context: { postType },
} ) {
	const blockProps = useBlockProps();
	const { defaultAmount, defaultAmountLabel } = getDonationFormEditorSettings();
	const { defaultAcceptsDonations } = getCampaignEditorSettings();
	const [ acceptsDonations ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_accepts_donations',
		defaultAcceptsDonations,
	);

	if ( acceptsDonations === false ) {
		return (
			<div { ...blockProps }>
				<p className="fundrik-donation-form__message" aria-live="polite">
					{ __( 'Donation form is hidden because donations are disabled for this campaign.', 'fundrik' ) }
				</p>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<div className="fundrik-donation-form">
				<div className="fundrik-donation-form__amount-field">
					<span className="fundrik-donation-form__amount-label">
						{ defaultAmountLabel }
					</span>
					<input
						className="fundrik-donation-form__amount-input"
						type="text"
						value={ String( defaultAmount ) }
						disabled
					/>
				</div>
				<button className="fundrik-donation-form__submit" type="button" disabled>
					{ __( 'Donate', 'fundrik' ) }
				</button>
			</div>
		</div>
	);
}
