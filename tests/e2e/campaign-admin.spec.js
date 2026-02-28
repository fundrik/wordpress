const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const getSaveButton = ( page ) =>
	page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', { name: /^(Save draft|Save)$/ } );

const getCampaignByPostId = ( requestUtils, postId ) =>
	requestUtils.rest( {
		path: `/wp/v2/fundrik_campaign/${ postId }`,
		params: { context: 'edit' },
	} );

const getPostIdFromEditorStore = ( page ) =>
	page.evaluate( () => wp.data.select( 'core/editor' ).getCurrentPostId() );

const setCampaignTitle = async ( editor, title ) => {
	await editor.canvas.getByRole( 'textbox', { name: 'Add title' } ).fill( title );
};

const saveCampaignAndGetResponse = async ( page, postId ) => {
	await expect( getSaveButton( page ) ).toBeEnabled();

	const responsePromise = page.waitForResponse( ( response ) => {
		const requestMethod = response.request().method().toUpperCase();
		const url = decodeURIComponent( response.url() );
		const matchesPostSaveRoute =
			postId === undefined || postId === null
				? /\/wp\/v2\/fundrik_campaign\/\d+/.test( url )
				: url.includes( `/wp/v2/fundrik_campaign/${ postId }` );

		return (
			( requestMethod === 'POST' || requestMethod === 'PUT' || requestMethod === 'PATCH' ) &&
			matchesPostSaveRoute &&
			! url.includes( '/autosaves' )
		);
	} );

	await getSaveButton( page ).click();

	return responsePromise;
};

test.describe( 'Fundrik campaign admin', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( 'fundrik' );
	} );

	test( 'shows campaigns list screen', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'edit.php', 'post_type=fundrik_campaign' );

		await expect( page.locator( 'h1.wp-heading-inline', { hasText: 'Campaigns' } ) ).toBeVisible();
		await expect( page.locator( '#menu-posts-fundrik_campaign' ) ).toBeVisible();
	} );

	test( 'creates campaign draft and persists custom meta', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Draft',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign Draft' );
		await editor.canvas.getByLabel( 'Has Target' ).check();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '2500' );

		const saveResponse = await saveCampaignAndGetResponse( page );
		expect( saveResponse.ok() ).toBe( true );

		const postId = await getPostIdFromEditorStore( page );
		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( true );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBe( 2500 );
	} );

	test( 'rejects save when target is enabled and amount is zero', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Invalid Target Zero',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign Invalid Target Zero' );
		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );
		const postId = await getPostIdFromEditorStore( page );

		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		await editor.canvas.getByLabel( 'Has Target' ).check();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '0' );

		const failedSaveResponse = await saveCampaignAndGetResponse( page, postId );
		const failedPayload = await failedSaveResponse.json();

		expect( failedSaveResponse.status() ).toBe( 422 );
		expect( failedPayload?.code ).toBe( 'fundrik_campaign_validation_failed' );
		expect( failedPayload?.message ).toMatch(
			/Target amount must be positive when targeting is enabled/i,
		);

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBe( 0 );
	} );

	test( 'rejects save when target is disabled and amount is non-zero', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Invalid Target Disabled',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign Invalid Target Disabled' );
		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );
		const postId = await getPostIdFromEditorStore( page );

		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		await editor.canvas.getByLabel( 'Has Target' ).uncheck();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '1500' );

		const failedSaveResponse = await saveCampaignAndGetResponse( page, postId );
		const failedPayload = await failedSaveResponse.json();

		expect( failedSaveResponse.status() ).toBe( 422 );
		expect( failedPayload?.code ).toBe( 'fundrik_campaign_validation_failed' );
		expect( failedPayload?.message ).toMatch(
			/Target amount must be zero when targeting is disabled/i,
		);

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBe( 0 );
	} );

	test( 'shows version mismatch when editor data is stale', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Version Mismatch',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign Version Mismatch' );
		await editor.canvas.getByLabel( 'Has Target' ).check();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '500' );
		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );
		const postId = await getPostIdFromEditorStore( page );

		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const currentPost = await getCampaignByPostId( requestUtils, postId );
		const currentVersion = currentPost?.meta?.fundrik_campaign_version;

		expect( typeof currentVersion ).toBe( 'number' );

		await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/fundrik_campaign/${ postId }`,
			data: {
				id: postId,
				title: 'External Update',
				meta: {
					fundrik_campaign_version: currentVersion,
					fundrik_campaign_is_open: true,
					fundrik_campaign_has_target: false,
					fundrik_campaign_target_amount: 0,
				},
			},
		} );

		await editor.canvas.getByLabel( 'Has Target' ).check();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '900' );

		const failedSaveResponse = await saveCampaignAndGetResponse( page, postId );
		const failedPayload = await failedSaveResponse.json();

		expect( failedSaveResponse.status() ).toBe( 409 );
		expect( failedPayload?.code ).toBe( 'fundrik_campaign_version_mismatch' );
		expect( failedPayload?.message ).toMatch(
			/Campaign data is out of date\. Refresh the page and try again\./i,
		);

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.title?.raw ).toBe( 'External Update' );
		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBe( 0 );
	} );

	test( 'deletes campaign post', async ( { admin, editor, page, requestUtils } ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign To Delete',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign To Delete' );
		await editor.canvas.getByLabel( 'Has Target' ).check();
		await editor.canvas.getByLabel( 'Target Amount' ).fill( '1500' );

		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );

		const postId = await getPostIdFromEditorStore( page );
		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const deleteResponse = await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/fundrik_campaign/${ postId }`,
			params: { force: true },
		} );

		expect( deleteResponse?.deleted ).toBe( true );
		expect( deleteResponse?.previous?.id ).toBe( postId );

		let getDeletedError = null;
		try {
			await getCampaignByPostId( requestUtils, postId );
		} catch ( error ) {
			getDeletedError = error;
		}

		expect( getDeletedError?.code ).toBe( 'rest_post_invalid_id' );
		expect( getDeletedError?.data?.status ).toBe( 404 );
	} );

	test( 'preloads campaign settings block and keeps it locked', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Block Lock',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		const blockState = await page.evaluate( () => {
			const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
			const firstBlock = blocks[ 0 ];
			const lock = firstBlock?.attributes?.lock ?? null;
			const canRemove =
				firstBlock == null
					? null
					: wp.data.select( 'core/block-editor' ).canRemoveBlock( firstBlock.clientId );

			return {
				total: blocks.length,
				firstName: firstBlock?.name ?? null,
				lock,
				canRemove,
			};
		} );

		expect( blockState.total ).toBe( 1 );
		expect( blockState.firstName ).toBe( 'fundrik/campaign-settings' );
		expect( blockState.lock ).toEqual( { move: true, remove: true } );
		expect( blockState.canRemove ).toBe( false );
	} );

	test( 'hides campaign settings block from inserter and marks it single-use', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Block Multiple',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		const insertionState = await page.evaluate( () => {
			const blockEditorSelect = wp.data.select( 'core/block-editor' );
			const blockType = wp.blocks.getBlockType( 'fundrik/campaign-settings' );
			const campaignSettingsBlocksCount = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/campaign-settings' ).length;

			return {
				campaignSettingsBlocksCount,
				supportsInserter: blockType?.supports?.inserter,
				supportsMultiple: blockType?.supports?.multiple,
			};
		} );

		expect( insertionState.campaignSettingsBlocksCount ).toBe( 1 );
		expect( insertionState.supportsInserter ).toBe( false );
		expect( insertionState.supportsMultiple ).toBe( false );
	} );

	test( 'keeps campaign settings block unavailable in regular posts', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'post',
			title: 'E2E Regular Post',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		const blockVisibility = await page.evaluate( () => {
			const blockEditorSelect = wp.data.select( 'core/block-editor' );
			const blocks = blockEditorSelect.getBlocks();
			const allowedBlockTypes = blockEditorSelect.getSettings().allowedBlockTypes;
			const hasCampaignSettingsBlock = blocks.some(
				( block ) => block.name === 'fundrik/campaign-settings',
			);

			const isAllowedInCurrentEditor = Array.isArray( allowedBlockTypes )
				? allowedBlockTypes.includes( 'fundrik/campaign-settings' )
				: allowedBlockTypes === true;

			return {
				hasCampaignSettingsBlock,
				isAllowedInCurrentEditor,
			};
		} );

		expect( blockVisibility.hasCampaignSettingsBlock ).toBe( false );
		expect( blockVisibility.isAllowedInCurrentEditor ).toBe( false );
	} );
} );
