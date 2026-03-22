import { usePostMetaField } from '../../hooks/usePostMetaField';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { ToggleControl, TextControl } from '@wordpress/components';

export default function Edit({
	context: { postType },
}) {

	const [acceptsDonations, setAcceptsDonations] = usePostMetaField(postType, 'fundrik_campaign_accepts_donations');
	const [hasTarget, setHasTarget] = usePostMetaField(postType, 'fundrik_campaign_has_target');
	const [targetAmount, setTargetAmount] = usePostMetaField(postType, 'fundrik_campaign_target_amount');

	useEffect(() => {
		if ( ! hasTarget && targetAmount !== undefined ) {
			setTargetAmount( undefined );
		}
	}, [ hasTarget, targetAmount, setTargetAmount ]);

	return (
		<div {...useBlockProps()}>
			<ToggleControl
				label="Accepts Donations"
				checked={acceptsDonations}
				onChange={setAcceptsDonations}
			/>
			<ToggleControl
				label="Has Target"
				checked={hasTarget}
				onChange={setHasTarget}
			/>
			{ hasTarget ? (
				<TextControl
					label="Target Amount"
					type="number"
					value={targetAmount ?? ''}
					onChange={setTargetAmount}
				/>
			) : (
				<p className="fundrik-campaign-settings__hint">
					Enable target to set a fundraising amount.
				</p>
			) }
		</div>
	);
}

