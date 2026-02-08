<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( PostTypeId::class )]
final class PostTypeIdTest extends TestCase {

	#[Test]
	public function constructor_sets_value(): void {

		$attribute = new PostTypeId( 'alpha' );

		self::assertSame( 'alpha', $attribute->value );
	}
}
