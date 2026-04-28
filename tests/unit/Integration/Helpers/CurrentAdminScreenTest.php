<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Screen;

#[CoversClass( CurrentAdminScreen::class )]
final class CurrentAdminScreenTest extends WordPressTestCase {

	#[Test]
	public function is_post_type_returns_false_when_current_screen_is_missing(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( null );

		self::assertFalse( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}

	#[Test]
	public function is_post_type_returns_false_when_current_screen_is_not_a_wp_screen(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new \stdClass() );

		self::assertFalse( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}

	#[Test]
	public function is_post_type_returns_false_when_current_screen_has_another_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = 'post';

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		self::assertFalse( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}

	#[Test]
	public function is_post_type_returns_true_when_current_screen_matches_the_post_type(): void {

		$screen = Mockery::mock( WP_Screen::class );
		$screen->post_type = 'fundrik_campaign';

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( $screen );

		self::assertTrue( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}
}
