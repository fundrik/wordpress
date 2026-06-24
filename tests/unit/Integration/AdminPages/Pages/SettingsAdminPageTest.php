<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages\Pages;

use ArrayObject;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\Pages\SettingsAdminPage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SettingsAdminPage::class )]
#[UsesClass( AdminPageDefinitions::class )]
final class SettingsAdminPageTest extends WordPressTestCase {

	private SettingsAdminPage $admin_page;

	protected function setUp(): void {

		parent::setUp();

		$this->admin_page = new SettingsAdminPage();
	}

	#[Test]
	public function register_registers_settings_submenu_and_render_callback(): void {

		$page_state = new ArrayObject(
			[
				'callback' => null,
			],
		);

			Functions\expect( 'add_submenu_page' )
			->once()
			->with(
				AdminPageDefinitions::ROOT_MENU_SLUG,
				__( 'Fundrik Settings', 'fundrik' ),
				__( 'Settings', 'fundrik' ),
				AdminPageDefinitions::SETTINGS_CAPABILITY,
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				Mockery::on(
					static function ( callable $callback ) use ( $page_state ): bool {

						$page_state['callback'] = $callback;

						return true;
					},
				),
			)
			->andReturn( 'fundrik_page_fundrik_settings' );

		Functions\expect( 'settings_errors' )
			->once()
			->withNoArgs()
			->andReturnUsing(
				static function (): void {
					echo '<div class="notice"></div>';
				},
			);

		Functions\expect( 'settings_fields' )
			->once()
			->with( AdminPageDefinitions::SETTINGS_PAGE_ID )
			->andReturnUsing(
				static function ( string $option_group ): void {
					echo '<input type="hidden" name="option_page" value="' . esc_attr( $option_group ) . '" />';
				},
			);

		Functions\expect( 'do_settings_sections' )
			->once()
			->with( AdminPageDefinitions::ROOT_MENU_SLUG )
			->andReturnUsing(
				static function ( string $page ): void {
					echo '<div data-page="' . esc_attr( $page ) . '"></div>';
				},
			);

		Functions\expect( 'submit_button' )
			->once()
			->withNoArgs()
			->andReturnUsing(
				static function (): void {
					echo '<button type="submit">Save Changes</button>';
				},
			);

		$this->admin_page->register();

		self::assertIsCallable( $page_state['callback'] );

		ob_start();
		( $page_state['callback'] )();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<h1>Fundrik Settings</h1>', $output );
		self::assertStringContainsString( '<form action="options.php" method="post">', $output );
		self::assertStringContainsString( 'name="option_page" value="fundrik_settings"', $output );
		self::assertStringContainsString( 'data-page="fundrik"', $output );
		self::assertStringContainsString( 'Save Changes', $output );
	}
}
