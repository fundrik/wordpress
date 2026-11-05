<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\DonationsAdminPage;
use Fundrik\WordPress\Integration\AdminPages\Pages\RootAdminPage;
use Fundrik\WordPress\Integration\AdminPages\Pages\SettingsAdminPage;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminPageDefinitions::class )]
final class AdminPageDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function constants_define_expected_admin_page_configuration(): void {

		self::assertSame( 'fundrik', AdminPageDefinitions::ROOT_MENU_SLUG );
		self::assertSame( 'fundrik_donations', AdminPageDefinitions::DONATIONS_PAGE_ID );
		self::assertSame( 'fundrik_settings', AdminPageDefinitions::SETTINGS_PAGE_ID );
		self::assertSame( 'edit_posts', AdminPageDefinitions::CONTENT_CAPABILITY );
		self::assertSame( 'manage_options', AdminPageDefinitions::SETTINGS_CAPABILITY );
	}

	#[Test]
	public function classes_returns_configured_admin_page_classes(): void {

		self::assertSame(
			[
				RootAdminPage::class,
				DonationsAdminPage::class,
				SettingsAdminPage::class,
			],
			AdminPageDefinitions::classes(),
		);
	}
}
