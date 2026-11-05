<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\SettingsAdminPage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SettingsAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
final class SettingsAdminPageTest extends WordPressTestCase {

	private SettingsAdminPage $admin_page;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->admin_page = new SettingsAdminPage();
	}

	#[Test]
	public function register_registers_settings_submenu_with_render_callback(): void {

		$render = null;

		Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				AdminPageDefinitions::ROOT_MENU_SLUG,
				__( 'Fundrik Settings', 'fundrik' ),
				__( 'Settings', 'fundrik' ),
				AdminPageDefinitions::SETTINGS_CAPABILITY,
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				Mockery::capture( $render ),
			);

		Functions\expect( 'settings_errors' )
			->once()
			->withNoArgs();

		Functions\expect( 'settings_fields' )
			->once()
			->with( AdminPageDefinitions::SETTINGS_PAGE_ID );

		Functions\expect( 'do_settings_sections' )
			->once()
			->with( AdminPageDefinitions::ROOT_MENU_SLUG );

		Functions\expect( 'submit_button' )
			->once()
			->withNoArgs();

		$this->admin_page->register();

		self::assertIsCallable( $render );

		ob_start();
		$render();
		$output = preg_replace( '/>\s+</', '><', trim( (string) ob_get_clean() ) );

		self::assertSame(
			'<div class="wrap"><h1>Fundrik Settings</h1><form action="options.php" method="post"></form></div>',
			$output,
		);
	}
}
