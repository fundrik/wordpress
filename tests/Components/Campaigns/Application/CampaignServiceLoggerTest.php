<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application;

use Fundrik\WordPress\Components\Campaigns\Application\CampaignServiceLogger;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( CampaignServiceLogger::class )]
final class CampaignServiceLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private CampaignServiceLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new CampaignServiceLogger( $this->psr_logger );
	}

	#[Test]
	public function log_find_by_id_failed_repository_writes_error_with_exception_and_id(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaign by ID failed (repository error).',
				$this->log_context(
					[
						'operation' => 'find_campaign_by_id',
						'id' => 7,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_find_by_id_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_find_by_id_failed_assembler_writes_error_with_exception_and_id(): void {

		$e = new CampaignAssemblerException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaign by ID failed (assembler error).',
				$this->log_context(
					[
						'operation' => 'find_campaign_by_id',
						'id' => 7,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_find_by_id_failed_assembler( id: 7, e: $e );
	}

	#[Test]
	public function log_find_all_failed_repository_writes_error_with_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaigns failed (repository error).',
				$this->log_context(
					[
						'operation' => 'find_all_campaigns',
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_find_all_failed_repository( $e );
	}

	#[Test]
	public function log_find_all_failed_assembler_writes_error_with_exception(): void {

		$e = new CampaignAssemblerException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaigns failed (assembler error).',
				$this->log_context(
					[
						'operation' => 'find_all_campaigns',
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_find_all_failed_assembler( $e );
	}

	#[Test]
	public function log_save_failed_repository_writes_error_with_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Saving campaign failed (repository error).',
				$this->log_context(
					[
						'operation' => 'save_campaign',
						'id' => 7,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_save_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_publish_saved_event_failed_writes_warning_with_exception_and_id(): void {

		$e = new RuntimeException();

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignSavedEvent failed (event bus error).',
				$this->log_context(
					[
						'operation' => 'save_campaign',
						'id' => 123,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_publish_saved_event_failed( id: 123, e: $e );
	}

	#[Test]
	public function log_save_succeeded_writes_info_with_required_context(): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->log_context(
					[
						'operation' => 'save_campaign',
						'id' => 123,
						'action' => 'update',
					],
				),
			);

		$this->logger->log_save_succeeded( id: 123, action: 'update' );
	}

	#[Test]
	public function log_delete_failed_repository_writes_error_with_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Deleting campaign failed (repository error).',
				$this->log_context(
					[
						'operation' => 'delete_campaign',
						'id' => 7,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_delete_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_publish_deleted_event_failed_writes_warning_with_exception_and_id(): void {

		$e = new RuntimeException();

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignDeletedEvent failed (event bus error).',
				$this->log_context(
					[
						'operation' => 'delete_campaign',
						'id' => 42,
						'exception' => static fn ( $ex ) => $ex === $e,
					],
				),
			);

		$this->logger->log_publish_deleted_event_failed( id: 42, e: $e );
	}

	#[Test]
	public function log_delete_succeeded_writes_info_with_required_context(): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Deleting campaign succeeded.',
				$this->log_context(
					[
						'operation' => 'delete_campaign',
						'id' => 42,
					],
				),
			);

		$this->logger->log_delete_succeeded( id: 42 );
	}

	private function log_context( array $expected ): \Mockery\Matcher\Closure {

		return $this->array_has(
			$expected + [
				'class_name' => CampaignServiceLogger::class,
				'component' => 'campaigns',
				'layer' => 'application',
	            // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText
				'system' => 'wordpress',
			],
		);
	}
}
