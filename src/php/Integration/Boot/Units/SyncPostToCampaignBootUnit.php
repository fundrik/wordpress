<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\Helpers\PluginUrl;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataValidator;
use stdClass;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_Screen;

/**
 * Synchronizes the campaign post state between WordPress and the Campaign domain model.
 *
 * - Rejects REST writes when the payload cannot be synchronized safely.
 * - Adds the current campaign version to REST responses for optimistic locking.
 * - Persists the saved campaign snapshot into Fundrik storage after REST saves.
 *
 * Also enqueues the block editor script required by the synchronization workflow.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SyncPostToCampaignBootUnit implements BootUnitInterface {

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param RestPreInsertCampaignFilterHookDispatcher $rest_pre_insert_hook The REST pre-insert filter hook for campaigns.
	 * @param RestPrepareCampaignFilterHookDispatcher $rest_prepare_hook The REST prepare filter hook for campaigns.
	 * @param RestAfterInsertCampaignActionHookDispatcher $rest_after_insert_hook The REST after-insert action hook for campaigns.
	 * @param EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook The block editor assets action hook.
	 * @param CampaignRepositoryPort $campaign_repository The campaign repository for reading the persisted version.
	 * @param RestPreInsertCampaignSyncDataExtractor $pre_insert_extractor The extractor for pre-insert synchronization data.
	 * @param RestPreInsertCampaignSyncDataValidator $pre_insert_validator The validator for pre-insert synchronization data.
	 * @param RestAfterInsertCampaignSyncDataExtractor $after_insert_extractor The extractor for after-insert synchronization data.
	 * @param RestAfterInsertCampaignSynchronizer $after_insert_synchronizer The synchronizer for persisting the saved snapshot.
	 */
	public function __construct(
		private RestPreInsertCampaignFilterHookDispatcher $rest_pre_insert_hook,
		private RestPrepareCampaignFilterHookDispatcher $rest_prepare_hook,
		private RestAfterInsertCampaignActionHookDispatcher $rest_after_insert_hook,
		private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook,
		private CampaignRepositoryPort $campaign_repository,
		private RestPreInsertCampaignSyncDataExtractor $pre_insert_extractor,
		private RestPreInsertCampaignSyncDataValidator $pre_insert_validator,
		private RestAfterInsertCampaignSyncDataExtractor $after_insert_extractor,
		private RestAfterInsertCampaignSynchronizer $after_insert_synchronizer,
	) {}
	// phpcs:enable

	/**
	 * Attaches the synchronization callbacks to campaign REST hooks.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {

		$this->rest_pre_insert_hook->attach( $this->reject_when_campaign_cannot_be_synced( ... ) );
		$this->rest_prepare_hook->attach( $this->attach_campaign_version_for_sync( ... ) );
		$this->rest_after_insert_hook->attach( $this->sync_campaign_after_rest_save( ... ) );
		$this->enqueue_block_editor_assets_hook->attach( $this->enqueue_block_editor_script( ... ) );
	}

	/**
	 * Rejects the REST insert/update when the post cannot be safely synchronized.
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $prepared_post The prepared post object before insert/update.
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return stdClass|WP_Error The unchanged prepared post, or a WP_Error when rejected.
	 */
	private function reject_when_campaign_cannot_be_synced(
		stdClass $prepared_post,
		WP_REST_Request $request,
	): stdClass|WP_Error {

		$data = $this->pre_insert_extractor->extract_or_error( $prepared_post, $request );

		if ( $data instanceof WP_Error ) {
			return $data;
		}

		$error = $this->pre_insert_validator->validate_or_error( $data );

		if ( $error instanceof WP_Error ) {
			return $error;
		}

		return $prepared_post;
	}

	/**
	 * Attaches the current campaign version to the REST response meta.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response The REST response object.
	 * @param WP_Post $post The campaign post.
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The modified response.
	 */
	private function attach_campaign_version_for_sync(
		WP_REST_Response $response,
		WP_Post $post,
		// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
		WP_REST_Request $request,
	): WP_REST_Response {

		$entity_id = EntityId::create( TypeCaster::to_int( $post->ID ) );

		try {
			$campaign = $this->campaign_repository->find_by_id( $entity_id );
		} catch ( CampaignRepositoryExceptionInterface ) {
			return $response;
		}

		$version = $campaign === null ? EntityVersion::initial() : $campaign->get_version();

		$meta = $response->data['meta'] ?? [];

		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$meta[ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME ] = $version->get_value();

		$response->data['meta'] = $meta;

		return $response;
	}

	/**
	 * Enqueues the block editor scripts required by the plugin.
	 *
	 * @since 1.0.0
	 */
	private function enqueue_block_editor_script(): void {

		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen ) {
			return;
		}

		if ( $screen->post_type !== CampaignPostTypeConfig::ID ) {
			return;
		}

		wp_enqueue_script(
			'fundrik-editor-save-sync',
			PluginUrl::JavaScripts->file( 'fundrik-editor-save-sync.js' ),
			[
				'wp-data',
				'wp-core-data',
				'wp-editor',
				'wp-api-fetch',
			],
			FUNDRIK_VERSION,
			[ 'in_footer' => true ],
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	/**
	 * Persists the saved campaign post snapshot into Fundrik storage after REST saves.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The inserted or updated post object.
	 * @param WP_REST_Request $request The REST request object.
	 * @param bool $creating Whether the post is being created (true) or updated (false).
	 */
	private function sync_campaign_after_rest_save( WP_Post $post, WP_REST_Request $request, bool $creating ): void {

		$data = $this->after_insert_extractor->extract_or_null( $post, $request );

		if ( $data === null ) {
			return;
		}

		$this->after_insert_synchronizer->sync( $data );
	}
	// phpcs:enable
}
