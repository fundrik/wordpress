<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use ArrayObject;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\RootAdminPage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RootAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
final class RootAdminPageTest extends WordPressTestCase {

	private RootAdminPage $admin_page;

	protected function setUp(): void {

		parent::setUp();

		$this->admin_page = new RootAdminPage();
	}

	#[Test]
	public function register_registers_root_menu_and_render_callback(): void {

		$page_state = new ArrayObject(
			[
				'callback' => null,
			],
		);

		Functions\expect( 'add_menu_page' )
			->once()
			->with(
				__( 'Fundrik', 'fundrik' ),
				__( 'Fundrik', 'fundrik' ),
				AdminPageDefinitions::CONTENT_CAPABILITY,
				AdminPageDefinitions::ROOT_MENU_SLUG,
				Mockery::on(
					static function ( callable $callback ) use ( $page_state ): bool {

						$page_state['callback'] = $callback;

						return true;
					},
				),
				'dashicons-heart',
			)
			->andReturn( 'toplevel_page_fundrik' );

		$this->admin_page->register();

		self::assertIsCallable( $page_state['callback'] );

		ob_start();
		( $page_state['callback'] )();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<h1>Fundrik</h1>', $output );
		self::assertStringContainsString( 'Use the submenu to open campaigns, donations, or settings.', $output );
	}
}
