const { request } = require( '@playwright/test' );
const { RequestUtils } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Prepares authenticated storage state for admin user.
 *
 * @param {import('@playwright/test').FullConfig} config
 */
module.exports = async function globalSetup( config ) {
	const { storageState, baseURL } = config.projects[ 0 ].use;
	const storageStatePath =
		typeof storageState === 'string' ? storageState : undefined;

	const requestContext = await request.newContext( { baseURL } );
	const requestUtils = new RequestUtils( requestContext, { storageStatePath } );

	await requestUtils.setupRest();
	await requestContext.dispose();
};
