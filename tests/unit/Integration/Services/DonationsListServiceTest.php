<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadRepository;
use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Integration\ReadModels\PaginatedDonationsAdminList;
use Fundrik\WordPress\Integration\Services\DonationsListService;
use Fundrik\WordPress\Presentation\Formatters\DateTimeFormatter;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( DonationsListService::class )]
final class DonationsListServiceTest extends WordPressTestCase {

	protected function setUp(): void {

		parent::setUp();

		Functions\when( 'admin_url' )->alias(
			static fn ( string $path = '' ): string => 'https://example.test/wp-admin/' . ltrim( $path, '/' ),
		);
	}

	#[Test]
	public function paginate_batches_campaign_lookup_and_maps_display_rows(): void {

		$donation_read = Mockery::mock( DonationReadPort::class );
		$donation_query_handler = new ReadPaginatedDonationsHandler( $donation_read );
		$donation_query = new DonationQueryService(
			new ReadDonationByIdHandler( $donation_read ),
			$donation_query_handler,
		);

		$database = Mockery::mock( DatabasePort::class );
		$campaign_read = new CampaignReadRepository( $database );
		$logger = Mockery::mock( LoggerInterface::class );

		$service = new DonationsListService(
			$donation_query,
			$campaign_read,
			$logger,
			new DateTimeFormatter(),
			new MoneyFormatter(),
		);

		$donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( 2, 20 )
			->andReturn(
				new PaginatedDonations(
					[
						$this->make_donation( '5b023809-1232-407e-a021-44db014c4395', 7, 2000, 'RUB', 'succeeded', '2026-06-08 21:08:26', '2026-06-08 21:27:01' ),
						$this->make_donation( 'c5f71a81-e7ef-49f1-bd41-9e980d6dd9b1', 7, 1000, 'RUB', 'pending', '2026-06-08 21:08:14', null ),
						$this->make_donation( 'ccddd545-df5b-4a4d-9324-2850451a4490', 8, 4000, 'RUB', 'succeeded', '2026-05-07 21:14:57', '2026-05-07 21:15:30' ),
					],
					2,
					20,
					3,
				),
			);

		$database
			->shouldReceive( 'get_all_by_ids' )
			->once()
			->with(
				'fundrik_campaigns',
				7,
				8,
			)
			->andReturn(
				[
					$this->campaign_row( 7, 'Campaign 7' ),
				],
			);

		$logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Donation row skipped because campaign could not be loaded.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === DonationsListService::class
						&& $context['component'] === 'donations_list'
						&& $context['layer'] === 'integration'
						&& $context['system'] === 'wordpress'
						&& $context['operation'] === 'resolve_campaign'
						&& $context['outcome'] === 'missing'
						&& $context['donation_id'] === 'ccddd545-df5b-4a4d-9324-2850451a4490'
						&& $context['campaign_id'] === 8,
				),
			);

		$result = $service->paginate( 2, 20 );

		self::assertInstanceOf( PaginatedDonationsAdminList::class, $result );
		self::assertSame( 2, $result->get_page() );
		self::assertSame( 20, $result->get_per_page() );
		self::assertSame( 3, $result->get_total() );
		self::assertSame( 1, $result->get_total_pages() );

		$items = $result->get_items();
		self::assertCount( 2, $items );
		self::assertInstanceOf( DonationAdminListItem::class, $items[0] );
		self::assertSame( '5b023809-1232-407e-a021-44db014c4395', $items[0]->get_id() );
		self::assertSame( 'Campaign 7', $items[0]->get_campaign_title() );
		self::assertSame( 'https://example.test/wp-admin/post.php?post=7&action=edit', $items[0]->get_campaign_edit_url() );
		self::assertSame( '20.00 RUB', $items[0]->get_amount() );
		self::assertSame( 'succeeded', $items[0]->get_status() );
		self::assertSame( '2026-06-08 21:08:26', $items[0]->get_created_at() );
		self::assertSame( '2026-06-08 21:27:01', $items[0]->get_updated_at() );
		self::assertSame( 'pending', $items[1]->get_status() );
		self::assertNull( $items[1]->get_updated_at() );
	}

	/**
	 * Returns a donation read model.
	 *
	 * @return Donation Donation read model.
	 */
	private function make_donation(
		int|string $id,
		int|string $campaign_id,
		int $amount,
		string $currency_code,
		string $status,
		string $created_at,
		?string $updated_at,
	): Donation {

		return new Donation(
			id: $id,
			campaign_id: $campaign_id,
			amount: $amount,
			currency_code: $currency_code,
			status: $status,
			created_at: UtcDateTime::create(
				new DateTimeImmutable( $created_at, new DateTimeZone( 'UTC' ) ),
			),
			updated_at: $updated_at === null
				? null
				: UtcDateTime::create(
					new DateTimeImmutable( $updated_at, new DateTimeZone( 'UTC' ) ),
				),
		);
	}

	/**
	 * Returns a campaign persistence row.
	 *
	 * @return array<string, string|null> Campaign persistence row.
	 */
	private function campaign_row( int $id, string $title ): array {

		return [
			'id' => (string) $id,
			'title' => $title,
			'accepts_donations' => '1',
			'currency_code' => 'RUB',
			'target_amount' => null,
			'collected_amount' => '0',
			'donations_count' => '0',
			'created_at' => '2026-03-21 10:00:00',
			'updated_at' => null,
		];
	}
}
