<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\CurrentAdminScreen;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpScreen;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

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
	public function is_post_type_returns_false_when_current_screen_has_another_post_type(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( 'post' ) );

		self::assertFalse( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}

	#[Test]
	public function is_post_type_returns_true_when_current_screen_matches_post_type(): void {

		Functions\expect( 'get_current_screen' )
			->once()
			->andReturn( new WpScreen( 'fundrik_campaign' ) );

		self::assertTrue( CurrentAdminScreen::is_post_type( 'fundrik_campaign' ) );
	}
}
