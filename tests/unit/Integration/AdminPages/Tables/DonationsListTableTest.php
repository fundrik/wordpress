<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Tables;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\WordPress\Integration\AdminPages\Tables\DonationsListTable;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( DonationsListTable::class )]
#[UsesClass( DonationAdminListItem::class )]
#[UsesClass( PaginatedDonationsAdminList::class )]
#[UsesClass( DonationsAdminListService::class )]
final class DonationsListTableTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;
	private DonationReadPort&MockInterface $donation_read;
	private DonationsListTable $table;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->donation_read = Mockery::mock( DonationReadPort::class );

		$donation_query = new DonationQueryService(
			new ReadDonationByIdHandler( $this->donation_read ),
			new ReadPaginatedDonationsHandler( $this->donation_read ),
		);

		$this->table = new DonationsListTable(
			new DonationsAdminListService(
				$donation_query,
				$this->campaign_read,
				Mockery::mock( LoggerInterface::class ),
				new DateTimeFormatter(),
				new MoneyFormatter(),
			),
		);
	}

	#[Test]
	public function constructor_configures_donation_labels(): void {

		self::assertSame(
			[
				'singular' => 'donation',
				'plural' => 'donations',
			],
			$this->table->get_test_args(),
		);
	}

	#[Test]
	public function prepare_items_loads_current_page_and_configures_table(): void {

		$this->table->items = [ $this->make_item() ];
		$this->table->set_test_page_number( 3 );

		$this->donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( 3, 20 )
			->andReturn( new PaginatedDonations( [], 3, 20, 41 ) );

		$this->campaign_read
			->shouldReceive( 'find_by_ids' )
			->once()
			->with( [] )
			->andReturn( [] );

		$this->table->prepare_items();

		self::assertSame( [], $this->table->items );
		self::assertSame(
			[
				$this->expected_columns(),
				[],
				[],
			],
			$this->table->get_test_column_headers(),
		);
		self::assertSame(
			[
				'total_items' => 41,
				'per_page' => 20,
			],
			$this->table->get_test_pagination_args(),
		);
	}

	#[Test]
	public function get_columns_returns_donation_columns(): void {

		self::assertSame( $this->expected_columns(), $this->table->get_columns() );
	}

	#[Test]
	#[DataProvider( 'simple_column_cases' )]
	public function column_default_returns_simple_column_value( string $column, string $value ): void {

		self::assertSame( $value, $this->table->column_default( $this->make_item(), $column ) );
	}

	#[Test]
	public function column_default_returns_campaign_link(): void {

		self::assertSame(
			'<a href="https://example.test/wp-admin/post.php?post=901&action=edit">Campaign title</a>',
			$this->table->column_default( $this->make_item(), 'campaign' ),
		);
	}

	#[Test]
	public function column_default_returns_fallback_when_updated_at_is_missing(): void {

		self::assertSame(
			'-',
			$this->table->column_default( $this->make_item( updated_at: null ), 'updated_at' ),
		);
	}

	#[Test]
	public function column_default_returns_empty_string_for_unsupported_item(): void {

		self::assertSame( '', $this->table->column_default( [], 'id' ) );
	}

	#[Test]
	public function column_default_returns_empty_string_for_unknown_column(): void {

		self::assertSame( '', $this->table->column_default( $this->make_item(), 'unknown' ) );
	}

	#[Test]
	public function no_items_outputs_empty_list_message(): void {

		ob_start();
		$this->table->no_items();
		$output = ob_get_clean();

		self::assertSame( 'No donations found.', $output );
	}

	/**
	 * Returns simple column cases.
	 *
	 * @return iterable<string, array{string, string}>
	 */
	public static function simple_column_cases(): iterable {

		yield 'donation ID' => [ 'id', 'donation-id' ];
		yield 'amount' => [ 'amount', '25.00 RUB' ];
		yield 'status' => [ 'status', 'succeeded' ];
		yield 'creation timestamp' => [ 'created_at', '2026-11-05 12:30:00' ];
		yield 'update timestamp' => [ 'updated_at', '2026-11-05 12:45:00' ];
	}

	/**
	 * Returns expected table columns.
	 *
	 * @return array<string, string>
	 */
	private function expected_columns(): array {

		return [
			'id' => 'ID',
			'campaign' => 'Campaign',
			'amount' => 'Amount',
			'status' => 'Status',
			'created_at' => 'Created at',
			'updated_at' => 'Updated at',
		];
	}

	private function make_item( ?string $updated_at = '2026-11-05 12:45:00' ): DonationAdminListItem {

		return new DonationAdminListItem(
			id: 'donation-id',
			campaign_title: 'Campaign title',
			campaign_edit_url: 'https://example.test/wp-admin/post.php?post=901&action=edit',
			amount: '25.00 RUB',
			status: 'succeeded',
			created_at: '2026-11-05 12:30:00',
			updated_at: $updated_at,
		);
	}
}
