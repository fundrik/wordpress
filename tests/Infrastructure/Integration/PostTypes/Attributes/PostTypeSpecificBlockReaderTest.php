<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlockReader;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidPostType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass( PostTypeSpecificBlockReader::class )]
#[UsesClass( PostTypeSpecificBlock::class )]
final class PostTypeSpecificBlockReaderTest extends TestCase {

	#[Test]
	public function get_blocks_returns_all_repeatable_attribute_values_in_declaration_order(): void {

		$reader = new PostTypeSpecificBlockReader();

		self::assertSame(
			[ 'fundrik/alpha-only', 'fundrik/shared' ],
			$reader->get_blocks( AlphaPostType::class ),
		);
	}

	#[Test]
	public function get_blocks_throws_when_attribute_missing(): void {

		$reader = new PostTypeSpecificBlockReader();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			'Post type specific blocks must be declared via #[PostTypeSpecificBlock] attribute. Given: ' . InvalidPostType::class . '.',
		);

		$reader->get_blocks( InvalidPostType::class );
	}
}
