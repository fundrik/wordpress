<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Services;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides campaign lookup for WordPress-facing integration entry points.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignLookupService {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignQueryService $campaign_query Provides campaign read operations by ID.
	 * @param WordPressRuntimeInterface $wp_runtime Provides current WordPress post context.
	 * @param LoggerInterface $logger Writes structured log entries for campaign lookup operations.
	 */
	public function __construct(
		private CampaignQueryService $campaign_query,
		private WordPressRuntimeInterface $wp_runtime,
		private LoggerInterface $logger,
	) {}

	/**
	 * Returns a campaign by ID or current campaign post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
	 *
	 * @return Campaign|null Campaign if found, null otherwise.
	 */
	public function get( ?int $campaign_id = null ): ?Campaign {

		$campaign_id = $this->resolve_campaign_id( $campaign_id );

		if ( $campaign_id === null ) {
			return null;
		}

		try {
			$campaign = $this->campaign_query->find_by_id( $campaign_id );
		} catch ( ReadCampaignByIdException $e ) {
			$this->log_campaign_lookup_failed( $campaign_id, $e );
			return null;
		}

		if ( $campaign === null ) {
			return null;
		}

		/**
		 * Filters the campaign.
		 *
		 * @since 1.0.0
		 *
		 * @param Campaign $campaign Campaign object.
		 * @param int $campaign_id Campaign ID.
		 */
		return apply_filters( 'fundrik_get_campaign', $campaign, $campaign_id );
	}

	/**
	 * Resolves a campaign ID from the given input or current WordPress post context.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
	 *
	 * @return int|null Campaign ID if available and valid, null otherwise.
	 */
	private function resolve_campaign_id( ?int $campaign_id = null ): ?int {

		$source = $campaign_id === null ? 'current_post' : 'argument';
		$campaign_id ??= $this->resolve_current_campaign_post_id();

		if ( $campaign_id === null ) {
			return null;
		}

		try {
			return CampaignId::from_value( $campaign_id )->get_value();
		} catch ( InvalidCampaignIdException $e ) {
			$this->log_invalid_campaign_id( $campaign_id, $source, $e );
			return null;
		}
	}

	/**
	 * Returns current campaign ID from the WordPress post context.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Current campaign ID if available, null otherwise.
	 */
	private function resolve_current_campaign_post_id(): ?int {

		$post = $this->wp_runtime->get_current_post();

		if ( $post?->post_type !== CampaignPostTypeConfig::ID ) {
			return null;
		}

		return $post->ID;
	}

	/**
	 * Logs an invalid campaign ID for campaign lookup.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Invalid campaign ID value.
	 * @param string $source Campaign ID source.
	 * @param InvalidCampaignIdException $exception Original validation exception.
	 */
	private function log_invalid_campaign_id(
		int $campaign_id,
		string $source,
		InvalidCampaignIdException $exception,
	): void {

		$this->logger->warning(
			'Campaign lookup skipped due to invalid campaign ID.',
			$this->logger_context(
				[
					'operation' => 'validate_campaign_id',
					'outcome' => 'invalid',
					'campaign_id' => $campaign_id,
					'source' => $source,
					'exception' => $exception,
				],
			),
		);
	}

	/**
	 * Logs a campaign lookup failure.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param ReadCampaignByIdException $exception Original lookup exception.
	 */
	private function log_campaign_lookup_failed( int $campaign_id, ReadCampaignByIdException $exception ): void {

		$this->logger->error(
			'Campaign lookup failed.',
			$this->logger_context(
				[
					'operation' => 'read_campaign',
					'outcome' => 'failed',
					'campaign_id' => $campaign_id,
					'exception' => $exception,
				],
			),
		);
	}

	/**
	 * Builds structured logger context for campaign lookup operations.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @return array<string, mixed> Structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'service_class' => self::class,
			'logger_class' => self::class,
			'component' => 'campaign_lookup',
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;
	}
}
