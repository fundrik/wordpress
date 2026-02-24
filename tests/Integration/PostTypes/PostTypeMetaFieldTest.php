<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\MetaFieldType;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PostTypeMetaField::class )]
final class PostTypeMetaFieldTest extends FundrikTestCase {

	#[Test]
	public function constructor_sets_type_and_default(): void {

		$attribute = new PostTypeMetaField( MetaFieldType::Boolean, true );

		self::assertSame( MetaFieldType::Boolean, $attribute->type );
		self::assertTrue( $attribute->default );
	}

	#[Test]
	public function to_array_returns_type_only_when_default_is_null(): void {

		$attribute = new PostTypeMetaField( MetaFieldType::Number );

		self::assertSame(
			[
				'type' => 'number',
			],
			$attribute->to_array(),
		);
	}

	#[Test]
	public function to_array_includes_default_when_provided(): void {

		$attribute = new PostTypeMetaField( MetaFieldType::Boolean, true );

		self::assertSame(
			[
				'type' => 'boolean',
				'default' => true,
			],
			$attribute->to_array(),
		);
	}
}
