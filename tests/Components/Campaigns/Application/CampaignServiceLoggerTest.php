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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'find_campaign_by_id'
						&& $context['id'] === 7
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'find_campaign_by_id'
						&& $context['id'] === 7
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'find_all_campaigns'
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'find_all_campaigns'
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'save_campaign'
						&& $context['id'] === 7
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
				),
			);

		$this->logger->log_save_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_save_succeeded_writes_info_with_required_context(): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'save_campaign'
						&& $context['id'] === 123
						&& $context['action'] === 'update'
						&& $this->check_logger_context( $context ),
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
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'delete_campaign'
						&& $context['id'] === 7
						&& $context['exception'] === $e
						&& $this->check_logger_context( $context ),
				),
			);

		$this->logger->log_delete_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_delete_succeeded_writes_info_with_required_context(): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Deleting campaign succeeded.',
				Mockery::on(
					fn ( array $context ): bool => $context['operation'] === 'delete_campaign'
						&& $context['id'] === 42
						&& $this->check_logger_context( $context ),
				),
			);

		$this->logger->log_delete_succeeded( id: 42 );
	}

	private function check_logger_context( array $context ): bool {

		return $context['class_name'] === CampaignServiceLogger::class
			&& $context['component'] === 'campaigns'
			&& $context['layer'] === 'application'
			// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText
			&& $context['system'] === 'wordpress';
	}
}
