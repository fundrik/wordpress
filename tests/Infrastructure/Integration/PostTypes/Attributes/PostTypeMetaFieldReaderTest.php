<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeMetaField::class )]
final class PostTypeMetaFieldReaderTest extends TestCase {

	#[Test]
	public function get_meta_fields_returns_declared_meta_fields_from_constants(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertSame(
			[
				AlphaPostType::META_HAS_NESTED => [
					'type' => 'boolean',
					'default' => true,
				],
			],
			$reader->get_meta_fields( AlphaPostType::class ),
		);
	}

	#[Test]
	public function get_meta_fields_returns_empty_array_when_no_meta_fields_declared(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertSame( [], $reader->get_meta_fields( BetaPostType::class ) );
	}
}
