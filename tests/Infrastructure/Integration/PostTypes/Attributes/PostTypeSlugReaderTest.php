<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlug;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlugReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidPostType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass( PostTypeSlugReader::class )]
#[UsesClass( PostTypeSlug::class )]
final class PostTypeSlugReaderTest extends TestCase {

	#[Test]
	public function get_slug_returns_attribute_value(): void {

		$reader = new PostTypeSlugReader();

		self::assertSame( 'alphas', $reader->get_slug( AlphaPostType::class ) );
	}

	#[Test]
	public function get_slug_throws_when_attribute_missing(): void {

		$reader = new PostTypeSlugReader();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			'Post type slug must be declared via #[PostTypeSlug] attribute. Given: ' . InvalidPostType::class . '.',
		);

		$reader->get_slug( InvalidPostType::class );
	}
}
