import { usePostMetaField } from '../../hooks/usePostMetaField';
import { useBlockProps } from '@wordpress/block-editor';
import { ToggleControl, TextControl } from '@wordpress/components';

export default function Edit({
	context: { postType },
}) {

	const [isOpen, setIsOpen] = usePostMetaField(postType, 'fundrik_campaign_is_open');
	const [hasTarget, setHasTarget] = usePostMetaField(postType, 'fundrik_campaign_has_target');
	const [targetAmount, setTargetAmount] = usePostMetaField(postType, 'fundrik_campaign_target_amount');

	return (
		<div {...useBlockProps()}>
			<ToggleControl
				label="Is Open"
				checked={isOpen}
				onChange={setIsOpen}
			/>
			<ToggleControl
				label="Has Target"
				checked={hasTarget}
				onChange={setHasTarget}
			/>
			<TextControl
				label="Target Amount"
				type="number"
				value={targetAmount}
				onChange={setTargetAmount}
			/>
		</div>
	);
}
