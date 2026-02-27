<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostTypeConfig;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeMetaField::class )]
final class PostTypeMetaFieldReaderTest extends FundrikTestCase {

	#[Test]
	public function get_meta_fields_returns_declared_meta_fields_from_constants(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new AlphaPostTypeConfig();

		self::assertSame(
			[
				AlphaPostTypeConfig::META_HAS_NESTED => [
					'type' => 'boolean',
					'default' => true,
				],
			],
			$reader->get_meta_fields( $config ),
		);
	}

	#[Test]
	public function get_meta_fields_returns_empty_array_when_no_meta_fields_declared(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new BetaPostTypeConfig();

		self::assertSame( [], $reader->get_meta_fields( $config ) );
	}
}
