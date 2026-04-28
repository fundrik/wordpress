<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Infrastructure\Helpers\PluginUrl;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestAfterInsertCampaignSynchronizer;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataExtractor;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataValidator;
use InvalidArgumentException;
use Override;
use stdClass;
use UnexpectedValueException;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Synchronizes the campaign post state between WordPress and the Campaign domain model.
 *
 * - Rejects REST writes when the payload cannot be synchronized safely.
 * - Adds the current campaign version to REST responses for optimistic locking.
 * - Persists the saved campaign snapshot into Fundrik storage after REST saves.
 * - Deletes the synchronized campaign when the campaign post is deleted.
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
	 * @param DeletePostActionHookDispatcher $delete_post_hook The post deletion action hook.
	 * @param EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook The block editor assets action hook.
	 * @param CampaignRepositoryPort $campaign_repository The campaign repository for reading the persisted version.
	 * @param CampaignCommandService $campaign_command Provides campaign write operations for synchronization callbacks.
	 * @param RestPreInsertCampaignSyncDataExtractor $pre_insert_extractor The extractor for pre-insert synchronization data.
	 * @param RestPreInsertCampaignSyncDataValidator $pre_insert_validator The validator for pre-insert synchronization data.
	 * @param RestAfterInsertCampaignSyncDataExtractor $after_insert_extractor The extractor for after-insert synchronization data.
	 * @param RestAfterInsertCampaignSynchronizer $after_insert_synchronizer The synchronizer for persisting the saved snapshot.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 */
	public function __construct(
		private RestPreInsertCampaignFilterHookDispatcher $rest_pre_insert_hook,
		private RestPrepareCampaignFilterHookDispatcher $rest_prepare_hook,
		private RestAfterInsertCampaignActionHookDispatcher $rest_after_insert_hook,
		private DeletePostActionHookDispatcher $delete_post_hook,
		private EnqueueBlockEditorAssetsActionHookDispatcher $enqueue_block_editor_assets_hook,
		private CampaignRepositoryPort $campaign_repository,
		private CampaignCommandService $campaign_command,
		private RestPreInsertCampaignSyncDataExtractor $pre_insert_extractor,
		private RestPreInsertCampaignSyncDataValidator $pre_insert_validator,
		private RestAfterInsertCampaignSyncDataExtractor $after_insert_extractor,
		private RestAfterInsertCampaignSynchronizer $after_insert_synchronizer,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}
	// phpcs:enable

	/**
	 * Attaches the synchronization callbacks to campaign REST hooks.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->rest_pre_insert_hook->attach( $this->reject_when_campaign_cannot_be_synced( ... ) );
		$this->rest_prepare_hook->attach( $this->attach_campaign_version_for_sync( ... ) );
		$this->rest_after_insert_hook->attach( $this->sync_campaign_after_rest_save( ... ) );
		$this->delete_post_hook->attach( $this->delete_campaign_after_post_delete( ... ) );
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

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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
	 *
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	private function attach_campaign_version_for_sync(
		WP_REST_Response $response,
		WP_Post $post,
		WP_REST_Request $request,
	): WP_REST_Response {

		// REST hooks pass a persisted WP_Post, so its ID is expected to be a positive integer.
		$post_id = TypeCaster::to_int( $post->ID );
		$campaign_id = CampaignId::from_value( $post_id );

		try {
			$campaign = $this->campaign_repository->find_by_id( $campaign_id->to_entity_id() );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_error(
				'Failed to resolve campaign version for REST response.',
				[
					'post_id' => $post_id,
					'campaign_id' => $campaign_id->get_value(),
					'exception' => $e,
				],
			);

			// Keep the REST response usable when version lookup fails; the failure is logged above.
			return $response;
		}

		// Treat a missing synchronized campaign as an unsaved snapshot and expose the initial version.
		$version = $campaign === null
			? EntityVersion::initial()
			: $campaign->get_version();

		$data = $response->get_data();
		$data = is_array( $data ) ? $data : [];

		$meta = $data['meta'] ?? [];
		$meta = is_array( $meta ) ? $meta : [];

		$meta[ CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME ] = $version->get_value();
		$data['meta'] = $meta;

		$response->set_data( $data );

		return $response;
	}
	// phpcs:enable

	/**
	 * Enqueues the block editor scripts required by the plugin.
	 *
	 * @since 1.0.0
	 */
	private function enqueue_block_editor_script(): void {

		if ( ! CurrentAdminScreen::is_post_type( CampaignPostTypeConfig::ID ) ) {
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

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Persists the saved campaign post snapshot into Fundrik storage after REST saves.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The inserted or updated post object.
	 * @param WP_REST_Request $request The REST request object.
	 * @param bool $creating Whether the post is being created or updated.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	private function sync_campaign_after_rest_save( WP_Post $post, WP_REST_Request $request, bool $creating ): void {

		// REST hooks pass a persisted WP_Post, so its ID is expected to be a positive integer.
		$post_id = TypeCaster::to_int( $post->ID );

		try {

			$data = $this->after_insert_extractor->extract( $post, $request );

		} catch ( FundrikDomainException | InvalidArgumentException | UnexpectedValueException $e ) {

			$this->logger->log_error(
				'Campaign synchronization after REST save failed: payload is not usable.',
				[
					'post_id' => $post_id,
				],
			);

			throw $e;
		}

		try {

			$this->after_insert_synchronizer->sync( $data );

		} catch (
			CampaignRepositoryExceptionInterface |
			CreateCampaignException |
			SyncCampaignFromSnapshotException $e
		) {

			$this->logger->log_error(
				'Campaign synchronization after REST save failed.',
				[
					'post_id' => $post_id,
					'campaign_id' => $data->id->get_value(),
					'version' => $data->version->get_value(),
					'exception' => $e,
				],
			);

			throw $e;
		}
	}
	// phpcs:enable

	/**
	 * Removes the synchronized campaign when the source campaign post is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The deleted post ID.
	 * @param WP_Post $post The deleted post snapshot.
	 */
	private function delete_campaign_after_post_delete( int $post_id, WP_Post $post ): void {

		if ( $post->post_type !== CampaignPostTypeConfig::ID ) {
			return;
		}

		// The validated delete_post hook input is expected to carry a positive campaign post ID.
		$campaign_id = CampaignId::from_value( $post_id );

		try {
			$this->campaign_command->delete( $campaign_id->to_entity_id() );
		} catch ( DeleteCampaignException $e ) {

			$this->logger->log_error(
				'Campaign synchronization after post delete failed.',
				[
					'post_id' => $post_id,
					'campaign_id' => $campaign_id->get_value(),
					'post_type' => $post->post_type,
					'exception' => $e,
				],
			);

			throw $e;
		}
	}
}
