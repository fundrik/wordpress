<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation as CoreDonation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Integration\ReadModels\PaginatedDonationsAdminList;
use Fundrik\WordPress\Integration\Services\DonationsAdminListService;
use Fundrik\WordPress\Presentation\Formatters\DateTimeFormatter;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( DonationsAdminListService::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( InvalidCampaignIdException::class )]
#[UsesClass( DonationId::class )]
#[UsesClass( InvalidDonationIdException::class )]
#[UsesClass( DonationAdminListItem::class )]
#[UsesClass( PaginatedDonationsAdminList::class )]
#[UsesClass( DateTimeFormatter::class )]
#[UsesClass( MoneyFormatter::class )]
final class DonationsAdminListServiceTest extends WordPressTestCase {

	private const string FIRST_DONATION_ID = '5b023809-1232-407e-a021-44db014c4395';

	private const string SECOND_DONATION_ID = 'c5f71a81-e7ef-49f1-bd41-9e980d6dd9b1';

	private const string THIRD_DONATION_ID = 'ccddd545-df5b-4a4d-9324-2850451a4490';

	private DonationReadPort&MockInterface $donation_read;
	private CampaignReadPort&MockInterface $campaign_read;
	private LoggerInterface&MockInterface $logger;
	private DonationsAdminListService $service;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->stub_admin_url();
		$this->stub_wp_date();
		$this->stub_number_format_i18n();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->service = new DonationsAdminListService(
			new DonationQueryService(
				new ReadDonationByIdHandler( $this->donation_read ),
				new ReadPaginatedDonationsHandler( $this->donation_read ),
			),
			$this->campaign_read,
			$this->logger,
			new DateTimeFormatter(),
			new MoneyFormatter(),
		);
	}

	#[Test]
	public function paginate_batches_campaign_lookup_and_maps_display_rows(): void {

		$this->expect_donation_page(
			[
				$this->make_core_donation_read_model(
					id: self::FIRST_DONATION_ID,
					campaign_id: 7,
					amount: 2_000,
					status: 'succeeded',
					created_at: '2026-06-08 21:08:26',
					updated_at: '2026-06-08 21:27:01',
				),
				$this->make_core_donation_read_model(
					id: self::SECOND_DONATION_ID,
					campaign_id: 7,
					amount: 1_000,
					status: 'pending',
					created_at: '2026-06-08 21:08:14',
				),
				$this->make_core_donation_read_model(
					id: self::THIRD_DONATION_ID,
					campaign_id: 8,
					amount: 4_000,
					status: 'succeeded',
					created_at: '2026-05-07 21:14:57',
				),
			],
			page: 2,
			per_page: 20,
			total: 45,
		);
		$this->expect_campaign_lookup(
			[ 7, 8 ],
			[
				$this->make_core_campaign_read_model( id: 7, title: 'Campaign 7' ),
				$this->make_core_campaign_read_model( id: 8, title: 'Campaign 8' ),
			],
		);

		$result = $this->service->paginate( 2, 20 );

		self::assertSame(
			[
				'page' => 2,
				'per_page' => 20,
				'total' => 45,
			],
			[
				'page' => $result->get_page(),
				'per_page' => $result->get_per_page(),
				'total' => $result->get_total(),
			],
		);

		$items = $result->get_items();

		self::assertCount( 3, $items );
		self::assertSame(
			[ self::FIRST_DONATION_ID, self::SECOND_DONATION_ID, self::THIRD_DONATION_ID ],
			array_map(
				static fn ( DonationAdminListItem $item ): string => $item->get_id(),
				$items,
			),
		);
		self::assertSame(
			[
				'campaign_title' => 'Campaign 7',
				'campaign_edit_url' => 'https://example.test/wp-admin/post.php?post=7&action=edit',
				'amount' => '20.00 RUB',
				'status' => 'succeeded',
				'created_at' => '2026-06-08 21:08:26',
				'updated_at' => '2026-06-08 21:27:01',
			],
			[
				'campaign_title' => $items[0]->get_campaign_title(),
				'campaign_edit_url' => $items[0]->get_campaign_edit_url(),
				'amount' => $items[0]->get_amount(),
				'status' => $items[0]->get_status(),
				'created_at' => $items[0]->get_created_at(),
				'updated_at' => $items[0]->get_updated_at(),
			],
		);
		self::assertNull( $items[1]->get_updated_at() );
		self::assertSame( 'Campaign 8', $items[2]->get_campaign_title() );
	}

	#[Test]
	public function paginate_skips_row_and_logs_error_when_donation_id_is_invalid(): void {

		$this->expect_donation_page( [ $this->make_core_donation_read_model( id: 123, campaign_id: 7 ) ] );
		$this->allow_empty_campaign_lookup();
		$this->expect_error_log(
			message: 'Donation row skipped because donation ID is invalid.',
			operation: 'normalize_donation_id',
			outcome: 'invalid',
			extra_context: [ 'donation_id' => 123 ],
		);

		$result = $this->service->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
	}

	#[Test]
	public function paginate_skips_row_and_logs_error_when_campaign_id_is_invalid(): void {

		$this->expect_donation_page(
			[ $this->make_core_donation_read_model( id: self::FIRST_DONATION_ID, campaign_id: '7' ) ],
		);
		$this->allow_empty_campaign_lookup();
		$this->expect_error_log(
			message: 'Donation row skipped because campaign ID is invalid.',
			operation: 'normalize_campaign_id',
			outcome: 'invalid',
			extra_context: [
				'donation_id' => self::FIRST_DONATION_ID,
				'campaign_id' => '7',
			],
		);

		$result = $this->service->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
	}

	#[Test]
	public function paginate_skips_row_and_logs_error_when_campaign_is_missing(): void {

		$this->expect_donation_page(
			[ $this->make_core_donation_read_model( id: self::FIRST_DONATION_ID, campaign_id: 7 ) ],
		);
		$this->expect_campaign_lookup( [ 7 ], [] );
		$this->expect_missing_campaign_log();

		$result = $this->service->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
	}

	#[Test]
	public function paginate_treats_campaign_with_invalid_id_as_missing(): void {

		$this->expect_donation_page(
			[ $this->make_core_donation_read_model( id: self::FIRST_DONATION_ID, campaign_id: 7 ) ],
		);
		$this->expect_campaign_lookup(
			[ 7 ],
			[ $this->make_core_campaign_read_model( id: '7' ) ],
		);
		$this->expect_missing_campaign_log();

		$result = $this->service->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
	}

	/**
	 * Configures the donation page returned by the read port.
	 *
	 * @param list<CoreDonation> $donations Donation read models.
	 */
	private function expect_donation_page(
		array $donations,
		int $page = 1,
		int $per_page = 20,
		?int $total = null,
	): void {

		$this->donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( $page, $per_page )
			->andReturn( new PaginatedDonations( $donations, $page, $per_page, $total ?? count( $donations ) ) );
	}

	/**
	 * Configures the campaigns returned for a batch of campaign IDs.
	 *
	 * @param list<int> $expected_ids Expected campaign IDs.
	 * @param list<CoreCampaign> $campaigns Campaign read models.
	 */
	private function expect_campaign_lookup( array $expected_ids, array $campaigns ): void {

		$this->campaign_read
			->shouldReceive( 'find_by_ids' )
			->once()
			->with(
				Mockery::on(
					static fn ( array $ids ): bool => array_map(
						static fn ( EntityId $id ): int => $id->get_as_int(),
						$ids,
					) === $expected_ids,
				),
			)
			->andReturn( $campaigns );
	}

	private function allow_empty_campaign_lookup(): void {

		$this->campaign_read
			->shouldReceive( 'find_by_ids' )
			->with( [] )
			->andReturn( [] );
	}

	private function expect_missing_campaign_log(): void {

		$this->expect_error_log(
			message: 'Donation row skipped because campaign could not be loaded.',
			operation: 'resolve_campaign',
			outcome: 'missing',
			extra_context: [
				'donation_id' => self::FIRST_DONATION_ID,
				'campaign_id' => 7,
			],
		);
	}

	/**
	 * Expects an error log with the common service context.
	 *
	 * @param array<string, int|string> $extra_context Additional context entries.
	 */
	private function expect_error_log(
		string $message,
		string $operation,
		string $outcome,
		array $extra_context,
	): void {

		$this->logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$message,
				[
					'service_class' => DonationsAdminListService::class,
					'component' => 'donations_list',
					'layer' => 'integration',
					'system' => 'wordpress',
					'operation' => $operation,
					'outcome' => $outcome,
				] + $extra_context,
			);
	}
}
