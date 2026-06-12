import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { getCampaignEditorSettings } from '../shared/campaignEditorSettings';
import { usePostMetaFieldWithDefault } from '../../hooks/usePostMetaFieldWithDefault';

export default function Edit( {
	context: { postType },
} ) {
	const {
		defaultAcceptsDonations,
		defaultHasTarget,
	} = getCampaignEditorSettings();
	const [ acceptsDonations ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_accepts_donations',
		defaultAcceptsDonations,
	);
	const [ hasTarget ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_has_target',
		defaultHasTarget,
	);
	const statusLabel = acceptsDonations === false
		? __( 'Donations disabled', 'fundrik' )
		: __( 'Campaign active', 'fundrik' );

	return (
		<div { ...useBlockProps() }>
			<section
				className="fundrik-campaign-summary"
				data-status={ acceptsDonations === false ? 'donations_disabled' : 'active' }
			>
				<p className="fundrik-campaign-summary__status">
					{ statusLabel }
				</p>
				<div className="fundrik-campaign-summary__metrics">
					<div className="fundrik-campaign-summary__metric" data-metric="collected">
						<span className="fundrik-campaign-summary__metric-label">
							{ __( 'Collected', 'fundrik' ) }
						</span>
						<strong className="fundrik-campaign-summary__metric-value">
							{ __( 'Updates on the site', 'fundrik' ) }
						</strong>
					</div>
					<div className="fundrik-campaign-summary__metric" data-metric="goal">
						<span className="fundrik-campaign-summary__metric-label">
							{ __( 'Goal', 'fundrik' ) }
						</span>
						<strong className="fundrik-campaign-summary__metric-value">
							{ hasTarget
								? __( 'Uses campaign target on the site', 'fundrik' )
								: __( 'No goal configured', 'fundrik' ) }
						</strong>
					</div>
					<div className="fundrik-campaign-summary__metric" data-metric="donations">
						<span className="fundrik-campaign-summary__metric-label">
							{ __( 'Donations', 'fundrik' ) }
						</span>
						<strong className="fundrik-campaign-summary__metric-value">
							{ __( 'Updates on the site', 'fundrik' ) }
						</strong>
					</div>
				</div>
			</section>
		</div>
	);
}
