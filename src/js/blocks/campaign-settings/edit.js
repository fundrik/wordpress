import { useEffect, useState } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { ToggleControl, TextControl } from '@wordpress/components';
import { getCampaignEditorSettings } from '../shared/campaignEditorSettings';
import { usePostMetaFieldWithDefault } from '../../hooks/usePostMetaFieldWithDefault';

export default function Edit( {
	context: { postType },
} ) {
	const {
		defaultAcceptsDonations,
		defaultHasTarget,
	} = getCampaignEditorSettings();

	const [ acceptsDonations, setAcceptsDonations ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_accepts_donations',
		defaultAcceptsDonations,
	);

	const [ hasTarget, setHasTarget ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_has_target',
		defaultHasTarget,
	);

	const [ targetAmount, setTargetAmount ] = usePostMetaFieldWithDefault(
		postType,
		'fundrik_campaign_target_amount',
		'',
	);

	const [ targetAmountInput, setTargetAmountInput ] = useState(
		() => String( targetAmount ),
	);

	useEffect( () => {
		setTargetAmountInput( String( targetAmount ) );
	}, [ targetAmount ] );

	const handleTargetAmountChange = ( nextValue ) => {
		setTargetAmountInput( nextValue );

		if ( nextValue.trim() === '' ) {
			setTargetAmount( undefined );
			return;
		}

		const nextMajorUnits = Number.parseInt( nextValue, 10 );

		if ( Number.isNaN( nextMajorUnits ) ) {
			return;
		}

		setTargetAmount( nextMajorUnits );
	};

	return (
		<div { ...useBlockProps() }>
			<ToggleControl
				label="Accepts Donations"
				checked={ acceptsDonations }
				onChange={ setAcceptsDonations }
			/>
			<ToggleControl
				label="Has Target"
				checked={ hasTarget }
				onChange={ setHasTarget }
			/>
			{ hasTarget ? (
				<TextControl
					label="Target Amount"
					type="text"
					inputMode="numeric"
					value={ targetAmountInput }
					onChange={ handleTargetAmountChange }
				/>
			) : (
				<p className="fundrik-campaign-settings__hint">
					Enable target to set a fundraising amount.
				</p>
			) }
		</div>
	);
}
