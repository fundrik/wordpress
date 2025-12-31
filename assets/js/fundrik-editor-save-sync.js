(() => {
	if (!wp?.apiFetch || !wp?.data) {
		return;
	}

	const VERSION_KEY = 'fundrik_campaign_version';
	const POST_TYPE = 'fundrik_campaign';
	const REST_PATH_FRAGMENT = `/wp/v2/${POST_TYPE}/`;

	wp.apiFetch.use((options, next) => {
		const method = (options.method || 'GET').toUpperCase();
		const path = options.path || '';

		const is_campaign_update =
			path.startsWith(REST_PATH_FRAGMENT) &&
			(method === 'POST' || method === 'PUT' || method === 'PATCH');

		if (!is_campaign_update) {
			return next(options);
		}

		const editor = wp.data.select('core/editor');
		const expected_version = editor.getCurrentPostAttribute('meta')?.[VERSION_KEY];

		if (expected_version == null) {
			return next(options);
		}

		const data = {
			...(options.data || {}),
			meta: {
				...((options.data && options.data.meta) || {}),
				[VERSION_KEY]: expected_version,
			},
		};

		return next({ ...options, data });
	});
})();
