<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use ArrayObject;
use Brain\Monkey\Functions;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\DonationsAdminPage;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadRepository;
use Fundrik\WordPress\Integration\Services\DonationsListService;
use Fundrik\WordPress\Presentation\Formatters\DateTimeFormatter;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( DonationsAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
final class DonationsAdminPageTest extends WordPressTestCase {

	private DonationsAdminPage $admin_page;

	protected function setUp(): void {

		parent::setUp();

		$this->admin_page = new DonationsAdminPage( $this->create_donations_list_service() );
	}

	protected function tearDown(): void {

		unset( $GLOBALS['fundrik_test_donations_callback'] );

		parent::tearDown();
	}

	#[Test]
	public function register_registers_donations_submenu_and_render_callback(): void {

		$page_state = new ArrayObject(
			[
				'callback' => null,
			],
		);

		Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				AdminPageDefinitions::ROOT_MENU_SLUG,
				__( 'Fundrik Donations', 'fundrik' ),
				__( 'Donations', 'fundrik' ),
				AdminPageDefinitions::CONTENT_CAPABILITY,
				AdminPageDefinitions::DONATIONS_PAGE_ID,
				Mockery::on(
					static function ( callable $callback ) use ( $page_state ): bool {

						$page_state['callback'] = $callback;
						$GLOBALS['fundrik_test_donations_callback'] = $callback;

						return true;
					},
				),
			)
			->andReturn( 'fundrik_page_fundrik_donations' );

		$this->admin_page->register();

		self::assertIsCallable( $page_state['callback'] );
	}

	private function create_donations_list_service(): DonationsListService {

		$donation_read = Mockery::mock( DonationReadPort::class );
		$donation_query = new DonationQueryService(
			new ReadDonationByIdHandler( $donation_read ),
			new ReadPaginatedDonationsHandler( $donation_read ),
		);

		$database = Mockery::mock( DatabasePort::class );
		$campaign_read = new CampaignReadRepository( $database );
		$logger = Mockery::mock( LoggerInterface::class );

		return new DonationsListService(
			$donation_query,
			$campaign_read,
			$logger,
			new DateTimeFormatter(),
			new MoneyFormatter(),
		);
	}
}
