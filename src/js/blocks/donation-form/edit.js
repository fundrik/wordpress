import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {

	const formLabel = '\u0424\u043e\u0440\u043c\u0430';

	return (
		<div {...useBlockProps()}>
			<div>{ formLabel }</div>
		</div>
	);
}
