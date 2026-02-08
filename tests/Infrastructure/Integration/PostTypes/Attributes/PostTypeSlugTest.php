<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSlug;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( PostTypeSlug::class )]
final class PostTypeSlugTest extends TestCase {

	#[Test]
	public function constructor_sets_value(): void {

		$attribute = new PostTypeSlug( 'alphas' );

		self::assertSame( 'alphas', $attribute->value );
	}
}
