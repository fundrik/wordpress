<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidPostType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass( PostTypeIdReader::class )]
#[UsesClass( PostTypeId::class )]
final class PostTypeIdReaderTest extends TestCase {

	#[Test]
	public function get_id_returns_attribute_value(): void {

		$reader = new PostTypeIdReader();

		self::assertSame( 'alpha', $reader->get_id( AlphaPostType::class ) );
	}

	#[Test]
	public function get_id_throws_when_attribute_missing(): void {

		$reader = new PostTypeIdReader();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			'Post type ID must be declared via #[PostTypeId] attribute. Given: ' . InvalidPostType::class . '.',
		);

		$reader->get_id( InvalidPostType::class );
	}
}
