<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( PostTypeSpecificBlock::class )]
final class PostTypeSpecificBlockTest extends TestCase {

	#[Test]
	public function constructor_sets_value(): void {

		$attribute = new PostTypeSpecificBlock( 'fundrik/shared' );

		self::assertSame( 'fundrik/shared', $attribute->value );
	}
}
