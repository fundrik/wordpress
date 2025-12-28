<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplate;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplateReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidPostType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass( PostTypeBlockTemplateReader::class )]
#[UsesClass( PostTypeBlockTemplate::class )]
final class PostTypeBlockTemplateReaderTest extends TestCase {

	#[Test]
	public function get_template_returns_attribute_value(): void {

		$reader = new PostTypeBlockTemplateReader();

		self::assertSame( [ [ 'core/paragraph' ] ], $reader->get_template( AlphaPostType::class ) );
	}

	#[Test]
	public function get_template_throws_when_attribute_missing(): void {

		$reader = new PostTypeBlockTemplateReader();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			'Post type block template must be declared via #[PostTypeBlockTemplate] attribute. Given: ' . InvalidPostType::class . '.',
		);

		$reader->get_template( InvalidPostType::class );
	}
}
