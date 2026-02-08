<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes\Attributes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeBlockTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( PostTypeBlockTemplate::class )]
final class PostTypeBlockTemplateTest extends TestCase {

	#[Test]
	public function constructor_sets_value(): void {

		$template = [ [ 'core/paragraph' ], [ 'core/image' ] ];

		$attribute = new PostTypeBlockTemplate( $template );

		self::assertSame( $template, $attribute->value );
	}
}
