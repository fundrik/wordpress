<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs application-level operations of the CampaignService.
 *
 * @since 1.0.0
 */
final readonly class CampaignServiceLogger {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Delegates logging to the underlying PSR-3 logger.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Logs the start of a find-by-ID operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being searched.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_started( int $id ): void {

		$this->logger->debug(
			'Finding campaign by ID started.',
			$this->logger_context(
				[
					'operation' => 'find_campaign_by_id',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a find-by-ID operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being searched.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_find_by_id_failed_repository( int $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Finding campaign by ID failed (repository error).',
			$this->logger_context(
				[
					'operation' => 'find_campaign_by_id',
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs that the requested campaign was not found (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID that was not found.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_not_found( int $id ): void {

		$this->logger->debug(
			'Finding campaign by ID not found.',
			$this->logger_context(
				[
					'operation' => 'find_campaign_by_id',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs assembler failure during a find-by-ID operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being processed.
	 * @param CampaignAssemblerException $e The assembler exception that occurred.
	 */
	public function log_find_by_id_failed_assembler( int $id, CampaignAssemblerException $e ): void {

		$this->logger->error(
			'Finding campaign by ID failed (assembler error).',
			$this->logger_context(
				[
					'operation' => 'find_campaign_by_id',
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a find-by-ID operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID found.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_succeeded( int $id ): void {

		$this->logger->debug(
			'Finding campaign by ID succeeded.',
			$this->logger_context(
				[
					'operation' => 'find_campaign_by_id',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs the start of a find-all operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_all_started(): void {

		$this->logger->debug(
			'Finding campaigns started.',
			$this->logger_context(
				[
					'operation' => 'find_all_campaigns',
				],
			),
		);
	}

	/**
	 * Logs repository failure during a find-all operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_find_all_failed_repository( CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Finding campaigns failed (repository error).',
			$this->logger_context(
				[
					'operation' => 'find_all_campaigns',
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs assembler failure during a find-all operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignAssemblerException $e The assembler exception that occurred.
	 */
	public function log_find_all_failed_assembler( CampaignAssemblerException $e ): void {

		$this->logger->error(
			'Finding campaigns failed (assembler error).',
			$this->logger_context(
				[
					'operation' => 'find_all_campaigns',
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a find-all operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $count The number of campaigns retrieved.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_all_succeeded( int $count ): void {

		$this->logger->debug(
			'Finding campaigns succeeded.',
			$this->logger_context(
				[
					'operation' => 'find_all_campaigns',
					'count' => $count,
				],
			),
		);
	}

	/**
	 * Logs the start of a save operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being saved.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_save_started( int $id ): void {

		$this->logger->debug(
			'Saving campaign started.',
			$this->logger_context(
				[
					'operation' => 'save_campaign',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a save operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being saved.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_save_failed_repository( int $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Saving campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => 'save_campaign',
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a save operation (info).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID saved.
	 * @param string $action The type of save performed ("create" or "update").
	 */
	public function log_save_succeeded( int $id, string $action ): void {

		$this->logger->info(
			'Saving campaign succeeded.',
			$this->logger_context(
				[
					'operation' => 'save_campaign',
					'id' => $id,
					'action' => $action,
				],
			),
		);
	}

	/**
	 * Logs the start of a delete operation (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being deleted.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_delete_started( int $id ): void {

		$this->logger->debug(
			'Deleting campaign started.',
			$this->logger_context(
				[
					'operation' => 'delete_campaign',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a delete operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID being deleted.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_delete_failed_repository( int $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Deleting campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => 'delete_campaign',
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a delete operation (info).
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The campaign ID deleted.
	 */
	public function log_delete_succeeded( int $id ): void {

		$this->logger->info(
			'Deleting campaign succeeded.',
			$this->logger_context(
				[
					'operation' => 'delete_campaign',
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Builds the structured logger context for this service.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @return array<string, mixed> The structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'class_name' => self::class,
			'component' => 'campaigns',
			'layer' => 'application',
			'system' => 'wordpress',
		] + $extra;
	}
}
