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

const editCampaignFromEditorStore = async ( page, { title, hasTarget, targetAmount } ) => {
	await page.waitForFunction( () =>
		typeof wp !== 'undefined' &&
		typeof wp.data !== 'undefined' &&
		typeof wp.data.dispatch === 'function' &&
		typeof wp.data.select === 'function' &&
		typeof wp.data.select( 'core/editor' )?.getCurrentPostId === 'function'
	);

	await page.evaluate(
		( payload ) => {
			const nextPost = {};
			const nextMeta = {};

			if ( payload.title !== undefined ) {
				nextPost.title = payload.title;
			}

			if ( payload.hasTarget !== undefined ) {
				nextMeta.fundrik_campaign_has_target = payload.hasTarget;
			}

			if ( payload.targetAmount !== undefined ) {
				nextMeta.fundrik_campaign_target_amount = payload.targetAmount;
			}

			if ( Object.keys( nextMeta ).length > 0 ) {
				nextPost.meta = nextMeta;
			}

			wp.data.dispatch( 'core/editor' ).editPost( nextPost );
		},
		{ title, hasTarget, targetAmount },
	);
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

const saveCampaignFromEditorStoreAndGetResponse = async ( page, postId ) => {
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

	await page.waitForFunction( () =>
		typeof wp !== 'undefined' &&
		typeof wp.data !== 'undefined' &&
		typeof wp.data.dispatch === 'function' &&
		typeof wp.data.dispatch( 'core/editor' )?.savePost === 'function'
	);

	await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );

	return responsePromise;
};

test.describe( 'Fundrik campaign admin', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( 'fundrik' );
	} );

	test( 'shows campaigns list screen', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'edit.php', 'post_type=fundrik_campaign' );

		await expect( page.locator( 'h1.wp-heading-inline', { hasText: 'Campaigns' } ) ).toBeVisible();
		await expect( page.locator( '#toplevel_page_fundrik' ) ).toBeVisible();
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
		await editor.canvas.getByLabel( /Target Amount/i ).fill( '25' );

		const saveResponse = await saveCampaignAndGetResponse( page );
		expect( saveResponse.ok() ).toBe( true );

		const postId = await getPostIdFromEditorStore( page );
		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( true );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBe( 25 );
	} );

	test( 'creates campaign draft without target amount when target stays disabled', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Draft Without Target',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Campaign Draft Without Target' );

		const saveResponse = await saveCampaignAndGetResponse( page );
		expect( saveResponse.ok() ).toBe( true );

		const postId = await getPostIdFromEditorStore( page );
		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBeNull();
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
		await editor.canvas.getByLabel( /Target Amount/i ).fill( '0' );

		const failedSaveResponse = await saveCampaignAndGetResponse( page, postId );
		const failedPayload = await failedSaveResponse.json();

		expect( failedSaveResponse.status() ).toBe( 422 );
		expect( failedPayload?.code ).toBe( 'fundrik_campaign_validation_failed' );
		expect( failedPayload?.message ).toMatch(
			/Target amount must be positive when targeting is enabled/i,
		);

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBeNull();
	} );

	test( 'ignores amount when target is disabled', async ( {
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

		await editCampaignFromEditorStore( page, {
			hasTarget: false,
			targetAmount: 1_500,
		} );

		const saveResponse = await saveCampaignAndGetResponse( page, postId );
		expect( saveResponse.ok() ).toBe( true );

		const response = await getCampaignByPostId( requestUtils, postId );

		expect( response?.meta?.fundrik_campaign_has_target ).toBe( false );
		expect( response?.meta?.fundrik_campaign_target_amount ).toBeNull();
	} );

	test( 'shows version mismatch when editor data is stale', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Campaign Version Mismatch',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await editCampaignFromEditorStore( page, {
			title: 'E2E Campaign Version Mismatch',
			hasTarget: true,
			targetAmount: 500,
		} );

		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );
		const postId = await getPostIdFromEditorStore( page );

		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const secondPage = await page.context().newPage();
		try {
			await secondPage.goto( `/wp-admin/post.php?post=${ postId }&action=edit` );

			await editCampaignFromEditorStore( secondPage, {
				title: 'External Update',
				hasTarget: false,
			targetAmount: null,
			} );

			const secondTabSaveResponse = await saveCampaignFromEditorStoreAndGetResponse(
				secondPage,
				postId,
			);
			expect( secondTabSaveResponse.ok() ).toBe( true );

			await editCampaignFromEditorStore( page, {
				hasTarget: true,
				targetAmount: 900,
			} );

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
			expect( response?.meta?.fundrik_campaign_target_amount ).toBeNull();
		} finally {
			await secondPage.close();
		}
	} );

	test( 'submits donation from frontend form and blocks replay with same idempotency key', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'fundrik_campaign',
			title: 'E2E Front Donation Submit',
			showWelcomeGuide: false,
			fullscreenMode: false,
		} );

		await setCampaignTitle( editor, 'E2E Front Donation Submit' );

		const initialSaveResponse = await saveCampaignAndGetResponse( page );
		expect( initialSaveResponse.ok() ).toBe( true );

		const postId = await getPostIdFromEditorStore( page );
		expect( typeof postId ).toBe( 'number' );
		expect( postId ).toBeGreaterThan( 0 );

		const frontendPost = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			params: { context: 'edit' },
			data: {
				status: 'publish',
				title: 'E2E Front Donation Form Host',
				content: '<!-- wp:fundrik/donation-form /-->',
			},
		} );

		expect( frontendPost?.status ).toBe( 'publish' );
		expect( typeof frontendPost?.link ).toBe( 'string' );
		expect( frontendPost?.content?.raw ).toContain( 'fundrik/donation-form' );

		await page.goto( frontendPost.link );

		const form = page.locator( '.fundrik-donation-form' );
		const amountInput = page.locator( '.fundrik-donation-form__amount-input' );
		const submitButton = page.locator( '.fundrik-donation-form__submit' );
		const message = page.locator( '.fundrik-donation-form__message' );

		await expect( form ).toBeVisible();
		await amountInput.fill( '123' );

		const initialDonationId = await form.getAttribute( 'data-donation-id' );
		const restUrl = await form.getAttribute( 'data-rest-url' );

		expect( initialDonationId ).toBeTruthy();
		expect( restUrl ).toBeTruthy();

		await page.evaluate(
			( { campaignPostId } ) => {
				const formNode = document.querySelector( '.fundrik-donation-form' );

				if ( ! ( formNode instanceof HTMLFormElement ) ) {
					return;
				}

				formNode.dataset.campaignId = String( campaignPostId );
			},
			{ campaignPostId: postId },
		);

		const donationResponsePromise = page.waitForResponse(
			( response ) =>
				response.request().method().toUpperCase() === 'POST' &&
				decodeURIComponent( response.url() ).includes( '/fundrik/v1/donations' ),
		);

		await submitButton.click();

		const donationResponse = await donationResponsePromise;
		expect( donationResponse.status() ).toBe( 201 );

		const donationPayload = await donationResponse.json();
		expect( donationPayload?.campaign_id ).toBe( postId );
		expect( donationPayload?.amount ).toBe( 12300 );
		expect( donationPayload?.status ).toBe( 'pending' );
		expect( typeof donationPayload?.id ).toBe( 'string' );

		await expect( message ).toHaveText( 'Donation submitted.' );
		await expect( message ).toHaveAttribute( 'data-state', 'success' );

		const donationIdAfterSuccess = await form.getAttribute( 'data-donation-id' );
		expect( donationIdAfterSuccess ).toBeTruthy();
		expect( donationIdAfterSuccess ).not.toBe( initialDonationId );

		const duplicateResult = await page.evaluate(
			async ( { url, donationId, postIdValue } ) => {
				const response = await fetch( url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( {
						donation_id: donationId,
						campaign_id: postIdValue,
						amount: 12300,
					} ),
				} );

				const body = await response.json().catch( () => ( {} ) );

				return {
					status: response.status,
					body,
				};
			},
			{
				url: restUrl,
				donationId: initialDonationId,
				postIdValue: postId,
			},
		);

		expect( duplicateResult.status ).toBe( 201 );
		expect( duplicateResult.body?.id ).toBe( donationPayload.id );
		expect( duplicateResult.body?.campaign_id ).toBe( postId );
		expect( duplicateResult.body?.amount ).toBe( 12300 );
	} );

	test( 'preloads campaign template blocks with expected lock state', async ( {
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
			const blockEditorSelect = wp.data.select( 'core/block-editor' );
			const blocks = blockEditorSelect.getBlocks();
			const firstBlock = blocks[ 0 ];
			const secondBlock = blocks[ 1 ];
			const thirdBlock = blocks[ 2 ];
			const campaignSettingsLock = firstBlock?.attributes?.lock ?? null;
			const campaignSettingsCanRemove =
				firstBlock == null
					? null
					: blockEditorSelect.canRemoveBlock( firstBlock.clientId );
			const campaignSummaryLock = secondBlock?.attributes?.lock ?? null;
			const campaignSummaryCanRemove =
				secondBlock == null
					? null
					: blockEditorSelect.canRemoveBlock( secondBlock.clientId );
			const donationFormLock = thirdBlock?.attributes?.lock ?? null;
			const donationFormCanRemove =
				thirdBlock == null
					? null
					: blockEditorSelect.canRemoveBlock( thirdBlock.clientId );
			const hasCampaignSummaryBlock = blocks.some(
				( block ) => block.name === 'fundrik/campaign-summary',
			);
			const hasDonationFormBlock = blocks.some(
				( block ) => block.name === 'fundrik/donation-form',
			);

			return {
				total: blocks.length,
				firstName: firstBlock?.name ?? null,
				secondName: secondBlock?.name ?? null,
				thirdName: thirdBlock?.name ?? null,
				campaignSettingsLock,
				campaignSettingsCanRemove,
				campaignSummaryLock,
				campaignSummaryCanRemove,
				hasCampaignSummaryBlock,
				donationFormLock,
				donationFormCanRemove,
				hasDonationFormBlock,
			};
		} );

		expect( blockState.total ).toBe( 3 );
		expect( blockState.firstName ).toBe( 'fundrik/campaign-settings' );
		expect( blockState.secondName ).toBe( 'fundrik/campaign-summary' );
		expect( blockState.thirdName ).toBe( 'fundrik/donation-form' );
		expect( blockState.hasCampaignSummaryBlock ).toBe( true );
		expect( blockState.hasDonationFormBlock ).toBe( true );
		expect( blockState.campaignSettingsLock ).toEqual( { move: true, remove: true } );
		expect( blockState.campaignSettingsCanRemove ).toBe( false );
		expect( blockState.campaignSummaryLock ).toBeNull();
		expect( blockState.campaignSummaryCanRemove ).toBe( true );
		expect( blockState.donationFormLock ).toEqual( { move: false, remove: true } );
		expect( blockState.donationFormCanRemove ).toBe( false );
	} );

	test( 'keeps required campaign blocks single-use and allows repeated summaries', async ( {
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
			const blockEditorDispatch = wp.data.dispatch( 'core/block-editor' );
			const campaignSettingsBlockType = wp.blocks.getBlockType( 'fundrik/campaign-settings' );
			const campaignSummaryBlockType = wp.blocks.getBlockType( 'fundrik/campaign-summary' );
			const donationFormBlockType = wp.blocks.getBlockType( 'fundrik/donation-form' );
			const campaignSettingsBlocksCount = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/campaign-settings' ).length;
			const campaignSummaryBlocksCount = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/campaign-summary' ).length;
			const initialDonationFormBlocksCount = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/donation-form' ).length;
			const canInsertCampaignSummaryBlock = blockEditorSelect.canInsertBlockType(
				'fundrik/campaign-summary',
			);
			const canInsertDonationFormBlock = blockEditorSelect.canInsertBlockType(
				'fundrik/donation-form',
			);

			if ( canInsertCampaignSummaryBlock ) {
				blockEditorDispatch.insertBlocks( [
					wp.blocks.createBlock( 'fundrik/campaign-summary' ),
				] );
			}

			if ( canInsertDonationFormBlock ) {
				blockEditorDispatch.insertBlocks( [
					wp.blocks.createBlock( 'fundrik/donation-form' ),
				] );
			}

			const campaignSummaryBlocksCountAfterInsert = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/campaign-summary' ).length;
			const donationFormBlocksCountAfterInsert = blockEditorSelect
				.getBlocks()
				.filter( ( block ) => block.name === 'fundrik/donation-form' ).length;

			return {
				campaignSettingsBlocksCount,
				campaignSettingsSupportsInserter: campaignSettingsBlockType?.supports?.inserter,
				campaignSettingsSupportsMultiple: campaignSettingsBlockType?.supports?.multiple,
				campaignSummaryBlocksCount,
				campaignSummarySupportsMultiple: campaignSummaryBlockType?.supports?.multiple,
				campaignSummarySupportsInserter: campaignSummaryBlockType?.supports?.inserter,
				canInsertCampaignSummaryBlock,
				campaignSummaryBlocksCountAfterInsert,
				donationFormSupportsInserter: donationFormBlockType?.supports?.inserter,
				donationFormSupportsMultiple: donationFormBlockType?.supports?.multiple,
				initialDonationFormBlocksCount,
				canInsertDonationFormBlock,
				donationFormBlocksCountAfterInsert,
			};
		} );

		expect( insertionState.campaignSettingsBlocksCount ).toBe( 1 );
		expect( insertionState.campaignSettingsSupportsInserter ).toBe( false );
		expect( insertionState.campaignSettingsSupportsMultiple ).toBe( false );
		expect( insertionState.campaignSummaryBlocksCount ).toBe( 1 );
		expect( insertionState.campaignSummarySupportsMultiple ).toBeUndefined();
		expect( insertionState.campaignSummarySupportsInserter ).toBeUndefined();
		expect( insertionState.canInsertCampaignSummaryBlock ).toBe( true );
		expect( insertionState.campaignSummaryBlocksCountAfterInsert ).toBe( 2 );
		expect( insertionState.donationFormSupportsMultiple ).toBe( false );
		expect( insertionState.initialDonationFormBlocksCount ).toBe( 1 );
		expect( insertionState.canInsertDonationFormBlock ).toBe( false );
		expect( insertionState.donationFormBlocksCountAfterInsert ).toBe( 1 );
	} );

	test( 'keeps campaign blocks unavailable in regular posts', async ( {
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
			const hasCampaignSummaryBlock = blocks.some(
				( block ) => block.name === 'fundrik/campaign-summary',
			);
			const hasDonationFormBlock = blocks.some(
				( block ) => block.name === 'fundrik/donation-form',
			);

			const isAllowedInCurrentEditor = Array.isArray( allowedBlockTypes )
				? allowedBlockTypes.includes( 'fundrik/campaign-settings' )
				: allowedBlockTypes === true;
			const isCampaignSummaryAllowedInCurrentEditor = Array.isArray( allowedBlockTypes )
				? allowedBlockTypes.includes( 'fundrik/campaign-summary' )
				: allowedBlockTypes === true;
			const isDonationFormAllowedInCurrentEditor = Array.isArray( allowedBlockTypes )
				? allowedBlockTypes.includes( 'fundrik/donation-form' )
				: allowedBlockTypes === true;

			return {
				hasCampaignSettingsBlock,
				hasCampaignSummaryBlock,
				hasDonationFormBlock,
				isAllowedInCurrentEditor,
				isCampaignSummaryAllowedInCurrentEditor,
				isDonationFormAllowedInCurrentEditor,
			};
		} );

		expect( blockVisibility.hasCampaignSettingsBlock ).toBe( false );
		expect( blockVisibility.hasCampaignSummaryBlock ).toBe( false );
		expect( blockVisibility.hasDonationFormBlock ).toBe( false );
		expect( blockVisibility.isAllowedInCurrentEditor ).toBe( false );
		expect( blockVisibility.isCampaignSummaryAllowedInCurrentEditor ).toBe( false );
		expect( blockVisibility.isDonationFormAllowedInCurrentEditor ).toBe( false );
	} );
} );
