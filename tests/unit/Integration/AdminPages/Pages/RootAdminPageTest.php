<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\RootAdminPage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RootAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
final class RootAdminPageTest extends WordPressTestCase {

	private RootAdminPage $admin_page;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->admin_page = new RootAdminPage();
	}

	#[Test]
	public function register_registers_root_menu_with_render_callback(): void {

		$render = null;

		Functions\expect( 'add_menu_page' )
			->once()
			->with(
				__( 'Fundrik', 'fundrik' ),
				__( 'Fundrik', 'fundrik' ),
				AdminPageDefinitions::CONTENT_CAPABILITY,
				AdminPageDefinitions::ROOT_MENU_SLUG,
				Mockery::capture( $render ),
				'dashicons-heart',
			);

		$this->admin_page->register();

		self::assertIsCallable( $render );

		ob_start();
		$render();
		$output = preg_replace( '/>\s+</', '><', trim( (string) ob_get_clean() ) );

		self::assertSame(
			'<div class="wrap"><h1>Fundrik</h1><p>Use the submenu to open campaigns, donations, or settings.</p></div>',
			$output,
		);
	}
}
