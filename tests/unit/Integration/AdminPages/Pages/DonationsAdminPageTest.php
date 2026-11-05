<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\DonationsAdminPage;
use Fundrik\WordPress\Integration\AdminPages\Tables\DonationsListTable;
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

#[CoversClass( DonationsAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( DonationsListTable::class )]
#[UsesClass( PaginatedDonationsAdminList::class )]
#[UsesClass( DonationsAdminListService::class )]
final class DonationsAdminPageTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;
	private DonationReadPort&MockInterface $donation_read;
	private DonationsAdminPage $admin_page;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->donation_read = Mockery::mock( DonationReadPort::class );

		$donation_query = new DonationQueryService(
			new ReadDonationByIdHandler( $this->donation_read ),
			new ReadPaginatedDonationsHandler( $this->donation_read ),
		);

		$this->admin_page = new DonationsAdminPage(
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
	public function register_registers_donations_submenu_with_render_callback(): void {

		$render = null;

		Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				AdminPageDefinitions::ROOT_MENU_SLUG,
				__( 'Fundrik Donations', 'fundrik' ),
				__( 'Donations', 'fundrik' ),
				AdminPageDefinitions::CONTENT_CAPABILITY,
				AdminPageDefinitions::DONATIONS_PAGE_ID,
				Mockery::capture( $render ),
			);

		$this->donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( 1, 20 )
			->andReturn( new PaginatedDonations( [], 1, 20, 0 ) );

		$this->campaign_read
			->shouldReceive( 'find_by_ids' )
			->once()
			->with( [] )
			->andReturn( [] );

		$this->admin_page->register();

		self::assertIsCallable( $render );

		ob_start();
		$render();
		$output = preg_replace( '/>\s+</', '><', trim( (string) ob_get_clean() ) );

		self::assertSame(
			'<div class="wrap"><h1>Fundrik Donations</h1><form method="get"><table></table></form></div>',
			$output,
		);
	}
}
